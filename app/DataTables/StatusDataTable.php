<?php

namespace App\DataTables;

use App\DataTables\BaseDataTable;
use App\Models\Status;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;

class StatusDataTable extends BaseDataTable
{
    protected string $tableId = 'statuses-table';

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
            ->editColumn('name', function ($row) {
                return $row->name ?? '<span class="text-muted">--</span>';
            })
            ->editColumn('type', function ($row) {
                return $row->type ?? '<span class="text-muted">--</span>';
            })
            ->editColumn('category_id', function ($row) {
                return $row->category?->name ?? '<span class="text-muted">--</span>';
            })
            ->editColumn('progress', function ($row) {
                if ($row->progress) {
                    return '<div class="progress" style="height: 30px;"><div class="progress-bar" role="progressbar" style="width: ' . $row->progress . '%; background-color: ' . ($row->color ?? '#0d6efd') . ';" aria-valuenow="' . $row->progress . '" aria-valuemin="0" aria-valuemax="100">' . $row->progress . '%</div></div>';
                }
                return '<span class="text-muted">--</span>';
            })
            ->editColumn('sort_order', function ($row) {
                return $row->sort_order ?? '<span class="text-muted">--</span>';
            })
            ->editColumn('is_active', function ($row) {
                return $row->is_active
                    ? '<span class="badge bg-success">' . __('file.table.active') . '</span>'
                    : '<span class="badge bg-danger">' . __('file.table.inactive') . '</span>';
            })
            ->addColumn('action', function ($row) {
                $user = Auth::user();
                $actions = '';
                $actions .= '<a href="javascript:void(0)" onclick="editStatus(\'' . (string) $row->id . '\')" class="btn btn-primary btn-sm" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>';
                
                $url = route('statuses.destroy', (string) $row->id);
                $actions .= '<button type="button" data-url="' . $url . '" data-table-id="#status-table" data-item="' . $row->name . '" data-name="Status" class="btn btn-danger btn-sm delete-btn" title="Delete"><i class="fa-solid fa-trash"></i></button>';
                
                if (empty($actions)) {
                    return '<span class="badge bg-secondary">No Access</span>';
                }

                return '<div class="btn-group gap-1">' . $actions . '</div>';
            })
            ->rawColumns(['index', 'category_id', 'is_active', 'progress', 'action'])
            ->setRowId('id');
    }

    public function query(Status $model): QueryBuilder
    {
        $query = $model->newQuery()->with('category');

        if (request()->filled('status')) {
            $query->where('is_active', request('status'));
        }

        return $query->latest();
    }

    protected function getAjaxParams(): array
    {
        return [
            'status'  => '$("#filter-status").val()',
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
                    'data-url' => route('statuses.bulk-delete'),
                ],
                'init' => 'function(dt, node, config){ $(node).attr("title", "Bulk Delete").tooltip({placement:"top"}); }'
            ]
        ];
    }

    public function getColumns(): array
    {
        return [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('name')->data('name')->title(__('file.table.name')),
            Column::make('type')->data('type')->title(__('file.field.type'))->addClass('text-capitalize'),
            Column::make('category_id')->data('category_id')->title(__('file.field.category')),
            Column::make('progress')->data('progress')->title(__('file.field.progress')),
            Column::make('sort_order')->data('sort_order')->title(__('file.field.order')),
            Column::make('is_active')->data('is_active')->name('statuses.is_active')->title(__('file.table.status')),
            Column::make('action')->title(__('file.table.action'))->addClass('text-end')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'Status_' . date('YmdHis');
    }
}
