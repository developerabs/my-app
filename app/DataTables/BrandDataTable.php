<?php

namespace App\DataTables;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class BrandDataTable extends BaseDataTable
{
    protected string $tableId = 'brand-table';

    protected function getExportColumns(): array|string
    {
        return [2, 3, 4, 5];
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Brand> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->addColumn('image', function ($row) {
                return '<img style="height: 30px;" src="' . $row->thumb_url . '" class="img-fluid" alt="' . $row->name . '" />';
            })
            ->editColumn('status', function ($row) {
                return $row->is_active
                    ? '<span class="badge bg-success">' . __('file.table.active') . '</span>'
                    : '<span class="badge bg-danger">' . __('file.table.inactive') . '</span>';
            })
            ->addColumn('action', function ($row) {
                $user = Auth::user();
                $actions = '';

                if ($user->can('brand_update')) {
                    $actions .= '<a href="javascript:void(0)" onclick="editBrand(\'' . (string) $row->id . '\')" class="btn btn-primary btn-sm" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>';
                }

                if ($user->can('brand_delete')) {
                    $url = route('brands.destroy', (string) $row->id);
                    $actions .= '<button type="button" data-url="' . $url . '" data-table-id="#brand-table" data-item="' . $row->name . '" data-name="Brand" class="btn btn-danger btn-sm delete-btn" title="Delete"><i class="fa-solid fa-trash"></i></button>';
                }

                if (empty($actions)) {
                    return '<span class="badge bg-secondary">No Access</span>';
                }

                return '<div class="btn-group gap-1">' . $actions . '</div>';
            })
            ->setRowId('id')
            ->rawColumns(['index', 'image', 'status', 'action']);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Brand>
     */
    public function query(Brand $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('name', 'asc');
    }

    protected function getAjaxParams(): array
    {
        return [
            'status'  => '$("#filter-status").val()',
            'source'  => '$("#filter-source").val()',
        ];
    }

    protected function getCustomButtons(): array
    {
        return [
            [
                'text' => '<i class="fa-solid fa-trash"></i><span class="bulk-count d-none">(0)</span>',
                'className' => 'btn btn-danger btn-bulk-delete me-2 disabled',
                'attr' => [
                    'id' => 'bulk-delete-btn',
                    'data-url' => route('brands.bulk-delete'),
                ],
                'init' => 'function(dt, node, config){ $(node).attr("title", "Bulk Delete").tooltip({placement:"top"}); }'
            ]
        ];
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('image')->title(__('file.table.image'))->orderable(false)->searchable(false)->exportable(false)->printable(false)->responsivePriority(1),
            Column::make('name')->data('name')->title(__('file.table.name'))->responsivePriority(2),
            Column::make('description')->data('description')->title(__('file.table.description')),
            Column::make('status')->data('status')->name('brands.is_active')->title(__('file.table.status')),
            Column::make('source')->data('source_from')->name('brands.source_from')->title(__('file.table.source')),
            Column::make('action')->title(__('file.table.action'))->addClass('text-end')->orderable(false)->searchable(false)->exportable(false)->printable(false)->responsivePriority(3),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Brand_' . date('YmdHis');
    }
}
