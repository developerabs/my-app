<?php

namespace App\DataTables;

use App\Models\Tax;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class TaxDataTable extends BaseDataTable
{
    protected string $tableId = 'tax-table';

    protected function getExportColumns(): array|string
    {
        return [1, 2, 3];
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Tax> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->addColumn('rate', function ($row) {
                return $row->rate . '%';
            })
            ->editColumn('status', function ($row) {
                return $row->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
            })
            ->addColumn('action', function ($row) {
                return $this->actionDropdown([
                    $this->editAction(
                        onclick: "editTax('{$row->id}')",
                    ),
                    $this->deleteAction(
                        url: route('taxes.destroy', (string) $row->id),
                        tableId: '#tax-table',
                        item: $row->name,
                        name: 'Tax',
                    ),
                ]);
            })
            ->rawColumns(['index', 'status', 'action']);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Tax>
     */
    public function query(Tax $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('name')->title(__('file.table.name')),
            Column::make('rate')->title(__('file.table.rate'))->addClass('text-start')->orderable(false),
            Column::make('status')->title(__('file.table.status'))->orderable(false)->searchable(false),
            Column::computed('action')->title(__('file.table.action'))->exportable(false)->printable(false)->addClass('text-end')->responsivePriority(1),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Tax_' . date('YmdHis');
    }
}
