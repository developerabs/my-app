<?php

namespace App\DataTables;

use App\Models\PublicForm;
use App\Models\PublicFormField;
use App\Models\PublicFormResponse;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class PublicFormResponseDataTable extends BaseDataTable
{
    protected string $tableId = 'public-form-response-table';

    protected ?PublicForm $resolvedForm = null;

    protected function getExportColumns(): array|string
    {
        return [1, 2, 3, 4];
    }

    /**
     * Resolve the current public form (with fields) once per request, from either the
     * ajax `public_form_id` param or the property set by the controller.
     */
    protected function getForm(): ?PublicForm
    {
        if ($this->resolvedForm) {
            return $this->resolvedForm;
        }

        $formId = request('public_form_id') ?? $this->public_form_id ?? null;
        if (!$formId) {
            return null;
        }

        return $this->resolvedForm = PublicForm::with('fields')->find($formId);
    }

    /**
     * DB-safe key used for columns, filters and response_data JSON lookups.
     */
    protected function resolveFieldKey(PublicFormField $field): string
    {
        $key = $field->name ?: Str::slug($field->label ?: 'field');

        return str_replace('-', '_', $key);
    }

    /**
     * Turn a field key into the HTML element id used by the filter inputs in the blade view.
     */
    protected function filterElementId(string $fieldKey): string
    {
        return 'filter-field-' . str_replace('_', '-', $fieldKey);
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $dataTable = (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->editColumn('created_at', function (PublicFormResponse $row) {
                return formatDate($row->created_at, true); 
            })
            ->addColumn('action', function (PublicFormResponse $row) {
                $actions = [];

                $actions[] = $this->editAction(
                    onclick: "showResponseDetails('{$row->id}')",
                    label: 'View Details',
                    icon: 'fa-solid fa-eye text-info',
                );
                
                $actions[] = $this->divider();

                $actions[] = $this->deleteAction(
                    url: route('public-forms-responses.destroy', $row->id),
                    tableId: '#public-form-response-table',
                    item: 'Response #' . $row->id,
                    name: 'Public Form Response',
                );

                return $this->actionDropdown($actions);
            });

        $rawColumnsList = ['index', 'form_title', 'lead_status', 'ip_address', 'action'];

        $form = $this->getForm();
        if ($form) {
            foreach ($form->fields as $field) {
                if (!$field->show_in_table) {
                    continue;
                }

                $fieldKey = $this->resolveFieldKey($field);
                $fieldType = $field->type;
                $rawColumnsList[] = $fieldKey;

                $dataTable->addColumn($fieldKey, function ($row) use ($fieldKey, $fieldType) {
                    $submittedData = $row->response_data ?? [];
                    $value = $submittedData[$fieldKey] ?? null;

                    if (is_array($value)) {
                        $value = implode(', ', $value);
                    }

                    if ($fieldType === 'file' && $value) {
                        return '<a href="' . e(file_url($value)) . '" target="_blank" rel="noopener" class="text-primary">'
                            . '<i class="fa-solid fa-paperclip me-1"></i>' . __('file.button.view') . '</a>';
                    }

                    return '<span class="text-secondary">' . e($value ?? 'N/A') . '</span>';
                });

                // Only wire up global/column search for fields explicitly marked searchable.
                if ($field->searchable) {
                    $dataTable->filterColumn($fieldKey, function ($query, $keyword) use ($fieldKey) {
                        $this->applyJsonFieldSearch($query, $fieldKey, $keyword);
                    });
                }
            }
        }

        return $dataTable->rawColumns($rawColumnsList)->setRowId('id');
    }

    /**
     * Build a driver-agnostic "LOWER(JSON field as text)" SQL fragment for response_data->fieldKey.
     * Returns [sql, bindings] so the caller can append its own comparison operator/value.
     */
    protected function jsonFieldLowerExpr($query, string $fieldKey): array
    {
        $driver = $query->getConnection()->getDriverName();

        return match ($driver) {
            'pgsql' => ['LOWER(response_data->>?)', [$fieldKey]],
            'sqlsrv' => ['LOWER(JSON_VALUE(response_data, ?))', ['$."' . str_replace('"', '\"', $fieldKey) . '"']],
            'sqlite' => ['LOWER(JSON_EXTRACT(response_data, ?))', ['$."' . str_replace('"', '\"', $fieldKey) . '"']],
            default => ['LOWER(JSON_UNQUOTE(JSON_EXTRACT(response_data, ?)))', ['$."' . str_replace('"', '\"', $fieldKey) . '"']],
        };
    }

    /**
     * Case-insensitive LIKE search inside the response_data JSON column. Both sides are
     * lower-cased explicitly to avoid capital/small letter mismatches across DB drivers.
     */
    protected function applyJsonFieldSearch($query, string $fieldKey, string $keyword): void
    {
        [$expr, $bindings] = $this->jsonFieldLowerExpr($query, $fieldKey);

        $query->whereRaw("{$expr} LIKE ?", [...$bindings, '%' . mb_strtolower($keyword) . '%']);
    }

    public function query(PublicFormResponse $model): QueryBuilder
    {
        $query = $model->newQuery()->with('publicForm');

        if (request()->filled('public_form_id')) {
            $query->where('public_form_id', request('public_form_id'));
        } elseif (isset($this->public_form_id)) {
            $query->where('public_form_id', $this->public_form_id);
        }

        $form = $this->getForm();
        if ($form) {
            foreach ($form->fields as $field) {
                if (!$field->filterable) {
                    continue;
                }

                $fieldKey = $this->resolveFieldKey($field);
                $value = request('filter_field_' . $fieldKey);

                if (!is_null($value) && $value !== '') {
                    [$expr, $bindings] = $this->jsonFieldLowerExpr($query, $fieldKey);
                    $query->whereRaw("{$expr} = ?", [...$bindings, mb_strtolower($value)]);
                }
            }
        }

        return $query->latest();
    }

    protected function getAjaxParams(): array
    {
        $params = [];

        $form = $this->getForm();
        if ($form) {
            foreach ($form->fields as $field) {
                if (!$field->filterable) {
                    continue;
                }

                $fieldKey = $this->resolveFieldKey($field);
                $params['filter_field_' . $fieldKey] = '$("#' . $this->filterElementId($fieldKey) . '").val()';
            }
        }

        return $params;
    }

    protected function getCustomButtons(): array
    {
        return [
            [
                'text' => '<i class="fa-solid fa-trash"></i><span class="bulk-count d-none">(0)</span>',
                'className' => 'btn btn-danger btn-bulk-delete me-1 disabled',
                'attr' => [
                    'id' => 'bulk-delete-btn',
                    'data-url' => route('public-forms-responses.bulk-delete'),
                ],
                'init' => 'function(dt, node, config){ $(node).attr("title", "Bulk Delete").tooltip({placement:"top"}); }'
            ]
        ];
    }

    public function getColumns(): array
    {
        $columns = [
            $this->indexColumn()->titleAttr('Select'),
        ];

        $form = $this->getForm();
        if ($form) {
            foreach ($form->fields as $field) {
                if (!$field->show_in_table) {
                    continue;
                }

                $fieldKey = $this->resolveFieldKey($field);
                $fieldLabel = $field->label ?: ucfirst($fieldKey);
                $columns[] = Column::computed($fieldKey)->title($fieldLabel)->searchable((bool) $field->searchable);
            }
        }

        $columns[] = Column::make('created_at')->title('Submitted At');
        $columns[] = Column::computed('action')->title(__('file.table.action'))->exportable(false)->printable(false)->addClass('text-end')->responsivePriority(1);

        return $columns;
    }

    protected function filename(): string
    {
        return 'Public_Form_Responses_' . date('YmdHis');
    }
}
