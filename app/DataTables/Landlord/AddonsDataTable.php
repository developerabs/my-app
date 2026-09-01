<?php

namespace App\DataTables\Landlord;

use App\Models\landlord\Addon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class AddonsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Addon> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="'.$row->id.'" />';
            })
            ->addColumn('image', function ($row) {
                $image_src = $row->meta['image'] ? asset('storage/'.$row->meta['image']) : asset('images/preview_image.png');
                return '<img src="' . $image_src . '" alt="' . $row->name . '" class="img-thumbnail" width="50" height="50" />';
            })
            ->addColumn('description', function ($row) {
                $limit = $row->meta['limit_mode'] ?? '';
                $limitVal = $row->meta['limit_value'] ?? '';
                return $limit.'-'.$limitVal;
            })
            ->addColumn('addon_price', function ($row) {
                return $row->price;
            })
            ->addColumn('addon_duration', function ($row) {
                return $row->duration_days . ' days';
            })
            ->addColumn('status', function ($row) {
                return '<span class="badge ' . ($row->is_active == 1 ? 'bg-success' : 'bg-danger') . ' text-white ">' . ($row->is_active == 1 ? 'Active' : 'Inactive') . '</span>';
            })
            ->addColumn('action', function ($row) {

                $isUsed = $row->isUsedByTenant();

                if($isUsed){
                    $edit = '<a href="javascript:void(0)" class="btn  btn-primary disabled"><i class="fa-solid fa-pen-to-square"></i></a>';
                    $delete = '<a href="javascript:void(0)" class="btn  btn-danger disabled"><i class="fa-solid fa-trash"></i></a>';
                }else{
                    $edit = '<a href="javascript:void(0)" onclick="editAddon('.$row->id.')" class="btn  btn-primary"><i class="fa-solid fa-pen-to-square"></i></a>';
                    $delete = '<a href="javascript:void(0)" onclick="deleteAddon('.$row->id.')" class="btn  btn-danger"><i class="fa-solid fa-trash"></i></a>';
                }
                return $edit.' '.$delete;
            })
            ->rawColumns(['index', 'image', 'status', 'action']);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Addon>
     */
    public function query(Addon $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('addons-table')
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
            Column::make('index')->title('<input type="checkbox" class="select-all" />')->addClass('text-center')->width('20px')->orderable(false)->searchable(false)->exportable(false)->printable(false)->width('20px')->responsivePriority(1),
            Column::make('image')->title(__('file.table.image'))->orderable(false)->searchable(false)->exportable(false)->printable(false),
            Column::make('name')->title(__('file.table.name')),
            Column::make('type')->title(__('file.table.type'))->orderable(false)->searchable(false),
            Column::make('description')->title(__('file.table.description'))->orderable(false)->searchable(false),
            Column::make('addon_price')->title(__('file.table.addon_price'))->addClass('text-end')->orderable(false)->searchable(false),
            Column::make('addon_duration')->title(__('file.table.addon_duration'))->addClass('text-center'),
            Column::make('status')->title(__('file.table.status'))->orderable(false)->searchable(false),
            Column::computed('action')->title(__('file.table.action'))->exportable(false)->printable(false)->addClass('text-end')->responsivePriority(1),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Addons_' . date('YmdHis');
    }
}
