<?php

namespace App\DataTables;

use App\Models\Bill;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class BillDataTable extends BaseDataTable
{
    protected string $tableId = 'bill-table';

    protected function getExportColumns(): array|string
    {
        return [1, 2, 3, 4, 5, 6];
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $defaultCurrency  = view()->shared('default_currency') ?? [];
        $baseCurrencyCode = $defaultCurrency['code'] ?? 'BDT';

        $dataTable = (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="'.$row->id.'" />';
            })
            ->editColumn('bill_no', function ($row) {
                return '<a href="javascript:void(0)" onclick="viewBill(\''.(string) $row->id.'\')" class="fw-bold text-primary text-decoration-none">'.e($row->bill_no).'</a>';
            })
            ->editColumn('bill_date', function ($row) {
                return formatDate($row->bill_date);
            })
            ->addColumn('supplier', function ($row) {
                return e($row->supplier->name ?? 'N/A');
            })
            // 🟢 ১. মাল্টি-কারেন্সি টোটাল বিল অ্যামাউন্ট
            ->editColumn('total_amount', function ($row) use ($baseCurrencyCode) {
                $currencyCode    = $row->currency->code ?? $baseCurrencyCode;
                $formattedNative = format_currency($row->total_amount, $row->currency);

                $subtext = '';
                if ($currencyCode !== $baseCurrencyCode && (float) $row->total_base_amount > 0) {
                    $subtext = '<small class="text-muted d-block" style="font-size: 11px;">≈ '.format_currency($row->total_base_amount).'</small>';
                }

                return '<div class="text-end">
                            <span class="fw-bold text-dark me-1">'.$formattedNative.'</span>
                            '.$subtext.'
                        </div>';
            })
            // 🟢 ২. মাল্টি-কারেন্সি ডিউ ও ওভারডিউ ট্র্যাকিং
            ->editColumn('due_amount', function ($row) use ($baseCurrencyCode) {
                $currencyCode       = $row->currency->code ?? $baseCurrencyCode;
                $dueColor           = (float) $row->due_amount > 0 ? 'text-danger' : 'text-success';
                $formattedDueNative = format_currency($row->due_amount, $row->currency);
                $dueAmountHtml      = '<span class="fw-bold me-1 '.$dueColor.'">'.$formattedDueNative.'</span>';

                if ($currencyCode !== $baseCurrencyCode && (float) $row->base_due_amount > 0) {
                    $dueAmountHtml .= '<small class="text-muted d-block" style="font-size: 10px;">≈ '.format_currency($row->base_due_amount).'</small>';
                }

                if (! $row->due_date) {
                    return '<div class="text-end">'.$dueAmountHtml.'</div>';
                }

                $today    = now()->startOfDay();
                $dueDate  = Carbon::parse($row->due_date)->startOfDay();
                $daysDiff = (int) $today->diffInDays($dueDate, false);

                $dateColorClass = 'text-muted';
                $statusTag      = '';

                if ((float) $row->due_amount > 0) {
                    if ($daysDiff < 0) {
                        $dateColorClass = 'text-danger fw-bold';
                        $overdueDays    = abs($daysDiff);
                        $statusTag      = "<span class=\"badge bg-danger-subtle text-danger border border-danger-subtle ms-1\" style=\"font-size:10px;\">Overdue ({$overdueDays}d)</span>";
                    } elseif ($daysDiff >= 0 && $daysDiff <= 5) {
                        $dateColorClass = 'text-warning-emphasis fw-bold';
                        $statusTag      = "<span class=\"badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-1\" style=\"font-size:10px;\">Due in {$daysDiff}d</span>";
                    }
                }

                $formattedDueDate = formatDate($row->due_date);
                $dateHtml         = "<div class=\"small {$dateColorClass} mt-1\"><i class=\"fa-regular fa-calendar-alt me-1\"></i>{$formattedDueDate}{$statusTag}</div>";

                return "<div class=\"text-end\">{$dueAmountHtml}{$dateHtml}</div>";
            })
            ->editColumn('payment_status', function ($row) {
                $badge = match ($row->payment_status) {
                    'paid'           => 'bg-success',
                    'partially_paid' => 'bg-warning text-dark',
                    default          => 'bg-danger',
                };

                return '<span class="badge '.$badge.'">'.ucfirst(str_replace('_', ' ', $row->payment_status)).'</span>';
            })
            ->addColumn('action', function ($row) {
                $supplierName = addslashes($row->supplier->name ?? 'Supplier');
                $dueAmount    = (float) ($row->due_amount ?? 0);
                $isCancelled  = ($row->status === 'cancelled');

                $actions = [
                    [
                        'type'       => 'link',
                        'label'      => __('file.view') ?? 'View Details',
                        'icon'       => 'fa-solid fa-eye text-primary',
                        'permission' => 'bill_view',
                        'onclick'    => "viewBill('{$row->id}')",
                    ],
                ];

                if ($dueAmount > 0 && ! $isCancelled) {
                    $actions[] = [
                        'type'       => 'link',
                        'label'      => 'Pay Bill',
                        'icon'       => 'fa-solid fa-money-check-dollar text-success',
                        'permission' => 'bill_payment',
                        'onclick'    => "openDocumentPaymentModal({
                            type: 'bill',
                            id: '{$row->id}',
                            no: '{$row->bill_no}',
                            due: {$dueAmount},
                            supplierId: '{$row->supplier_id}',
                            supplierName: '{$supplierName}',
                            tableId: '{$this->getTableId()}'
                        })",
                    ];
                }

                $activeLateFeeTotal = (float) $row->financeCharges
                    ->whereIn('status', ['posted', 'partially_waived'])
                    ->sum('amount');

                if ($dueAmount > 0 && $row->payment_status !== 'paid' && $activeLateFeeTotal > 0 && ! $isCancelled) {
                    $actions[] = [
                        'type'       => 'link',
                        'label'      => 'Waive Late Fee',
                        'icon'       => 'fa-solid fa-hand-holding-dollar text-danger',
                        'permission' => 'acc_finance_charge_waive',
                        'onclick'    => "openWaiveModalForBill('{$row->id}', '{$row->bill_no}', {$activeLateFeeTotal})",
                    ];
                }

                if (! $isCancelled) {
                    $actions[] = $this->linkAction(
                        label: __('file.edit') ?? 'Edit Bill',
                        href: route('bills.edit', $row->id),
                        icon: 'fa-solid fa-pen-to-square text-warning',
                        permission: 'bill_update'
                    );
                }

                $actions[] = $this->divider();
                $actions[] = $this->deleteAction(
                    url: route('bills.destroy', $row->id),
                    tableId: $this->getTableId(),
                    item: $row->bill_no,
                    name: 'Vendor Bill',
                    permission: 'bill_delete',
                );

                return $this->actionDropdown($actions);
            })
            ->addColumn('custom_info', function ($row) {
                return $this->renderCustomInfoColumn($row);
            });

        $dataTable = $this->applyAucitColumnLogic($dataTable);

        return $dataTable->rawColumns(['index', 'bill_no', 'supplier', 'total_amount', 'due_amount', 'payment_status', 'created_at', 'updated_at', 'action']);
    }

    public function query(Bill $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['supplier', 'branch', 'currency', 'creator', 'updater', 'financeCharges']);

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
            $query->where('payment_status', request('status'));
        }

        return $query->latest('bill_date')->latest('id');
    }

    public function getColumns(): array
    {
        return [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('bill_no')->title(__('file.field.bill_no') ?? 'Bill No')->responsivePriority(1),
            Column::make('bill_date')->title(__('file.field.date') ?? 'Bill Date'),
            Column::make('supplier')->title(__('file.field.supplier') ?? 'Supplier'),
            Column::make('total_amount')->title('<div class="text-end">'.(__('file.field.total_amount') ?? 'Total Amount').'</div>')->addClass('text-end'),
            Column::make('due_amount')->title('<div class="text-end">'.(__('file.field.due_amount') ?? 'Due Amount & Date').'</div>')->addClass('text-end me-2')->responsivePriority(2),
            Column::make('payment_status')->title('<div class="text-center">'.(__('file.field.status') ?? 'Status').'</div>')->addClass('text-center'),
            ...$this->auditColumns(),
            Column::make('action')->title(__('file.table.action') ?? 'Actions')->addClass('text-end')->orderable(false)->searchable(false)->exportable(false)->printable(false)->responsivePriority(1),
        ];
    }

    protected function filename(): string
    {
        return 'Bill_'.date('YmdHis');
    }
}