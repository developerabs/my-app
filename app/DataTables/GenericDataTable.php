<?php

namespace App\DataTables;

use App\Models\Generic;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class GenericDataTable extends BaseDataTable
{
    protected string $tableId = 'generic-table';

    protected function getExportColumns(): array|string
    {
        return [2, 3, 4, 5];
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Generic> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->editColumn('description', function ($row) {
                return $row->description ? '<span class="d-inline-block text-truncate" style="max-width: 200px;" title="' . e($row->description) . '">' . e($row->description) . '</span>' : '---';
            })
            ->editColumn('status', function ($row) {
                return $row->is_active
                    ? '<span class="badge bg-success">' . __('file.table.active') . '</span>'
                    : '<span class="badge bg-danger">' . __('file.table.inactive') . '</span>';
            })
            ->addColumn('action', function ($row) {
                return $this->actionDropdown([
                    $this->editAction(
                        onclick: "editGeneric('{$row->id}')",
                    ),
                    $this->divider(),
                    $this->deleteAction(
                        url: route('generics.destroy', (string) $row->id),
                        tableId: '#generic-table',
                        item: $row->name,
                        name: 'Generic',
                    )
                ]);
            })
            ->setRowId('id')
            ->rawColumns(['index', 'description', 'status', 'action']);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Generic>
     */
    public function query(Generic $model): QueryBuilder
    {
        return $model->newQuery();
    }

    protected function getCustomButtons(): array
    {
        return [
            [
                'text' => '<i class="fa-solid fa-trash"></i><span class="bulk-count d-none">(0)</span>',
                'className' => 'btn btn-danger btn-bulk-delete me-2 disabled',
                'attr' => [
                    'id' => 'bulk-delete-btn',
                    'data-url' => route('generics.bulk-delete'),
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
        $columns = [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('name')->data('name')->title(__('file.table.name'))->responsivePriority(2),
            Column::make('description')->data('description')->title(__('file.table.description')),
            Column::make('status')->data('status')->name('generics.is_active')->title(__('file.table.status')),
            Column::make('source')->data('source_from')->name('generics.source_from')->title(__('file.table.source')),
        ];

        return array_merge($columns,[
            ...$this->auditColumns(),
            Column::make('action')->title(__('file.table.action'))->addClass('text-end')->orderable(false)->searchable(false)->exportable(false)->printable(false)->responsivePriority(3),
        ]);
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Generic_' . date('YmdHis');
    }
}
