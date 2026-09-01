<?php

namespace App\DataTables\Landlord;

use App\Models\landlord\Page;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class PagesDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Page> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('slug', function ($row) {
                return '<a href="' . url('pages', $row->slug) . '">' . url('pages', $row->slug) . '</a>';
            })
            ->editColumn('status', function ($row) {
                return '
                    <select class="form-select pageStatus" data-id="' . $row->id . '" aria-label="Status">
                        <option value="published" ' . ($row->status === 'published' ? 'selected' : '') . '>' . __('file.option.Published') . '</option>
                        <option value="draft" ' . ($row->status === 'draft' ? 'selected' : '') . '>' . __('file.option.Draft') . '</option>
                        <option value="archived" ' . ($row->status === 'archived' ? 'selected' : '') . '>' . __('file.option.Archived') . '</option>
                    </select>
                ';
            })
            ->addColumn('action', function ($row) {
                $editBtn = '<a href="' . route('landlord.pages.edit', $row->id) . '" class="btn btn-sm btn-primary"><i class="fa-solid fa-pen-to-square me-1"></i></a>';
                $deleteBtn = '<a href="javascript:void(0);" onclick="deletePage('.$row->id.')" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash me-1"></i></a>';
                return $editBtn . ' ' . $deleteBtn;
            })
            ->rawColumns(['DT_RowIndex', 'slug', 'status', 'action'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Page>
     */
    public function query(Page $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('pages-table')
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
            Column::make('title')->title(__('file.table.title')),
            Column::make('slug')->title(__('file.table.slug'))->orderable(false)->searchable(false),
            Column::make('status')->title(__('file.table.status'))->orderable(false)->searchable(false),
            Column::computed('action')->title(__('file.table.action'))->exportable(false)->printable(false)->addClass('text-end')->responsivePriority(1),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Pages_' . date('YmdHis');
    }
}
