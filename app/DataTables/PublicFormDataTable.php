<?php

namespace App\DataTables;

use App\Models\PublicForm;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class PublicFormDataTable extends BaseDataTable
{
    protected string $tableId = 'public-form-table';

    protected function getExportColumns(): array|string
    {
        return [1, 2, 3, 5, 6];
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->editColumn('title', function ($row) {
                return '<div class="fw-semibold"><a href="' . route('public-forms.edit', $row->id) . '">' . e(substr($row->title, 0, 30)) . (strlen($row->title) > 30 ? '...' : '') . '</a></div><small class="text-muted">/' . e(substr($row->slug, 0, 30)) . (strlen($row->slug) > 30 ? '...' : '') . '</small>';
            })
            ->addColumn('total_submissions', function ($row) {
                return '<span class="badge bg-warning-subtle text-secondary fw-bold"><a target="_blank" href="' . route('public-forms-responses.index', $row->id) . '">' . ($row->total_submissions) . '</a></span>';
            })
            ->addColumn('link', function ($row) {
                $url = null;
                if ($row->activeToken && $row->activeToken->token_encrypted) {
                    try {
                        $url = route('public-forms.show', [$row->slug, decrypt($row->activeToken->token_encrypted)]);
                    } catch (DecryptException $e) {
                        $url = null;
                    }
                }

                if (!$url) {
                    return '<span class="text-muted small">' . __('file.no_active_link') . '</span>';
                }

                return '
                    <div class="input-group input-group-sm" style="max-width: 220px;">
                        <input type="text" class="form-control form-control-sm" value="' . e($url) . '" readonly>
                        <button type="button" class="btn btn-outline-secondary copy-link" data-link="' . e($url) . '" title="Copy link">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                    </div>
                ';
            })
            ->editColumn('submission_mode', function ($row) {
                $label = $row->submission_mode === 'response_only' ? 'Response Only' : 'Auto Lead';
                $class = $row->submission_mode === 'response_only' ? 'bg-info-subtle text-info' : 'bg-primary-subtle text-primary';

                return '<span class="badge ' . $class . '">' . $label . '</span>';
            })
            ->editColumn('is_active', function ($row) {
                $class = $row->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary';
                $label = $row->is_active ? 'Active' : 'Inactive';

                return '<span class="badge ' . $class . '">' . $label . '</span>';
            })
            ->editColumn('created_at', function ($row) {
                return formatDate($row->created_at, true);
            })
            ->editColumn('expired_at', function ($row) {
                return formatDate($row->activeToken->expires_at ?? null, true);
            })
            //  ->addColumn('action', function ($row) {
            //     $user = Auth::user();
            //     $generateDisabled = $row->is_active ? '' : ' disabled';
            //     $generateTitle = $row->is_active ? 'Generate secure link' : 'Activate the form first';
            //     $actions = '';
            //     $actions .= '<button type="button" class="btn btn-outline-primary generate-link" data-url="' . e(route('public-forms.tokens.store', $row)) . '" title="' . e($generateTitle) . '"' . $generateDisabled . '>
            //                 <i class="fa-solid fa-link"></i>
            //             </button>';
            //     $actions .= '<a href="' . e(route('public-forms.edit', $row)) . '" class="btn btn-outline-secondary" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>';
            //     $actions .= '<button type="button" class="btn btn-outline-warning toggle-form" data-url="' . e(route('public-forms.toggle', $row)) . '" title="Change status">
            //                 <i class="fa-solid fa-power-off"></i>
            //             </button>';
                
            //     $url = route('public-forms.destroy', (string) $row->id);
            //     $actions .= '<button type="button" data-url="' . $url . '" data-table-id="#public-form-table" data-item="' . $row->name . '" data-name="Public Form" class="btn btn-danger btn-sm delete-btn" title="Delete"><i class="fa-solid fa-trash"></i></button>';
                
            //     if (empty($actions)) {
            //         return '<span class="badge bg-secondary">No Access</span>';
            //     }

            //     return '<div class="btn-group gap-1">' . $actions . '</div>';
            // })
             ->addColumn('action', function ($row) {
                $actions = [];

                $actions[] = $this->linkAction(
                    href: route('public-forms-responses.index', $row->id),
                    label: __('file.table.submissions'),
                    icon: 'fa-solid fa-list',
                    target: '_blank',
                );
                $actions[] = $this->divider();

                $actions[] = $this->editAction(
                    onclick: "toggleForm('{$row->id}')",
                    label: __('file.table.toggle'),
                    icon: 'fa-solid fa-power-off text-warning',
                );
                $actions[] = $this->divider();

                $actions[] = $this->editAction(
                    onclick: "generateLink('{$row->id}')",
                    label: __('file.table.generate_link'),
                    icon: 'fa-solid fa-link',
                );
                $actions[] = $this->divider();

                $actions[] = $this->linkAction(
                    href: route('public-forms.edit', $row->id),
                    label: __('file.table.edit'),
                    icon: 'fa-solid fa-pen-to-square text-success',
                );
                $actions[] = $this->divider();

                $actions[] = $this->deleteAction(
                    url: route('public-forms.destroy', $row->id),
                    tableId: '#public-form-table',
                    item: $row->title,
                    name: 'Public Form',
                );

                return $this->actionDropdown($actions);
            })
            ->rawColumns(['index', 'title', 'total_submissions', 'submission_mode', 'is_active', 'link', 'action'])
            ->setRowId('id');
    }

    public function query(PublicForm $model): QueryBuilder
    {
        return $model->newQuery()
            ->withCount('formResponses as total_submissions')
            ->with('activeToken')
            ->latest();
    }

    protected function getCustomButtons(): array
    {
        return [
            [
                'text' => '<i class="fa-solid fa-trash"></i><span class="bulk-count d-none">(0)</span>',
                'className' => 'btn btn-danger btn-bulk-delete me-1 disabled',
                'attr' => [
                    'id' => 'bulk-delete-btn',
                    'data-url' => route('public-forms.bulk-delete'),
                ],
                'init' => 'function(dt, node, config){ $(node).attr("title", "Bulk Delete").tooltip({placement:"top"}); }'
            ]
        ];
    }

    public function getColumns(): array
    {
        return [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('title')->title('Form'),
            Column::make('submission_mode')->title('Mode'),
            Column::computed('total_submissions')->title('Submissions')->searchable(false),
            Column::computed('link')->title('Link')->searchable(false)->orderable(false)->exportable(false)->printable(false),
            Column::make('is_active')->title('Status'),
            Column::make('created_at')->title('Created At'),
            Column::computed('expired_at')->title('Expired At')->searchable(false),
            Column::computed('action')->title(__('file.table.action'))->exportable(false)->printable(false)->addClass('text-end')->responsivePriority(1),
        ];
    }

    protected function filename(): string
    {
        return 'Public_Forms_' . date('YmdHis');
    }
}
