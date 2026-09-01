<?php

namespace App\DataTables;

use App\Models\Trash;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class TrashDataTable extends BaseDataTable
{
    protected string $tableId = 'trash-table';

    protected function getExportColumns(): array|string
    {
        return [1, 2, 3, 4];
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Trash> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->addColumn('action', function ($row) {
                return '
                    <div class="btn-group gap-2">
                        <button class="btn btn-sm btn-success restore-item" title="Restore" data-id="' . $row->id . '"><i class="fa-solid fa-rotate-left"></i></button>
                        <button class="btn btn-sm btn-danger delete-permanent" title="Delete Permanent" data-id="' . $row->id . '"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                ';
            })
            ->editColumn('trashable_type', function ($row) {
                // Model path থেকে শুধু নামটা দেখাবে (e.g., Category)
                return class_basename($row->trashable_type);
            })
            ->editColumn('deleted_by', function ($row) {
                return $row->deleter?->name ?? 'System';
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at->format('d M, Y h:i A');
            })
            ->rawColumns(['index', 'action'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Trash>
     */
    public function query(Trash $model): QueryBuilder
    {
        return $model->newQuery()->with(['trashable', 'deleter']);
    }
    
    /**
     * English: Keep only Print and PDF
     */
    protected function disabledButtons(): array
    {
        return ['excel', 'csv'];
    }

    /**
     * English: Remove Clear Filter and Refresh buttons
     */
    protected function showUtilityButtons(): bool
    {
        return false;
    }

    protected function getCustomButtons(): array
    {
        return [
            // Bulk Restore Button
            [
                'text' => '<i class="fa-solid fa-rotate-left"></i> <span class="bulk-restore-count d-none">(0)</span>',
                'className' => 'btn btn-success bulk-restore-btn me-1 disabled',
                'init' => 'function(dt, node, config){
                            $(node).attr("title", "Bulk Restore").tooltip({placement:"top"});
                        }'
            ],
            // Bulk Delete Button
            [
                'text' => '<i class="fa-solid fa-trash-can"></i> <span class="bulk-delete-count d-none">(0)</span>',
                'className' => 'btn btn-danger bulk-delete-btn me-1 disabled',
                'init' => 'function(dt, node, config){
                            $(node).attr("title", "Bulk Permanent Delete").tooltip({placement:"top"});
                        }'
            ],
        ];
    }
    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('name')->title('Item Name'),
            Column::make('trashable_type')->title('Module'),
            Column::make('deleted_by')->title('Deleted By'),
            Column::make('created_at')->title('Deleted At'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(200)
                ->addClass('text-end no-export'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Trash_' . date('YmdHis');
    }
}
