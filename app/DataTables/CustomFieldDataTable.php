<?php

namespace App\DataTables;

use App\Models\CustomField;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class CustomFieldDataTable extends BaseDataTable
{
    protected string $tableId = 'custom-field-table';

    protected function getExportColumns(): array|string
    {
        return [1, 2, 3, 4];
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<CustomField> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->editColumn('model_type', function ($row) {
                // এটি 'App\Models\Supplier' থেকে শুধু 'Supplier' রিটার্ন করবে
                return class_basename($row->model_type);
            })
            ->addColumn('action', function ($row) {
                return $this->actionDropdown([
                    $this->editAction(
                        onclick: "editCustomField('{$row->id}')",
                    ),
                    $this->deleteAction(
                        url: route('custom_fields.destroy', (string) $row->id),
                        tableId: '#custom-field-table',
                        item: $row->label,
                        name: 'Custom Field',
                    ),
                ]);
            })
            ->rawColumns(['index', 'action']);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<CustomField>
     */
    public function query(CustomField $model): QueryBuilder
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
            Column::make('label')->title(__('file.table.field_label')),
            Column::make('type')->title(__('file.table.model_type')),
            Column::make('options')->title(__('file.table.field_options')),
            Column::make('model_type')->title(__('file.table.field_type')),
            Column::computed('action')->title(__('file.table.action'))->exportable(false)->printable(false)->addClass('text-end')->responsivePriority(1),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'CustomField_' . date('YmdHis');
    }
}
