<?php

namespace App\DataTables;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class BranchDataTable extends BaseDataTable
{
    protected string $tableId = 'branch-table';


    protected function getExportColumns(): array|string
    {
        return [ 2, 3, 4];
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Branch> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->addColumn('logo', function ($row) {
                $image = $row->branch_logo_url ?? url('images/preview_image.png');
                return '<img style="height: 50px;" src="' . $row->branch_logo_url . '" class="img-fluid" alt="' . $row->name . '" />';
            })
            ->addColumn('name', function ($row) {
                $binInfo = $row->bin_number ? "<div class='text-muted'>BIN: {$row->bin_number}</div>" : "";

                return "<div>
                            <strong>{$row->name}</strong>
                            {$binInfo}
                        </div>";
            })
            ->addColumn('contact', function ($row) {
                // PHP-তে ডাবল কোটেশন ব্যবহার করতে হবে
                return "<div>
                        <strong>{$row->phone}</strong>
                        <div class='text-muted'>{$row->email}</div>
                    </div>";
            })
            ->addColumn('status', function ($row) {
                return $row->is_active ? "<span class='badge bg-success'>Active</span>" : "<span class='badge bg-danger'>Inactive</span>";
            })
            ->addColumn('action', function ($row) {
               return $this->actionDropdown([
                   $this->editAction(
                       onclick: "editBranch('{$row->id}')",
                       permission: 'branch_update'
                   ),
                   $this->divider(),
                   $this->deleteAction(
                       url: route('branches.destroy', $row->id),
                       tableId: '#branch-table',
                       item: $row->name,
                       name: 'Branch',
                       permission: 'branch_delete'

                   )
               ]);
            })
            ->rawColumns(['index', 'logo', 'name', 'contact', 'status', 'action'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Branch>
     */
    public function query(Branch $model): QueryBuilder
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
            Column::make('logo')->title(__('file.table.logo'))->orderable(false)->searchable(false),
            Column::make('name')->title(__('file.table.name'))->orderable(false)->searchable(false),
            Column::make('contact')->title(__('file.table.contacts'))->orderable(false)->searchable(false),
            Column::make('status')->title(__('file.table.status'))->orderable(false)->searchable(false),
            Column::make('action')->title(__('file.table.action'))->orderable(false)->searchable(false)->exportable(false)->printable(false)->addClass('text-end')->responsivePriority(1),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Branch_' . date('YmdHis');
    }
}
