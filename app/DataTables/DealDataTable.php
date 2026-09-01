<?php

namespace App\DataTables;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class DealDataTable extends BaseDataTable
{
    protected string $tableId = 'lead-table';

    /**
     * Override export columns for Category
     */
    protected function getExportColumns(): array|string
    {
        return [2, 3, 4, 5, 6];
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $dataTable = (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->addColumn('customer-info', function ($row) {
                $leadsDetails = route('leads.show', $row->id);
                $phone = $row->effective_phone ? $row->effective_phone : $row->phone;

                return '<strong><a href="'.$leadsDetails.'" target="_blank">' . e($row->name) . '</a></strong></br>'.
                    '<small class="">' . ($row->company_name ? e($row->company_name) . '</br>' : '') . '</small>'.
                    '<small class="copy-trigger">' . ($row->email ? e($row->email) : '') . '</small>'.
                    ($row->email ? ' <span class="copy-trigger text-primary" data-copy="' . e($row->email) . '"><i class="fa-solid fa-copy"></i></span></br>' : '') .
                    '<small class="copy-trigger">' . e($phone) . '</small>' .
                    ($phone ? ' <span class="copy-trigger text-primary" data-copy="' . e($phone) . '"><i class="fa-solid fa-copy"></i></span></br>' : '');
            })
            ->addColumn('last-note', function ($row) {
                // MorphOne latestNote Eager Loaded
                $lastNote = $row->latestNote?->note;
                $displayNote = $lastNote ? e(substr($lastNote, 0, 30)) . (strlen($lastNote) > 30 ? '...' : '') : '';

                return '<strong class="text-primary" onclick="showLeadNotes(\'' . (string) $row->id . '\')" style="cursor: pointer;">' . $displayNote . '</strong></br>'.
                    '<small class="">Sub: ' . e($row->leadSubject->name ?? 'N/A') . '</small></br>'.
                    '<small class="">Source: ' . e($row->leadSource->name ?? 'N/A') . '</small></br>'.
                    '<small class="">Category: ' . e($row->category->name ?? 'N/A') . '</small>';
            })
            ->addColumn('managed-by', function ($row) {
                return '<small class="">Lead Manager: ' . e($row->manager?->name ?? 'N/A') . '</small></br>'.
                    '<small class="">Assigned To: ' . e($row->assignedTo?->name ?? 'N/A') . '</small></br>'.
                    '<small class="">Last Updated By: ' . e($row->updatedBy?->name ?? 'N/A') . '</small></br>'.
                    '<small class="text-capitalize">Priority: ' . e($row->priority ?? 'N/A') . '</small>';
            })
            ->addColumn('follow-up-date', function ($row) {
                // MorphOne latestMeeting Eager Loaded
                $lastMeetingDate = $row->latestMeeting?->start_at;

                return '<small class="">Follow-up Date: ' . (formatDate($row->follow_up_date, true) ?? 'N/A') . '</small></br>'.
                    '<small class="">Last Updated: ' . (formatDate($row->updated_at, true) ?? 'N/A') . '</small></br>'.
                    '<small class="">Last Meeting: ' . (formatDate($lastMeetingDate, true) ?? 'N/A') . '</small></br>'.
                    '<small class="">Created: ' . (formatDate($row->created_at, true) ?? 'N/A') . '</small>';
            })
            ->addColumn('progress', function ($row) {
                if ($row->is_failed) {
                    return '<span class="badge bg-danger p-1">' . __('file.label.failed') . '</span>';
                }

                $progress = $row->leadStatus->progress ?? 0;
                $color = $row->leadStatus->color ?? '#000000';
                $statusName = $row->leadStatus->name ?? 'Unknown';

                return '<div class="progress" style="height: 20px;">
                            <div class="progress-bar" role="progressbar" style="width: ' . $progress . '%; background-color: ' . $color . ';" aria-valuenow="' . $progress . '" aria-valuemin="0" aria-valuemax="100">' . $progress . '%</div>
                        </div>
                        <small class="text-muted">' . e($statusName) . '</small>';
            })
            ->filterColumn('customer-info', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'ilike', "%{$keyword}%")
                        ->orWhere('company_name', 'ilike', "%{$keyword}%")
                        ->orWhere('email', 'ilike', "%{$keyword}%")
                        ->orWhere('phone', 'ilike', "%{$keyword}%")
                        ->orWhere('effective_phone', 'ilike', "%{$keyword}%");
                });
            })
            ->filterColumn('last-note', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('notes', function ($noteQuery) use ($keyword) {
                            $noteQuery->where('note', 'ilike', "%{$keyword}%");
                        })
                        ->orWhereHas('leadSubject', function ($subjectQuery) use ($keyword) {
                            $subjectQuery->where('name', 'ilike', "%{$keyword}%");
                        })
                        ->orWhereHas('leadSource', function ($sourceQuery) use ($keyword) {
                            $sourceQuery->where('name', 'ilike', "%{$keyword}%");
                        })
                        ->orWhereHas('category', function ($categoryQuery) use ($keyword) {
                            $categoryQuery->where('name', 'ilike', "%{$keyword}%");
                        });
                });
            })
            ->filterColumn('managed-by', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->whereHas('createdBy', function ($userQuery) use ($keyword) {
                            $userQuery->where('name', 'ilike', "%{$keyword}%");
                        })
                        ->orWhereHas('updatedBy', function ($userQuery) use ($keyword) {
                            $userQuery->where('name', 'ilike', "%{$keyword}%");
                        })
                        ->orWhereHas('manager', function ($userQuery) use ($keyword) {
                            $userQuery->where('name', 'ilike', "%{$keyword}%");
                        })
                        ->orWhereHas('assignedTo', function ($userQuery) use ($keyword) {
                            $userQuery->where('name', 'ilike', "%{$keyword}%");
                        });
                });
            })
            ->addColumn('action', function ($row) {
                $actions = [];
                $isNotFailed = !$row->is_failed;

                if ($isNotFailed) {
                    $actions[] = $this->editAction(
                        onclick: "addNote('{$row->id}','{$row->category_id}','{$row->type}')",
                        label: __('file.table.add_note'),
                        icon: 'fa-solid fa-plus',
                        permission: 'crm_leads_notes_create',
                    );
                    $actions[] = $this->divider();
                }

                $actions[] = $this->editAction(
                    onclick: "showLeadNotes('{$row->id}')",
                    label: __('file.table.view_notes'),
                    icon: 'fa-solid fa-sticky-note text-info',
                    permission: 'crm_leads_notes_view',
                );
                $actions[] = $this->divider();

                $actions[] = $this->linkAction(
                    href: route('leads.show', $row->id),
                    label: __('file.table.view_details'),
                    icon: 'fa-solid fa-eye text-secondary',
                    permission: 'crm_leads_view',
                );
                $actions[] = $this->divider();

                if ($isNotFailed) {
                    $actions[] = $this->editAction(
                        onclick: "convertToDeal('{$row->id}')",
                        label: __('file.table.convert_to_deal'),
                        icon: 'fa-solid fa-handshake text-primary',
                        permission: 'crm_leads_update',
                    );
                    $actions[] = $this->divider();
                }

                if (empty($row->customer_id) && $isNotFailed) {
                    $actions[] = $this->linkAction(
                        href: route('leads.convert', $row->id),
                        label: __('file.table.convert_to_customer'),
                        icon: 'fa-solid fa-user-plus text-success',
                        permission: 'crm_leads_update',
                    );
                    $actions[] = $this->divider();
                }

                if ($isNotFailed) {
                    $actions[] = $this->editAction(
                        onclick: "markAsFailed('{$row->id}','{$row->category_id}','{$row->type}')",
                        label: __('file.table.mark_as_failed'),
                        icon: 'fa-solid fa-times text-danger',
                        permission: 'crm_leads_update',
                    );
                    $actions[] = $this->divider();
                } else {
                    $actions[] = $this->editAction(
                        onclick: "removeFromFailed('{$row->id}','{$row->category_id}','{$row->type}')",
                        label: __('file.table.remove_from_failed'),
                        icon: 'fa-solid fa-undo text-warning',
                        permission: 'crm_leads_update',
                    );
                    $actions[] = $this->divider();
                }

                if ($isNotFailed) {
                    $actions[] = $this->editAction(
                        onclick: "editLead('{$row->id}')",
                        permission: 'crm_leads_update',
                    );
                    $actions[] = $this->divider();
                }

                $actions[] = $this->deleteAction(
                    url: route('leads.destroy', $row->id),
                    tableId: '#deal-table',
                    item: $row->name,
                    name: 'Deal',
                    permission: 'crm_leads_delete',
                );

                return $this->actionDropdown($actions);
            })
            ->rawColumns(['index', 'customer-info', 'last-note', 'managed-by', 'follow-up-date', 'progress', 'action'])
            ->setRowId('id');

        return $dataTable;
    }

    public function query(Lead $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with([
                'leadStatus:id,name,progress,color',
                'leadSubject:id,name',
                'leadSource:id,name',
                'manager:id,name',
                'assignedTo:id,name',
                'updatedBy:id,name',
                'createdBy:id,name',
                'category:id,name',
                'latestMeeting', // MorphOne Relation
                'latestNote'     // MorphOne Relation
            ])
            ->select('leads.*')
            ->where('type', 'deal');

        if (request()->filled('subject_id')) {
            $query->where('lead_subject_id', request('subject_id'));
        }

        if (request()->filled('source_id')) {
            $query->where('lead_source_id', request('source_id'));
        }

        if (request()->filled('category_id')) {
            $query->where('category_id', request('category_id'));
        }

        if (request()->filled('status_id')) {
            $query->where('status_id', request('status_id'));
        }

        if (request()->filled('manager_id')) {
            $query->where('manager_id', request('manager_id'));
        }

        if (request()->filled('assigned_to_id')) {
            $query->where('assigned_to_id', request('assigned_to_id'));
        }

        if (request()->filled('created_by')) {
            $query->where('created_by', request('created_by'));
        }

        if (request()->filled('updated_by')) {
            $query->where('updated_by', request('updated_by'));
        }

        if (request()->filled('follow_up_date_range')) {
            $dates = explode(' to ', request('follow_up_date_range'));
            $from = trim($dates[0] ?? null);
            $to = trim($dates[1] ?? $from);

            if ($from) {
                $query->whereDate('follow_up_date', '>=', $from);
            }
            if ($to) {
                $query->whereDate('follow_up_date', '<=', $to);
            }
        }

        if (request()->filled('created_at_date_range')) {
            $dates = explode(' to ', request('created_at_date_range'));
            $from = trim($dates[0] ?? null);
            $to = trim($dates[1] ?? $from);

            if ($from) {
                $query->whereDate('created_at', '>=', $from);
            }
            if ($to) {
                $query->whereDate('created_at', '<=', $to);
            }
        }

        if (request()->filled('is_failed')) {
            $isFailed = request('is_failed') === '1';
            $query->where('is_failed', $isFailed);
        }

        if (request()->filled('priority')) {
            $query->where('priority', request('priority'));
        }

        $userId = Auth::id();
        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('created_by', $userId)
                    ->orWhere('manager_id', $userId)
                    ->orWhere('assigned_to_id', $userId);
            });
        }

        return $query->latest();
    }

    protected function getAjaxParams(): array
    {
        return [
            'status_id' => '$("#filter-status").val()',
            'subject_id' => '$("#filter-subject").val()',
            'source_id' => '$("#filter-source").val()',
            'category_id' => '$("#filter-category").val()',
            'manager_id' => '$("#filter-manager").val()',
            'assigned_to_id' => '$("#filter-assigned-to").val()',
            'created_by' => '$("#filter-created-by").val()',
            'updated_by' => '$("#filter-updated-by").val()',
            'follow_up_date_range' => '$("#filter-follow-up-date").val()',
            'created_at_date_range' => '$("#filter-created-at-date").val()',
            'is_failed' => '$("#filter-failed").val()',
            'priority' => '$("#filter-priority").val()',
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
                    'data-url' => route('leads.bulk-delete'),
                ],
                'init' => 'function(dt, node, config){ $(node).attr("title", "Bulk Delete").tooltip({placement:"top"}); }'
            ]
        ];
    }

    public function getColumns(): array
    {
        return [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('customer-info')->data('customer-info')->title(__('file.table.customer_info')),
            Column::make('last-note')->data('last-note')->title(__('file.table.last_note')),
            Column::make('managed-by')->data('managed-by')->title(__('file.table.managed_by')),
            Column::make('follow-up-date')->data('follow-up-date')->title(__('file.table.follow_up_date')),
            Column::make('progress')->data('progress')->title(__('file.table.progress'))->addClass('text-center'),
            Column::make('action')->title(__('file.table.action'))->addClass('text-end')->orderable(false)->searchable(false),
        ];
    }

    protected function filename(): string
    {
        return 'Lead_' . date('YmdHis');
    }
}