<?php

namespace App\DataTables;

use App\Models\LeadSubject;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;

class LeadSubjectDataTable extends BaseDataTable
{
    protected string $tableId = 'lead-subject-table';

    /**
     * English: Override export columns for Category
     */
    protected function getExportColumns(): array|string
    {
        return [2, 3, 4, 5, 6];
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->editColumn('name', function ($row) {
                return $row->name ?? '<span class="text-muted">--</span>';
            })
            ->editColumn('sort_order', function ($row) {
                return $row->sort_order ?? '<span class="text-muted">--</span>';
            })
            ->editColumn('is_active', function ($row) {
                return $row->is_active
                    ? '<span class="badge bg-success">' . __('file.table.active') . '</span>'
                    : '<span class="badge bg-danger">' . __('file.table.inactive') . '</span>';
            })
            ->addColumn('action', function ($row) {
                $user = Auth::user();
                $actions = '';
                $actions .= '<a href="javascript:void(0)" onclick="editLeadSubject(\'' . (string) $row->id . '\')" class="btn btn-primary btn-sm" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>';

                $url = route('lead-subjects.destroy', (string) $row->id);
                $actions .= '<button type="button" data-url="' . $url . '" data-table-id="#lead-subject-table" data-item="' . $row->name . '" data-name="Lead Subject" class="btn btn-danger btn-sm delete-btn" title="Delete"><i class="fa-solid fa-trash"></i></button>';

                if (empty($actions)) {
                    return '<span class="badge bg-secondary">No Access</span>';
                }

                return '<div class="btn-group gap-1">' . $actions . '</div>';
            })
            ->rawColumns(['index', 'is_active', 'action'])
            ->setRowId('id');
    }

    public function query(LeadSubject $model): QueryBuilder
    {
        $query = $model->newQuery();

        if (request()->filled('status')) {
            $query->where('lead_subjects.is_active', request('status'));
        }

        return $query->latest();
    }

    protected function getAjaxParams(): array
    {
        return [
            'status'  => '$("#filter-status").val()',
        ];
    }

    protected function getCustomButtons(): array
    {
        return [
            [
                'text' => '<i class="fa-solid fa-trash"></i><span class="bulk-count d-none">(0)</span>',
                'className' => 'btn btn-danger btn-bulk-delete me-1 disabled',
                'attr' => [
                    'id' => 'bulk-delete-btn',
                    'data-url' => route('lead-subjects.bulk-delete'),
                ],
                'init' => 'function(dt, node, config){ $(node).attr("title", "Bulk Delete").tooltip({placement:"top"}); }'
            ]
        ];
    }

    public function getColumns(): array
    {
        return [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('name')->data('name')->title(__('file.table.name')),
            Column::make('sort_order')->data('sort_order')->title(__('file.field.order')),
            Column::make('is_active')->data('is_active')->name('lead_subjects.is_active')->title(__('file.table.status')),
            Column::make('action')->title(__('file.table.action'))->addClass('text-end')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'Lead_Subject_' . date('YmdHis');
    }
}
