<?php

namespace App\DataTables;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class UnitDataTable extends BaseDataTable
{
    protected string $tableId = 'unit-table';

    protected function getExportColumns(): array|string
    {
        return [2, 3, 4, 5, 6];
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Unit> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->editColumn('name', function ($row) {
                // English comment: Highlight if it is a base unit
                if ($row->is_base_unit) {
                    return '<strong>' . $row->name . '</strong> <span class="badge bg-success ms-1">Base</span>';
                }
                return $row->name;
            })
            ->addColumn('group', function ($row) {
                // English comment: Access the group name through the relationship
                return $row->group ? '<span class="badge bg-light text-dark border">' . $row->group->name . '</span>' : '<span class="text-muted">No Group</span>';
            })
            ->addColumn('parent_unit', function ($row) {
                // English comment: Show the immediate parent unit name
                return $row->baseUnit ? $row->baseUnit->name : '<span class="text-muted">N/A</span>';
            })
            ->editColumn('formula', function ($row) {
                if ($row->is_base_unit) return '-';

                if ($row->is_formulaic) {
                    return '<code class="text-primary">' . $row->formula . '</code>';
                }

                // English comment: Show simple operator logic (e.g., * 10)
                return '<span class="badge bg-info">' . ($row->operator ?? '') . ' ' . ($row->operator_value ?? '') . '</span>';
            })
            ->addColumn('display_hierarchy', function ($row) {
                // English comment: Access the custom attribute defined in the Model
                $hierarchy = $row->display_hierarchy_text;

                return $hierarchy
                    ? '<small class="text-primary fw-bold">' . $hierarchy . '</small>'
                    : '<span class="text-muted">-</span>';
            })
            ->addColumn('action', function ($row) {
                return $this->actionDropdown([
                    $this->editAction(
                        onclick: "editUnit('{$row->id}')",
                        permission: 'unit_update'
                    ),
                    $this->deleteAction(
                        url: route('units.destroy', (string) $row->id),
                        tableId: '#unit-table',
                        item: $row->name,
                        name: 'Unit',
                        permission: 'unit_delete'
                    ),
                ],
                [
                    'button_text' => '<i class="fa-solid fa-gear me-1"></i> ' . __('Actions'),
                ]);
            })
            ->setRowId('id')
            ->rawColumns(['index', 'name', 'parent_unit', 'group', 'formula', 'display_hierarchy', 'action']);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Unit>
     */
    public function query(Unit $model): QueryBuilder
    {
        return $model->newQuery()->with(['group', 'baseUnit'])->orderBy('name', 'asc');
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            $this->indexColumn()->titleAttr('Select'),

            Column::make('name')
                ->title(__('file.table.name')),

            Column::make('parent_unit')
                ->title('Base/Parent Unit') // English comment: Relationship column
                ->orderable(false),
            Column::make('group')->title(__('file.table.group')), // English comment: This will show group name
            Column::make('formula')
                ->title('Logic/Formula'),

            Column::make('display_hierarchy')
                ->title('Invoice Display') // English comment: Hierarchy chain
                ->orderable(false),

            Column::make('action')
                ->title(__('file.table.action'))
                ->addClass('text-end')
                ->orderable(false)
                ->searchable(false)
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Unit_' . date('YmdHis');
    }
}
