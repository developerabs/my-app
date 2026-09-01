<?php

namespace App\DataTables;

use App\Models\CustomerGroup;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class CustomerGroupDataTable extends BaseDataTable
{
    protected string $tableId = 'customergroup-table';
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<CustomerGroup> $query Results from query() method.
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
                        onclick: "editCustomerGroup('{$row->id}')",
                    ),
                    $this->deleteAction(
                        url: route('customer_groups.destroy', (string) $row->id),
                        tableId: '#customergroup-table',
                        item: $row->name,
                        name: 'Customer Group',
                    ),
                ]);
            })
            ->addColumn('is_default', function ($row) {
                // Determine if the checkbox should be checked
                $checked = $row->is_default ? 'checked' : '';

                return '<div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_default" value="1" 
                    id="edit_is_default_' . $row->id . '" ' . $checked . ' 
                    onclick="return false;" 
                    style="opacity: 1 !important; cursor: default;">
            </div>';
            })
            ->addColumn('status', function ($row) {
                return $row->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
            })
            ->rawColumns(['index', 'status', 'is_default', 'action']);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<CustomerGroup>
     */
    public function query(CustomerGroup $model): QueryBuilder
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
            Column::make('discount_type')->title(__('file.table.discount_type')),
            Column::make('discount_value')->title(__('file.table.discount_value'))->addClass('text-start')->orderable(false)->searchable(false),
            Column::make('min_order_amount')->title(__('file.table.min_order_amount'))->addClass('text-start')->orderable(false)->searchable(false),
            Column::make('is_default')->title(__('file.table.is_default'))->name('customer_groups.is_default')->addClass('text-center'),
            Column::make('status')->title(__('file.table.status'))->name('customer_groups.is_active')->addClass('text-center'),
            Column::computed('action')->title(__('file.table.action'))->exportable(false)->printable(false)->addClass('text-end')->responsivePriority(1),

        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'CustomerGroup_' . date('YmdHis');
    }
}
