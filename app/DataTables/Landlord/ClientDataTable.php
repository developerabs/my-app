<?php

namespace App\DataTables\Landlord;

use App\Models\Client;
use App\Models\landlord\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class ClientDataTable extends DataTable
{
    protected $user;
    public function __construct()
    {
        $this->user = Auth::user();
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Client> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->addColumn('tenant', fn($row) => $row->id)
            ->addColumn('domains', function ($row) {
                return $row->domains->pluck('domain')
                    ->map(fn($d) => '<a href="http://' . $d . '" target="_blank">' . $d . '</a>')
                    ->implode('<br>');
            })
            ->addColumn('package', function ($row) {
                $package = $row->package ? $row->package->name : 'N/A';
                return $package;
            })
            ->editColumn('created_at', fn($row) => $row->created_at->format('d-M-Y'))
            ->editColumn('status', function ($row) {
                return $row->status ? '<span class="badge bg-success text-white">' . $row->status . '</span>' : '<span class="badge bg-danger text-white">' . $row->status . '</span>';
            })
            // FIXED: expire_date now works
            ->addColumn('expire_date', function ($row) {
                $expire_date = $row->expires_at ? Carbon::parse($row->expires_at)->format('d-M-Y') : 'N/A';
                $expire_date_html = '';
                if ($row->expires_at < Carbon::now()) {
                    $expire_date_html = '<span class="badge bg-danger text-white">' . $expire_date . '</span>';
                } else {
                    $expire_date_html = '<span class="badge bg-success text-white">' . $expire_date . '</span>';
                }
                return $expire_date_html;
            })

            ->addColumn('action', function ($row) {
                $edit = '';
                $delete = '';
                if($this->user->can('client_edit')) {
                    $edit = '<a href="#" class="btn btn-sm btn-primary me-2"><i class="fa-solid fa-pen-to-square"></i></a>';
                }
                if($this->user->can('client_delete')) {
                    $delete = '<button type="button" onclick="deleteClient(\'' . $row->id . '\')" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button>';
                }
                return '<div class="d-flex justify-content-end">'.$edit.$delete.'</div>';
            })
            ->rawColumns(['domains', 'status', 'expire_date', 'action']);
    }


    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Client>
     */
    public function query(Tenant $model): QueryBuilder
    {
        $query = $model->newQuery();

        if(Auth::user()->hasRole('Reseller')) {
            $query->where('data->reseller_id', Auth::user()->reseller_id);
        }
        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('client-table')
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
                'dom' => "<'row mb-3'<'col-md-2 d-flex align-items-center d-none d-md-block'l><'col-md-4 order-3 order-md-2 d-flex align-items-center justify-content-center'f><'col-md-6 order-2 order-md-3 d-flex align-items-center justify-content-end text-end'B>>" . // Top: left=length, center=search, right=buttons
                    "<'row'<'col-12'tr>>" .                                        // Table
                    "<'row mt-3'<'col-12 col-md-5'i><'col-12 col-md-7 d-flex justify-content-center justify-content-md-end'p>>",
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
            Column::make('DT_RowIndex')->title('#')->orderable(false)->searchable(false)->addClass('text-start')->responsivePriority(1),
            Column::make('tenant')->title(__('file.table.tenants')),
            Column::make('domains')->title(__('file.table.domains')),
            Column::make('package')->title(__('file.table.package')),
            Column::make('created_at')->title(__('file.table.created_at')),
            Column::make('status')->title(__('file.table.status'))->orderable(false)->searchable(false),
            Column::make('expire_date')->title(__('file.table.expire_date')),
            Column::computed('action')->title(__('file.table.action'))->exportable(false)->printable(false)->addClass('text-end')->responsivePriority(1),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Client_' . date('YmdHis');
    }
}
