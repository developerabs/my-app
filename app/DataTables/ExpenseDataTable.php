<?php

namespace App\DataTables;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class ExpenseDataTable extends BaseDataTable
{
    protected string $tableId = 'expense-table';

    protected function getExportColumns(): array|string
    {
        return [1, 2, 3, 4, 5];
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $defaultCurrency  = view()->shared('default_currency') ?? [];
        $baseCurrencyCode = $defaultCurrency['code'] ?? 'BDT';

        $dataTable = (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="'.$row->id.'" />';
            })
            ->editColumn('expense_no', function ($row) {
                return '<a href="javascript:void(0)" onclick="viewExpense(\''.(string) $row->id.'\')" class="fw-bold text-primary text-decoration-none">'.e($row->expense_no).'</a>';
            })
            ->editColumn('expense_date', function ($row) {
                return formatDate($row->expense_date);
            })
            ->addColumn('payment_account', function ($row) {
                $accName = e($row->paymentAccount->account_name ?? 'N/A');
                $method  = e(ucfirst($row->payment_method ?? 'Cash'));

                return '<div class="fw-semibold text-dark">'.$accName.'</div><small class="text-muted"><i class="fa-solid fa-credit-card me-1"></i>'.$method.'</small>';
            })
            // 🟢 মাল্টি-কারেন্সি টোটাল এক্সপেন্স
            ->editColumn('total_amount', function ($row) use ($baseCurrencyCode) {
                $currencyCode    = $row->currency->code ?? $baseCurrencyCode;
                $formattedNative = format_currency($row->total_amount, $row->currency);

                $subtext = '';
                if ($currencyCode !== $baseCurrencyCode && (float) $row->total_base_amount > 0) {
                    $subtext = '<small class="text-muted d-block" style="font-size: 11px;">≈ '.format_currency($row->total_base_amount).'</small>';
                }

                return '<div class="text-end">
                            <span class="fw-bold text-dark fs-13">'.$formattedNative.'</span>
                            '.$subtext.'
                        </div>';
            })
            ->addColumn('action', function ($row) {
                return $this->actionDropdown([
                    [
                        'type'       => 'link',
                        'label'      => __('file.view') ?? 'View Details',
                        'icon'       => 'fa-solid fa-eye text-primary',
                        'permission' => 'expense_view',
                        'onclick'    => "viewExpense('{$row->id}')",
                    ],
                    $this->linkAction(
                        label: __('file.edit') ?? 'Edit Expense',
                        href: route('expenses.edit', $row->id),
                        icon: 'fa-solid fa-pen-to-square text-warning',
                        permission: 'expense_update'
                    ),
                    $this->divider(),
                    $this->deleteAction(
                        url: route('expenses.destroy', $row->id),
                        tableId: '#expense-table',
                        item: $row->expense_no,
                        name: 'Expense',
                        permission: 'expense_delete',
                    ),
                ]);
            })
            ->addColumn('custom_info', function ($row) {
                return $this->renderCustomInfoColumn($row);
            })
            ->filterColumn('payment_account', function ($query, $keyword) {
                $query->whereHas('paymentAccount', function ($q) use ($keyword) {
                    $q->where('account_name', 'like', "%{$keyword}%");
                });
            });

        $dataTable = $this->applyCustomFieldFilter($dataTable, Expense::class);
        $dataTable = $this->applyAucitColumnLogic($dataTable);

        return $dataTable->rawColumns(['index', 'expense_no', 'payment_account', 'total_amount', 'custom_info', 'created_at', 'updated_at', 'action']);
    }

    public function query(Expense $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['paymentAccount', 'branch', 'currency', 'creator', 'updater', 'customFieldValues.customField']);

        if (! user_can_access_all_branches()) {
            $permittedBranchIds = get_auth_permitted_branch_ids();
            $query->whereIn('branch_id', $permittedBranchIds);
        }

        if (request()->filled('branch_id')) {
            $requestedBranchId = request('branch_id');
            if (user_can_access_all_branches() || in_array($requestedBranchId, get_auth_permitted_branch_ids())) {
                $query->where('branch_id', $requestedBranchId);
            }
        }

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        return $query->latest('expense_date')->latest('id');
    }

    public function getColumns(): array
    {
        $columns = [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('expense_no')->title(__('file.field.expense_no') ?? 'Expense No')->responsivePriority(1),
            Column::make('expense_date')->title(__('file.field.date') ?? 'Date'),
            Column::make('payment_account')->title(__('file.field.payment_source') ?? 'Payment Source'),
            Column::make('total_amount')->title('<div class="text-end">'.(__('file.field.total_amount') ?? 'Total Amount').'</div>')->addClass('text-end')->responsivePriority(2),
        ];

        if ($this->hasVisibleCustomFields(Expense::class)) {
            $columns[] = Column::make('custom_info')->title(__('file.table.custom_info') ?? 'Additional Info')->orderable(false)->searchable(true);
        }

        $columns = array_merge($columns, $this->auditColumns(), [
            Column::make('action')->title(__('file.table.action') ?? 'Actions')->addClass('text-end')->orderable(false)->searchable(false)->exportable(false)->printable(false)->responsivePriority(1),
        ]);

        return $columns;
    }

    protected function filename(): string
    {
        return 'Expense_'.date('YmdHis');
    }
}