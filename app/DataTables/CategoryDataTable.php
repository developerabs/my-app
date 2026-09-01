<?php

namespace App\DataTables;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class CategoryDataTable extends BaseDataTable
{
    protected string $tableId = 'category-table';

    /**
     * English: Override export columns for Category
     */
    protected function getExportColumns(): array|string
    {
        return [2, 3, 4, 5, 6];
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->addColumn('image', function ($row) {
                return '<img style="height: 30px;" src="' . $row->thumb_url . '" class="img-fluid" alt="' . $row->name . '" />';
            })
            ->editColumn('parent_category', function ($row) {
                return $row->parent_name ?? '<span class="text-muted">--</span>';
            })
            ->editColumn('category_type', function ($row) {
                return $row->type_display_name ?? '<span class="text-muted">--</span>';
            })
            ->editColumn('status', function ($row) {
                return $row->is_active
                    ? '<span class="badge bg-success">' . __('file.table.active') . '</span>'
                    : '<span class="badge bg-danger">' . __('file.table.inactive') . '</span>';
            })
            ->addColumn('action', function ($row) {
                $user = Auth::user();
                $actions = '';

                if ($user->can('category_update')) {
                    $actions .= '<a href="javascript:void(0)" onclick="editCategory(\'' . (string) $row->id . '\')" class="btn btn-primary btn-sm" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>';
                }

                if ($user->can('category_delete')) {
                    $url = route('categories.destroy', (string) $row->id);
                    $actions .= '<button type="button" data-url="' . $url . '" data-table-id="#category-table" data-item="' . $row->name . '" data-name="Category" class="btn btn-danger btn-sm delete-btn" title="Delete"><i class="fa-solid fa-trash"></i></button>';
                }

                if (empty($actions)) {
                    return '<span class="badge bg-secondary">No Access</span>';
                }

                return '<div class="btn-group gap-1">' . $actions . '</div>';
            })
            ->rawColumns(['index', 'image', 'parent_category', 'category_type', 'status', 'action'])
            ->setRowId('id');
    }

    public function query(Category $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->leftJoin('categories as parents', 'categories.parent_id', '=', 'parents.id')
            ->leftJoin('category_types', 'categories.category_type_id', '=', 'category_types.id')
            ->select([
                'categories.*',
                'parents.name as parent_name',
                'category_types.display_name as type_display_name'
            ]);

        if (request()->filled('status')) {
            $query->where('categories.is_active', request('status'));
        }
        if (request()->filled('type_id')) {
            $query->where('categories.category_type_id', request('type_id'));
        }
        if (request()->filled('source')) {
            $query->where('categories.source_from', request('source'));
        }

        return $query->orderBy('categories.name', 'asc');
    }

    protected function getAjaxParams(): array
    {
        return [
            'status'  => '$("#filter-status").val()',
            'type_id' => '$("#filter-type").val()',
            'source'  => '$("#filter-source").val()',
        ];
    }

    protected function getCustomButtons(): array
    {
        return [
            [
                'text' => '<i class="fa-solid fa-trash"></i><span class="bulk-count d-none">(0)</span>',
                'className' => 'btn btn-danger btn-bulk-delete me-1 disabled',
                'attr' => [
                    'id' => 'bulk-delete-btn',
                    'data-url' => route('categories.bulk-delete'),
                ],
                'init' => 'function(dt, node, config){ $(node).attr("title", "Bulk Delete").tooltip({placement:"top"}); }'
            ]
        ];
    }

    public function getColumns(): array
    {
        return [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('image')->title(__('file.table.image'))->orderable(false)->searchable(false),
            Column::make('name')->data('name')->title(__('file.table.name')),
            Column::make('parent_category')->data('parent_name')->name('parents.name')->title(__('file.table.parent_category')),
            Column::make('category_type')->data('type_display_name')->name('category_types.display_name')->title(__('file.table.category_type')),
            Column::make('source')->data('source_from')->name('categories.source_from')->title(__('file.table.source')),
            Column::make('status')->data('status')->name('categories.is_active')->title(__('file.table.status'))->addClass('text-center'),
            Column::make('action')->title(__('file.table.action'))->addClass('text-end')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'Category_' . date('YmdHis');
    }
}
