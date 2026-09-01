<?php

namespace App\DataTables;

use App\Models\Rack;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class RackDataTable extends BaseDataTable
{
    protected string $tableId = 'rack-table';

    protected function getExportColumns(): array|string
    {
        return [2, 3, 4, 5];
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Rack> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->editColumn('name', function ($row) {
                return '<a href="javascript:void(0)" onclick="viewRack(\'' . (string) $row->id . '\')" class="text-decoration-none fw-semibold text-dark text-hover-primary" title="' . e($row->name) . '">' . e($row->name) . '</a>';
            })
            ->editColumn('code', function ($row) {
                if (!$row->code) return '---';
                return '<a href="javascript:void(0)" onclick="viewRack(\'' . (string) $row->id . '\')" class="text-decoration-none text-muted text-hover-primary" title="' . e($row->code) . '">' . e($row->code) . '</a>';
            })
            ->editColumn('description', function ($row) {
                return $row->description ? '<span class="d-inline-block text-truncate" style="max-width: 200px;" title="' . e($row->description) . '">' . e($row->description) . '</span>' : '---';
            })
            ->addColumn('action', function ($row) {
                return $this->actionDropdown([
                    $this->editAction(
                        onclick: "editRack('{$row->id}')",
                        permission: 'rack_update'
                    ),
                    $this->deleteAction(
                        url: route('racks.destroy', (string) $row->id),
                        tableId: '#rack-table',
                        item: $row->name,
                        name: 'Rack',
                        permission: 'rack_delete'
                    ),
                ]);
            })
            ->setRowId('id')
            ->rawColumns(['index', 'name', 'code', 'description', 'action']);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Rack>
     */
    public function query(Rack $model): QueryBuilder
    {
        return $model->newQuery()->with('branch');
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('name')->data('name')->title(__('file.table.name'))->responsivePriority(2),
            Column::make('code')->data('code')->title(__('file.table.code'))->responsivePriority(2),
            Column::make('branch_name')->data('branch.name')->title(__('file.table.branch'))->responsivePriority(3),
            Column::make('description')->data('description')->title(__('file.table.description')),
            Column::make('action')->title(__('file.table.action'))->addClass('text-end')->orderable(false)->searchable(false)->exportable(false)->printable(false)->responsivePriority(3),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Rack_' . date('YmdHis');
    }
}
