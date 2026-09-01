<?php

namespace App\DataTables;

use App\Models\UnitGroup;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class UnitGroupDataTable extends BaseDataTable
{
    protected string $tableId = 'unit-group-table';

    protected function getExportColumns(): array|string
    {
        return [2, 3];
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<UnitGroup> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->addColumn('action', function ($row) {
                return $this->actionDropdown([
                    $this->editAction(
                        onclick: "editUnitGroup('{$row->id}')",
                        permission: 'unit_group_update'
                    ),
                    $this->deleteAction(
                        url: route('unit-groups.destroy', (string) $row->id),
                        tableId: '#unit-group-table',
                        item: $row->name,
                        name: 'Unit Group',
                        permission: 'unit_group_delete'
                    ),
                ],
                [
                    'button_text' => '<i class="fa-solid fa-gear me-1"></i> ' . __('Actions'),
                ]);
            })
            ->setRowId('id')
            ->rawColumns(['index', 'action']);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<UnitGroup>
     */
    public function query(UnitGroup $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('name', 'asc');
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('name')->title(__('file.table.name')),
            Column::make('description')->title(__('file.table.description')),
            Column::make('action')->title(__('file.table.action'))->addClass('text-end')->orderable(false)->searchable(false)
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'UnitGroup_' . date('YmdHis');
    }
}
