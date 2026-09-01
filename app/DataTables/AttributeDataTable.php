<?php

namespace App\DataTables;

use App\Models\Attribute;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class AttributeDataTable extends BaseDataTable
{
    protected string $tableId = 'attribute-table';
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Attribute> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->editColumn('name', function ($row) {
                $name = '<div class="fw-bold text-dark mb-0" title="' . e($row->name) . '">' . str($row->name)->limit(45) . '</div>';
                if ($row->description) {
                    $name .= '<div class="text-muted" style="font-size: 10px;">' . e($row->description) . '</div>';
                }
                return $name;
            })
            ->addColumn('values', function ($row) {
                return $row->values->pluck('value')->implode(', ');
            })
            ->editColumn('is_active', function ($row) {
                return $row->is_active ? "<span class='badge bg-success'>Active</span>" : "<span class='badge bg-danger'>Inactive</span>";
            })
            ->addColumn('action', function ($row) {
                $deleteUrl = route('attributes.destroy', (string) $row->id);
                $edit = '<a href="javascript:void(0)" onclick="editAttribute(\'' . (string) $row->id . '\')" class="btn  btn-primary"><i class="fa-solid fa-pen-to-square"></i></a>';
                $delete = '<button type="button" data-url="' . $deleteUrl . '" data-table-id="#attribute-table" data-name="Attribute" class="btn btn-danger delete-btn"><i class="fa-solid fa-trash"></i></button>';

                return $edit . ' ' . $delete;
            })
            ->rawColumns(['index', 'name', 'action', 'values', 'is_active'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Attribute>
     */
    public function query(Attribute $model): QueryBuilder
    {
        return $model->newQuery()->with('values');
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        $columns = [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('name')->title(__('file.table.name')),
            Column::make('values')->title(__('file.table.values'))->orderable(false)->searchable(false),
            Column::make('is_active')->title(__('file.table.status'))->orderable(false)->searchable(false),
            ...$this->auditColumns(), 
            Column::make('action')->title(__('file.table.action'))->orderable(false)->searchable(false)->exportable(false)->printable(false)->addClass('text-end')->responsivePriority(1),
        ];

        return $columns;
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Attribute_' . date('YmdHis');
    }
}
