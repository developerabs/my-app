<?php

namespace App\DataTables;

use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Column;

abstract class BaseDataTable extends DataTable
{
    /**
     * English: Default table ID
     */
    protected string $tableId = 'datatable';

    /**
     * English: Method to define exportable columns to avoid property type mismatch
     */
    protected function getExportColumns(): array|string
    {
        return [0, 1, 2, 3, 4];
    }

    /**
     * English: Specify which buttons to disable (excel, csv, pdf, print)
     */
    protected function disabledButtons(): array
    {
        return [];
    }

    /**
     * English: Control visibility of Reset and Refresh buttons
     */
    protected function showUtilityButtons(): bool
    {
        return true; // Default: show them
    }

    /**
     * English: Build the HTML for the DataTable
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId($this->getTableId())
            ->columns($this->getColumns())
            ->minifiedAjax('', null, $this->getAjaxParams())
            ->parameters([
                'stateSave' => true,
                'scrollY' => '70vh',
                'scrollCollapse' => true,
                'fixedHeader' => ['header' => true],
                'responsive' => true,
                'autoWidth' => false,
                'order' => [[1, 'asc']],
                'dom' => "<'row mb-3'<'col-md-2 d-none d-md-block'l><'col-md-4 order-3 order-md-2 d-flex align-items-center justify-content-center'f><'col-md-6 order-2 order-md-3 d-flex ps-0 align-items-center justify-content-center justify-content-md-end'B>>" .
                    "<'row'<'col-12'tr>>" .
                    "<'row mt-3'<'col-12 col-md-5'i><'col-12 col-md-7 d-flex justify-content-center justify-content-md-end'p>>",
                'buttons' => $this->getButtons(),
                'language' => $this->getLanguageConfig(),
            ]);
    }

    /**
     * English: Generate common buttons for the table
     */
    protected function getButtons(): array
    {
        $btns = [
            'excel' => ['icon' => 'fa-file-excel', 'class' => 'btn-success'],
            'csv'   => ['icon' => 'fa-file-csv', 'class' => 'btn-info'],
            'pdf'   => ['icon' => 'fa-file-pdf', 'class' => 'btn-danger'],
            'print' => ['icon' => 'fa-print', 'class' => 'btn-primary'],
        ];

        $config = [];
        foreach ($btns as $type => $attr) {
            if (in_array($type, $this->disabledButtons())) {
                continue;
            }

            $config[] = [
                'extend' => $type,
                'className' => "btn {$attr['class']} me-1",
                'text' => '<i class="fa-solid ' . $attr['icon'] . '"></i>',
                'exportOptions' => ['columns' => $this->getExportColumns()],
                'init' => "function(dt, node, config){ $(node).attr('title', 'Export to " . ucfirst($type) . "').tooltip({placement:'top'}); }"
            ];
        }
        // English: Add Column Visibility Button (ColVis)
        $config[] = [
            'extend' => 'colvis',
            'className' => 'btn btn-outline-secondary me-1',
            'text' => '<i class="fa-solid fa-columns"></i>',
            'columnText' => 'function ( dt, idx, title ) {
                // English: Remove any HTML tags from the title
                let cleanTitle = title.replace(/<[^>]*>?/gm, "").trim();
                
                // English: If the title becomes empty (which happens for checkbox-only headers), return "Select"
                return cleanTitle.length > 0 ? cleanTitle : "Select All";
            }',
            'align' => 'button-right',
            'init' => "function(dt, node, config){ $(node).attr('title', 'Show/Hide Columns').tooltip({placement:'top'}); }"
        ];

        $utils = [];
        // English: Only add Utility buttons if showUtilityButtons is true
        if ($this->showUtilityButtons()) {
            $utils = [
                [
                    'text' => '<i class="fa-solid fa-rotate-left"></i>',
                    'className' => 'btn btn-secondary me-1',
                    'action' => 'function ( e, dt, node, config ) { dt.search("").columns().search("").draw(); }',
                    'init' => 'function(dt, node, config){ $(node).attr("title", "Clear Filters").tooltip({placement:"top"}); }'
                ],
                [
                    'text' => '<i class="fa-solid fa-arrows-rotate"></i>',
                    'className' => 'btn btn-warning me-1',
                    'action' => 'function ( e, dt, node, config ) { dt.ajax.reload(); }',
                    'init' => 'function(dt, node, config){ $(node).attr("title", "Refresh").tooltip({placement:"top"}); }'
                ]
            ];
        }

        return array_merge($config, $utils, $this->getCustomButtons());
    }


    protected function indexColumn(): Column
    {
        return Column::make('index')
            ->title('<input type="checkbox" class="select-all" />') // Table header text
            ->exportable(false)
            ->printable(false)
            ->orderable(false)
            ->searchable(false)
            ->addClass('text-center')
            ->width('20px')
            ->responsivePriority(1);
    }

    /**
     * English: Common Audit Columns (Hidden by default)
     * Usage: Merge this in getColumns() of child classes
     */
    protected function auditColumns(): array
    {
        return [
            Column::make('created_at')->title('<div class="text-end">' . __('file.table.created_at') . '</div>')->addClass('text-end')->visible(false)->printable(false)->exportable(true),
            Column::make('updated_at')->title('<div class="text-end">' . __('file.table.updated_at') . '</div>')->addClass('text-end')->visible(false)->printable(false)->exportable(true),
        ];
    }

    protected function applyAucitColumnLogic($dataTable)
    {
        return $dataTable
            ->editColumn('created_at', function ($row) {
                $date = formatDate($row->created_at, true);

                $user = isset($row->created_by) ? '<br><small class="text-muted">by: ' . $row->creator->name . '</small>' : '';

                return $date . $user;
            })
            ->editColumn('updated_at', function ($row) {
                $date = formatDate($row->updated_at, true);
                $user = isset($row->updated_by) ? '<br><small class="text-muted">by: ' . $row->updater->name . '</small>' : '';
                return $date . $user;
            });
    }

    protected function hasVisibleCustomFields($modelClass): bool
    {
        $exists = \App\Models\CustomField::where('model_type', $modelClass)
            ->where('show_in_list', true)
            ->exists();

        //dd($exists);
        // Debug statement to check if the query is working correctly
        return $exists;
    }

    // English: Common logic to render custom info column
    protected function renderCustomInfoColumn($row)
    {
        // ১. নিশ্চিত হওয়া যে রিলেশনটি লোড হয়েছে এবং এটি null নয়
        if (!$row->customFieldValues) {
            return '<span class="text-muted">---</span>';
        }

        $visibleValues = $row->customFieldValues->filter(function ($val) {
            // ২. এখানে চেক করুন customField রিলেশনটি null কি না
            // আপনার মডেলে যদি রিলেশন নাম customField হয় তবে সেটিই লিখুন
            return $val->customField !== null && $val->customField->show_in_list == true;
        });

        if ($visibleValues->isEmpty()) {
            return '<span class="text-muted">---</span>';
        }

        $html = '<div class="d-flex flex-wrap gap-1">';
        foreach ($visibleValues as $val) {
            // ৩. এখানেও আমরা নিশ্চিত হবো যে অবজেক্টটি আছে
            if ($val->customField) {
                $label = e($val->customField->label);
                $value = e($val->value);
                $html .= "<span class='badge border text-dark fw-normal' style='background:#f8f9fa' title='{$label}'>
                            <span class='text-primary small'>{$label}:</span> {$value}
                        </span>";
            }
        }
        return $html . '</div>';
    }

    /**
     * English: DataTable language configurations
     */
    protected function getLanguageConfig(): array
    {
        return [
            'search' => '',
            'searchPlaceholder' => __('file.table.search') . '...',
            'lengthMenu' => "_MENU_ ",
            'info' => __('file.table.showing') . ' _START_ ' . __('file.table.to') . ' _END_ ' . __('file.table.of') . ' _TOTAL_ ' . __('file.table.entries'),
            'infoEmpty' => __('file.table.no_entries'),
            'infoFiltered' => '(' . __('file.table.filtered_from') . ' _MAX_ ' . __('file.table.total_entries') . ')',
            'zeroRecords' => __('file.table.no_matching_records'),
            'paginate' => [
                'previous' => __('file.table.prev'),
                'next' => __('file.table.next'),
            ],
        ];
    }

    protected function applyCustomFieldFilter($dataTable, $modelClass)
    {
        // কাস্টম কলামের জন্য ফিল্টার ডিফাইন করা
        return $dataTable->filterColumn('custom_info', function ($query, $keyword) {
            // মেইন কুয়েরিকে একটি গ্রুপড হোয়্যার (Grouped Where) এর মধ্যে রাখা ভালো
            $query->where(function ($rootQuery) use ($keyword) {
                $rootQuery->whereHas('customFieldValues', function ($q) use ($keyword) {
                    $q->where('value', 'like', "%{$keyword}%")
                        ->orWhereHas('customField', function ($subQ) use ($keyword) {
                            $subQ->where('label', 'like', "%{$keyword}%");
                        });
                });
            });
        });
    }

    protected function getTableId(): string
    {
        return $this->tableId;
    }
    protected function getAjaxParams(): array
    {
        return [];
    }
    protected function getCustomButtons(): array
    {
        return [];
    }

    /**
     * Check permission (optional)
     */
    protected function can(?string $permission): bool
    {
        if (blank($permission)) {
            return true;
        }

        return auth()->check() && auth()->user()->can($permission);
    }

    protected function divider(): array
    {
        return [
            'type' => 'divider',
        ];
    }

    protected function editAction(
        string $onclick,
        ?string $permission = null,
        bool $visible = true,
        ?string $label = null,
        ?string $icon = 'fa-solid fa-pen-to-square text-primary',
    ): ?array {

        if (!$visible) {
            return null;
        }

        return [
            'type'       => 'link',
            'label'      => $label ?? __('file.edit'),
            'icon'       => $icon,
            'permission' => $permission,
            'onclick'    => $onclick,
        ];
    }

    protected function deleteAction(
        string $url,
        string $tableId,
        string $item,
        string $name,
        ?string $permission = null,
        bool $visible = true,
        ?string $label = null,
    ): ?array {

        if (!$visible) {
            return null;
        }

        return [
            'type'       => 'button',
            'label'      => $label ?? __('Delete'),
            'icon'       => 'fa-solid fa-trash text-danger',
            'permission' => $permission,
            'class'      => 'dropdown-item delete-btn text-danger',
            'attributes' => [
                'data-url'      => $url,
                'data-table-id' => $tableId,
                'data-item'     => $item,
                'data-name'     => $name,
            ],
        ];
    }

    protected function linkAction(
        string $label,
        string $href,
        string $icon = '',
        ?string $permission = null,
        bool $visible = true,
        ?string $target = null,
        string $class = 'dropdown-item'
    ): ?array {

        if (!$visible) {
            return null;
        }

        return [
            'type'       => 'link',
            'label'      => $label,
            'href'       => $href,
            'icon'       => $icon,
            'permission' => $permission,
            'target'     => $target,
            'class'      => $class,
        ];
    }

    protected function buttonAction(
        string $label,
        string $class = 'dropdown-item',
        array $attributes = [],
        string $icon = '',
        ?string $permission = null,
        bool $visible = true,
    ): ?array {

        if (!$visible) {
            return null;
        }

        return [
            'type'       => 'button',
            'label'      => $label,
            'class'      => $class,
            'attributes' => $attributes,
            'icon'       => $icon,
            'permission' => $permission,
        ];
    }

    /**
     * Reusable Action Dropdown
     */
    protected function actionDropdown(array $items, array $options = []): string
    {
        $buttonText = $options['button_text'] ?? __('Actions');
        $buttonClass = $options['button_class'] ?? 'btn btn-light btn-sm border';

        $actions = [];

        foreach ($items as $item) {

            if (empty($item)) {
                continue;
            }

            if (
                array_key_exists('visible', $item)
                && !$item['visible']
            ) {
                continue;
            }

            if (!$this->can($item['permission'] ?? null)) {
                continue;
            }

            if (($item['type'] ?? '') === 'divider') {

                if (!empty($actions) && end($actions) !== '__divider__') {
                    $actions[] = '__divider__';
                }

                continue;
            }

            $icon = '';

            if (!empty($item['icon'])) {
                $icon = '<i class="' . $item['icon'] . ' me-2"></i>';
            }

            $class = $item['class'] ?? 'dropdown-item';

            $attrs = '';

            foreach (($item['attributes'] ?? []) as $key => $value) {
                $attrs .= ' ' . $key . '="' . e($value) . '"';
            }

            if (!empty($item['onclick'])) {
                $attrs .= ' onclick="' . $item['onclick'] . '"';
            }

            if (($item['type'] ?? 'link') === 'button') {

                $actions[] = '
                    <li>
                        <button
                            type="button"
                            class="' . $class . '"
                            ' . $attrs . '>
                            ' . $icon . $item['label'] . '
                        </button>
                    </li>';

                continue;
            }

            $href = $item['href'] ?? 'javascript:void(0)';

            if (!empty($item['target'])) {
                $attrs .= ' target="' . $item['target'] . '"';
            }

            $actions[] = '
                <li>
                    <a
                        href="' . $href . '"
                        class="' . $class . '"
                        ' . $attrs . '>

                        ' . $icon . $item['label'] . '

                    </a>
                </li>';
        }

        // remove divider from start/end
        while (!empty($actions) && reset($actions) === '__divider__') {
            array_shift($actions);
        }

        while (!empty($actions) && end($actions) === '__divider__') {
            array_pop($actions);
        }

        $actions = array_map(function ($item) {

            if ($item === '__divider__') {
                return '<li><hr class="dropdown-divider"></li>';
            }

            return $item;
        }, $actions);

        if (empty($actions)) {
            return '<span class="text-muted">-</span>';
        }

        $menuId = 'action-menu-' . uniqid();

        return '

        <div class="text-end">

            <button
                type="button"
                class="' . $buttonClass . ' action-menu"
                data-menu="' . $menuId . '">

                ' . $buttonText . '

            </button>

            <div id="' . $menuId . '" class="d-none">

                <ul class="dropdown-menu show position-static shadow border-0 m-0" style="display:block;min-width:180px;">

                    ' . implode('', $actions) . '

                </ul>

            </div>

        </div>

        ';
    }

    abstract protected function getColumns(): array;
}
