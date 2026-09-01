<?php

namespace App\DataTables\Landlord;

use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use App\Models\landlord\ResellerClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ClientDuesDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()

            ->addColumn('tenant', function ($row) {
                if (!$row->tenant) return 'N/A';
                return '<div>
                    <small>' . ($row->tenant->id ?? 'N/A') . '</small><br>
                    <small>' . ($row->tenant->phone ?? $row->tenant->mobile ?? 'N/A') . '</small>
                </div>';
            })

            ->addColumn('reseller', function ($row) {
                if (!$row->reseller) return 'N/A';
                return '<div>
                        <small>' . ($row->reseller->name ?? 'N/A') . '</small><br>
                        <small>' . ($row->reseller->phone ?? $row->reseller->mobile ?? 'N/A') . '</small>
                    </div>';
            })
             ->addColumn('latest_note', function($row) {
                    return '<div  title="' . e($row->latestNote->note ?? 'N/A') . '">' . Str::limit($row->latestNote->note ?? 'N/A', 50) .
                        '<br><small class="text-muted">' . $row->latestNote?->created_at->format('d M, Y H:i') . '</small></div>';
                })
            ->editColumn('registration_fee', function ($row) {
                return number_format($row->registration_fee, 2);
            })
            ->editColumn('commission', function ($row) {
                return '<div>
                        <div>Reseller: ' . number_format($row->comission_amount, 2) . '</div>
                        <div>Admin: ' . number_format($row->admin_receivable, 2) . '</div>
                    </div>';
            })

            ->editColumn('paid', function ($row) {
                return number_format($row->paid, 2);
            })

            ->addColumn('due', function ($row) {
                return '<div class="d-flex flex-column">
                        <span>Reseller Due' . number_format($row->due - $row->admin_due, 2) . '</span>
                        <span>Admin Due: ' . number_format($row->admin_due, 2) . '</span>
                        <span>Total Due: ' . number_format($row->due, 2) . '</span>
                    </div>';
            })
            ->addColumn('action', function ($row) {
                return '
                <div class="dropdown text-start">
                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        Action
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="#" class="dropdown-item createNoteBtn" data-reseller-client-id="' . $row->id . '">
                                <i class="fas fa-plus"></i> Create Note
                            </a>
                        </li>
                        <li>
                            <a href="#" class="dropdown-item" onclick="viewClientNotes(' . $row->id . ')">
                                <i class="fas fa-eye"></i> View All Notes
                            </a>
                        </li>
                    </ul>
                </div>';
            })
            ->rawColumns(['tenant', 'reseller', 'due', 'paid', 'commission', 'action', 'latest_note']);
    }

    public function query(ResellerClient $model): QueryBuilder
    {
        
        $query = $model->newQuery()
            ->with(['tenant', 'reseller']);

        if (Auth::user()->hasRole('Reseller')) {
            $query->where('reseller_id', Auth::user()->reseller_id);
        }

        if ($search = request('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('tenant', function ($t) use ($search) {
                    $t->where('data->tenant', 'like', "%{$search}%")
                    ->orWhere('data->phone', 'like', "%{$search}%");
                })
                ->orWhereHas('reseller', function ($r) use ($search) {
                    $r->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
                });
            });
        }
        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('clientdues-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'scrollY' => '70vh',
                'scrollCollapse' => true,
                'fixedHeader' => [
                    'header' => true,
                ],
                'responsive' => true,
                'autoWidth' => false,
                'order' => [[1, 'asc']],
                'dom' => "<'row mb-3'<'col-md-2 d-flex align-items-center d-none d-md-block'l><'col-md-4 order-3 order-md-2 d-flex align-items-center justify-content-center'f><'col-md-6 order-2 order-md-3 d-flex align-items-center justify-content-end text-end'B>>"
                        . "<'row'<'col-12'tr>>"
                        . "<'row mt-3'<'col-12 col-md-5'i><'col-12 col-md-7 d-flex justify-content-center justify-content-md-end'p>>",
                'buttons' => [
                    ['extend' => 'excel', 'className' => 'btn btn-success me-2', 'text' => '<i class="fa-solid fa-file-excel"></i>'],
                    ['extend' => 'csv', 'className' => 'btn btn-info me-2', 'text' => '<i class="fa-solid fa-file-csv"></i>'],
                    ['extend' => 'pdf', 'className' => 'btn btn-danger me-2', 'text' => '<i class="fa-solid fa-file-pdf"></i>'],
                    ['extend' => 'print', 'className' => 'btn btn-primary me-2', 'text' => '<i class="fa-solid fa-print"></i>'],
                    ['text' => '<i class="fa-solid fa-rotate-left"></i>', 'className' => 'btn btn-secondary me-2', 'action' => 'function ( e, dt, node, config ) { dt.search("").columns().search("").draw(); }'],
                    ['text' => '<i class="fa-solid fa-arrows-rotate"></i>', 'className' => 'btn btn-warning', 'action' => 'function ( e, dt, node, config ) { dt.ajax.reload(); }']
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

    public function getColumns(): array
    {
        return [
            Column::make('DT_RowIndex')->title('#')->addClass('text-start')->orderable(false)->searchable(false)->exportable(false)->printable(false)->responsivePriority(1),
            Column::make('tenant')->title(__('file.table.client'))->orderable(false)->searchable(false)->addClass('text-start'),
            Column::make('reseller')->title(__('file.table.reseller'))->orderable(false)->searchable(false)->addClass('text-start'),
            Column::make('latest_note')->title(__('file.table.latest_note'))->addClass('text-start')->orderable(false)->searchable(false)->responsivePriority(2),
            Column::make('registration_fee')->title(__('file.table.registration_fee'))->addClass('text-start')->orderable(false)->searchable(false)->responsivePriority(3),
            Column::make('commission')->title(__('file.table.commission'))->addClass('text-start')->orderable(false)->searchable(false)->responsivePriority(4),
            Column::make('due')->title(__('file.table.due'))->addClass('text-start')->orderable(false)->searchable(false)->responsivePriority(1),
            Column::make('paid')->title(__('file.table.paid'))->addClass('text-start')->orderable(false)->searchable(false)->responsivePriority(1),
            Column::computed('action')->title(__('file.table.action'))->addClass('text-start')->orderable(false)->searchable(false)->exportable(false)->printable(false)->responsivePriority(1),
        ];
    }

    protected function filename(): string
    {
        return 'ClientDues_' . date('YmdHis');
    }
}
