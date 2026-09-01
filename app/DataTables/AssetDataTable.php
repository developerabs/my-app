<?php

namespace App\DataTables;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class AssetDataTable extends BaseDataTable
{
    protected string $tableId = 'asset-table';

    protected function getExportColumns(): array|string
    {
        return [2, 3, 4, 5];
    }

    /**
     * Build the DataTable class.
     *
     * @param  QueryBuilder<Asset>  $query  Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $dataTable = (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="'.$row->id.'" />';
            })
            // এখানে মডেলের অ্যাক্সেসর থেকে মানগুলো কল করা হয়েছে
            ->editColumn('total_quantity', function ($row) {
                return $row->total_quantity ?? 0; 
            })
            ->editColumn('total_cost', function ($row) {
                return number_format($row->total_cost ?? 0, 2); 
            })
            ->editColumn('is_active', function ($row){
                return $row->is_active
                    ? '<span class="badge bg-success">' . __('file.table.active') . '</span>'
                    : '<span class="badge bg-danger">' . __('file.table.inactive') . '</span>';
            })
            ->addColumn('action', function ($row) {
                return $this->actionDropdown([
                    $this->editAction(
                        onclick: "editAsset('{$row->id}')",
                        permission: 'assets_update',
                    ),
                    $this->divider(),
                    $this->deleteAction(
                        url: route('assets.destroy', $row->id),
                        tableId: '#asset-table',
                        item: $row->asset_name,
                        name: 'Asset',
                        permission: 'assets_delete',
                    ),
                ],
                [
                    'button_text' => '<i class="fa-solid fa-gear me-1"></i> '.__('Actions'),
                ]);
            });

        return $this->applyAucitColumnLogic($dataTable->rawColumns(['index', 'is_active', 'action']));
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Asset>
     */
    public function query(Asset $model): QueryBuilder
    {
        // Eager load relations so accessor can calculate without N+1 query issue
        return $model->newQuery()->with('registerItems.register');
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        $columns = [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('asset_name')->title(__('file.table.name')),
            Column::make('total_quantity')->title(__('file.table.quantity'))->addClass('text-start')->orderable(false),
            Column::make('total_cost')->title('<div class="text-end">' . __('file.table.total_value') . '</div>')->orderable(false),
            Column::make('is_active')->title('<div class="text-end">' . __('file.table.status') . '</div>')->addClass('text-end'),
        ];

        $columns = array_merge(
            $columns,
            $this->auditColumns(),
            [
                Column::make('action')->title(__('file.table.action'))->orderable(false)->searchable(false)->exportable(false)->printable(false)->addClass('text-end')->responsivePriority(1),
            ]
        );

        return $columns;
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Asset_'.date('YmdHis');
    }
}