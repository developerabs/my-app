<?php

namespace App\DataTables;

// use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Spatie\Activitylog\Models\Activity as ActivityLog;

class ActivityLogDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<ActivityLog> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $dataTable = new EloquentDataTable($query);

        //activity global search implementation
        $dataTable->filter(function (QueryBuilder $query) {
            $searchValue = request()->input('search.value');
            if (!empty($searchValue)) {
                $searchWildcard = '%' . $searchValue . '%';

                $query->where(function ($q) use ($searchWildcard) {
                    // ১. সাধারণ টেক্সট সার্চ
                    $q->orWhere('description', 'ILIKE', $searchWildcard);

                    // ২. JSON কলাম থেকে টেক্সট বের করে সার্চ (কাস্টিং সহ)
                    // PostgreSQL-এ (column->>'key') সরাসরি টেক্সট দেয়
                    $q->orWhereRaw("properties->>'ip' ILIKE ?", [$searchWildcard]);
                    $q->orWhereRaw("properties->>'attributes' ILIKE ?", [$searchWildcard]);
                    $q->orWhereRaw("properties->>'old' ILIKE ?", [$searchWildcard]);
                    $q->orWhereRaw("properties->>'user_agent' ILIKE ?", [$searchWildcard]);

                    // ৩. রিলেশনশিপ সার্চ
                    $q->orWhereHas('causer', function ($causerQuery) use ($searchWildcard) {
                        $causerQuery->where('name', 'ILIKE', $searchWildcard);
                    });
                });
            }
        });
        return $dataTable
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->editColumn('created_at', function ($row) {
                // English: Stacked layout for mobile, single line for desktop using Bootstrap classes
                return '
        <div class="d-flex flex-column flex-md-row gap-md-1 align-items-start align-items-md-center" style="line-height: 1.2;">
            <span class="fw-bold text-dark" style="font-size: calc(12px + 0.1vw);">' . $row->created_at->format('d M, Y') . '</span>
            <span class="text-muted d-block d-md-inline" style="font-size: calc(10px + 0.1vw);">' . $row->created_at->format('h:i A') . '</span>
        </div>
    ';
            })
            ->addColumn('user', function ($row) {
                return optional($row->causer)->name ?? 'System';
            })
            ->addColumn('action', function ($row) {
                $desc = ucfirst($row->description);
                if ($desc == 'Created') return '<span class="badge bg-success">Created</span>';
                if ($desc == 'Updated') return '<span class="badge bg-warning text-dark">Updated</span>';
                if ($desc == 'Deleted') return '<span class="badge bg-danger">Deleted</span>';
                return '<span class="badge bg-info">' . $desc . '</span>';
            })
            ->addColumn('view', function ($row) {
                return '<button type="button" class="btn btn-sm btn-primary" onclick="showDetails(' . $row->id . ')">View</button>';
            })
            ->addColumn('target_model', function ($row) {
                // English: Fallback if no subject model exists
                if (!$row->subject_type) {
                    return '<span class="text-muted small">System</span>';
                }

                // English: Extract model name and format to ucfirst
                $modelPath = explode('\\', $row->subject_type);
                $modelName = ucfirst(strtolower(end($modelPath)));

                // English: Minimalist text display, aligned to start
                return '
                    <div class="text-start">
                        <span class="fw-semibold text-dark" style="font-size: 14px;">'
                    . $modelName .
                    '</span>
                    </div>
                ';
            })
            ->addColumn('ip_address', function ($row) {
                $ip = $row->properties['ip'] ?? null;
                if ($ip == '::1') $ip = '127.0.0.1';
                $agent = $row->properties['user_agent'] ?? null;
                $browser = $this->detectBrowser($agent);

                return '
                <div class="d-flex gap-2">
                    <span class="badge bg-primary d-flex align-items-center">' . $ip . '</span><br>
                    <small class="text-muted">
                        <i class="fa fa-globe"></i> ' . $browser . '
                    </small>
                </div>
            ';
            })
            ->rawColumns(['index', 'created_at', 'user', 'action', 'view', 'target_model', 'ip_address']);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<ActivityLog>
     */

    public function query(ActivityLog $model): QueryBuilder
    {
        $query = $model->with('causer')->newQuery();

        if ($startDate = request('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = request('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $query->when(request('event'), function ($q) {
            return $q->where('activity_log.description', request('event'));
        });

        $query->when(request('user_id'), function ($q) {
            return $q->where('activity_log.causer_id', (string) request('user_id'));
        });

        $query->when(request('filter_model'), function ($q) {
            return $q->where('activity_log.subject_type', request('filter_model'));
        });

        // ৩. রোল ভিত্তিক পারমিশন
        if (!Auth::user()->hasRole('Super Admin')) {
            $query->where('causer_id', Auth::id());
        }

        return $query->latest();
    }
    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('activity-log-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->ajax([
                'url' => route('activity-log'),
                'type' => 'GET',
                'data' => 'function (d) {
                    // টেবিল আইডি ধরে ডাটা বের না করে সরাসরি উইন্ডো অবজেক্ট বা গ্লোবাল সেটিংস চেক করা ভালো
                    // এখানে ট্রাই-ক্যাচ ব্যবহার করছি যাতে এরর আসলে পুরো টেবিল ক্র্যাশ না করে
                    try {
                        var tableElement = $("#activity-log-table");
                        if ($.fn.DataTable.isDataTable(tableElement)) {
                            var settings = tableElement.DataTable().settings()[0];
                            if (settings && settings._dateFilter) {
                                d.start_date = settings._dateFilter.start;
                                d.end_date   = settings._dateFilter.end;
                            }
                        }
                    } catch (e) {
                        console.log("DataTable not yet initialized");
                    }
                    d.event = $("#filter-event").val();
                    d.user_id = $("#filter-user").val();
                    d.filter_model = $("#filter-model").val();
                }'
            ])
            ->parameters([
                'scrollY' => '70vh',
                'scrollCollapse' => true,
                'fixedHeader' => [
                    'header' => true,
                    'processing' => true,
                    'serverSide' => true,
                ],
                'responsive' => true,
                'autoWidth' => false,
                'order' => [[1, 'asc']],
                'dom' => "<'row mb-3'
                        <'col-md-2 d-none d-md-block'l>
                        <'col-md-3'f> 
                        <'col-md-3'd>
                        <'col-md-4 d-flex justify-content-center justify-content-md-end text-end mt-2 mt-md-0'B>>"
                    . "<'row'<'col-12'tr>>"
                    . "<'row mt-3'<'col-12 col-md-5'i><'col-12 col-md-7 d-flex justify-content-center justify-content-md-end'p>>",
                'buttons' => [
                    [
                        'extend' => 'excel',
                        'className' => 'btn  btn-success me-2',
                        'text' => '<i class="fa-solid fa-file-excel"></i>',
                        'init' => 'function(dt, node, config){
                                    $(node).attr("title", "Export to Excel").tooltip({placement:"top"});
                                }'
                    ],
                    [
                        'extend' => 'csv',
                        'className' => 'btn  btn-info me-2',
                        'text' => '<i class="fa-solid fa-file-csv"></i>',
                        'init' => 'function(dt, node, config){
                                    $(node).attr("title", "Export to CSV").tooltip({placement:"top"});
                                }'
                    ],
                    [
                        'extend' => 'pdf',
                        'className' => 'btn  btn-danger me-2',
                        'text' => '<i class="fa-solid fa-file-pdf"></i>',
                        'init' => 'function(dt, node, config){
                                    $(node).attr("title", "Export to PDF").tooltip({placement:"top"});
                                }'
                    ],
                    [
                        'extend' => 'print',
                        'className' => 'btn  btn-primary me-2',
                        'text' => '<i class="fa-solid fa-print"></i>',
                        'init' => 'function(dt, node, config){
                                    $(node).attr("title", "Print").tooltip({placement:"top"});
                                }'
                    ],
                    [
                        'text' => '<i class="fa-solid fa-rotate-left"></i>',
                        'className' => 'btn  btn-secondary me-2',
                        'action' => 'function ( e, dt, node, config ) { dt.search("").columns().search("").draw(); }',
                        'init' => 'function(dt, node, config){
                                    $(node).attr("title", "Clear Filters").tooltip({placement:"top"});
                                }'
                    ],
                    [
                        'text' => '<i class="fa-solid fa-arrows-rotate"></i>',
                        'className' => 'btn  btn-warning',
                        'action' => 'function ( e, dt, node, config ) { dt.ajax.reload(); }',
                        'init' => 'function(dt, node, config){
                                    $(node).attr("title", "Refresh").tooltip({placement:"top"});
                                }'
                    ]
                ],
                'language' => [
                    'search' => '',
                    'searchPlaceholder' => __('file.table.search') . '...',
                    'lengthMenu' => "_MENU_ ",
                    'info' => __('file.table.showing') . ' _START_ ' . __('file.table.to') . ' _END_ ' . __('file.table.of') . ' _TOTAL_ ' . __('file.table.entries'),
                    'infoEmpty' => __('file.table.no_entries'),
                    'infoFiltered' => '(' . __('file.table.filtered_from') . ' _MAX_ ' . __('file.table.total_entries') . ')',
                    'zeroRecords' => __('file.table.no_matching_records'),
                    'paginate' => [
                        'previous' => __('file.table.prev'),
                        'next' => __('file.table.next'),
                    ],
                ],
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('index')->title('<input type="checkbox" class="select-all" />')->addClass('text-center')->width('20px')->orderable(false)->searchable(false)->exportable(false)->printable(false),
            Column::make('created_at')
                ->title('Date & Time')
                ->addClass('text-start align-middle')
                ->searchable(false)
                ->width('110px')
                ->responsivePriority(1),
            // English: Use only data, avoid .name to prevent automatic relationship join errors in Postgres
            Column::make('user')->data('user')->searchable(false)->title(__('file.table.user')),
            Column::make('action')->data('action')->searchable(false)->title(__('file.table.action'))->responsivePriority(2),
            Column::make('view')->title(__('file.table.view'))->orderable(false)->searchable(false)->responsivePriority(3),
            Column::make('target_model')->data('target_model')->searchable(false)->title(__('file.table.target_model')),
            Column::make('ip_address')->data('ip_address')->searchable(false)->title(__('file.table.ip_address')),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'ActivityLog_' . date('YmdHis');
    }

    private function detectBrowser($agent)
    {
        if (!$agent) return '-';
        if (stripos($agent, 'Chrome') !== false) return 'Chrome';
        if (stripos($agent, 'Firefox') !== false) return 'Firefox';
        if (stripos($agent, 'Safari') !== false) return 'Safari';
        if (stripos($agent, 'Edge') !== false) return 'Microsoft Edge';
        if (stripos($agent, 'Opera') !== false || stripos($agent, 'OPR') !== false) return 'Opera';

        return 'Unknown Browser';
    }
}
