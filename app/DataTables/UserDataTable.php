<?php

namespace App\DataTables;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class UserDataTable extends BaseDataTable
{
    protected string $tableId = 'user-table';

    protected function getExportColumns(): array|string
    {
        return [1, 2, 3, 4, 5];
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<User> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->addColumn('action', function ($row) {
                $id = is_object($row->id) ? (string) $row->id : $row->id;

                if ($row->hasRole('Super Admin')) {
                    return '';
                }
                $deleteUrl = route('users.destroy', $id);

                $edit = '<a href="javascript:void(0)" onclick="editUser(\'' . $id . '\')" class="btn btn-primary me-1">
                    <i class="fa-solid fa-pen-to-square"></i>
                </a>';

                $delete = '<button type="button" data-url="' . $deleteUrl . '" data-table-id="#user-table" data-name="User" class="btn btn-danger delete-btn">
                <i class="fa-solid fa-trash"></i>
               </button>';

                return $edit . $delete;
            })
            ->editColumn('phone', function ($row) {
                return '<a href="tel:' . $row->phone . '">' . $row->phone . '</a>';
            })
            ->editColumn('created_at', function ($row) {
                return formatDate($row->created_at, true);
            })
            ->rawColumns(['index', 'action', 'phone'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<User>
     */
    public function query(User $model): QueryBuilder
    {
        return $model->newQuery()->select([
            'id',
            'name',
            'username',
            'email',
            'phone',
            'company_name',
            'created_at'
        ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('name')->title(__('file.table.name')),
            Column::make('username')->title(__('file.table.username')),
            Column::make('email')->title(__('file.table.email')),
            Column::make('phone')->title(__('file.table.phone_number'))->sortable(false)->addClass('text-start'),
            Column::make('company_name')->title(__('file.table.company_name')),
            Column::make('created_at')->title(__('file.table.created_at')),
            Column::computed('action')->title(__('file.table.action'))->exportable(false)->printable(false)->addClass('text-end')->responsivePriority(1),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'User_' . date('YmdHis');
    }
}
