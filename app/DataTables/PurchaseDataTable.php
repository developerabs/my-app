<?php

namespace App\DataTables;

use App\Models\Purchase;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Lang;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;

class PurchaseDataTable extends BaseDataTable
{
    protected string $tableId = 'purchase-table';

    protected function getExportColumns(): array|string
    {
        return [1, 2, 3, 4, 5, 6, 7, 8, 9];
    }

    public function html(): HtmlBuilder
    {
        return parent::html()
            ->parameters([
                'responsive' => false,
                'scrollX'    => true,
                'stateSave'  => true,
                'order'      => [[1, 'desc']],
            ]);
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $defaultCurrency  = view()->shared('default_currency') ?? [];
        $baseCurrencyCode = $defaultCurrency['code'] ?? 'BDT';

        $dataTable = (new EloquentDataTable($query))
            ->addIndexColumn()
            ->editColumn('index', function ($row) {
                return '<input type="checkbox" class="select-row row-select" value="'.$row->id.'">';
            })
            ->editColumn('purchase_date', function ($row) {
                return $row->purchase_date ? $row->purchase_date->format('Y-m-d') : '---';
            })
            ->editColumn('purchase_no', function ($row) {
                $html = '<a href="javascript:void(0);" onclick="viewPurchase(\''.(string) $row->id.'\')" class="fw-bold text-primary text-decoration-none">'.e($row->purchase_no).'</a>';
                if ($row->memo_number) {
                    $html .= '<br><small class="text-muted"><i class="fa-solid fa-file-invoice me-1"></i>'.e($row->memo_number).'</small>';
                }
                if ($row->reference) {
                    $html .= '<br><small class="text-secondary"><i class="fa-solid fa-hashtag me-1"></i>'.e($row->reference).'</small>';
                }
                return $html;
            })
            ->addColumn('supplier_name', function ($row) {
                if (! $row->supplier) return '<span class="text-muted">N/A</span>';
                $name = '<span class="fw-semibold text-dark">'.e($row->supplier->name).'</span>';
                if ($row->supplier->company_name) {
                    $name .= '<br><small class="text-muted">'.e($row->supplier->company_name).'</small>';
                }
                return $name;
            })
            ->addColumn('branch_name', function ($row) {
                return $row->branch->name ?? '<span class="text-muted">N/A</span>';
            })
            ->editColumn('purchase_status', function ($row) {
                $badgeClass = match ($row->purchase_status) {
                    'received'         => 'bg-success',
                    'partial',
                    'partial_received' => 'bg-info text-dark',
                    'ordered'          => 'bg-warning text-dark',
                    'pending'          => 'bg-secondary',
                    'cancelled'        => 'bg-danger',
                    default            => 'bg-light text-dark border',
                };
                $label = ucfirst(str_replace('_', ' ', $row->purchase_status));
                return '<span class="badge '.$badgeClass.' px-2 py-1">'.$label.'</span>';
            })
            ->editColumn('payment_status', function ($row) {
                $badgeClass = match ($row->payment_status) {
                    'paid'           => 'bg-success',
                    'partially_paid',
                    'partial'        => 'bg-warning text-dark',
                    'unpaid'         => 'bg-danger',
                    default          => 'bg-secondary',
                };
                $label = ucfirst(str_replace('_', ' ', $row->payment_status));
                return '<span class="badge '.$badgeClass.' px-2 py-1">'.$label.'</span>';
            })
            // 🟢 ১. মাল্টি-কারেন্সি টোটাল অ্যামাউন্ট
            ->editColumn('total_amount', function ($row) use ($baseCurrencyCode) {
                $currencyCode    = $row->currency->code ?? $baseCurrencyCode;
                $formattedNative = format_currency($row->total_amount, $row->currency);
                $subtext = '';
                if ($currencyCode !== $baseCurrencyCode && (float) $row->total_base_amount > 0) {
                    $subtext = '<br><small class="text-muted" style="font-size: 10px;">≈ '.format_currency($row->total_base_amount).'</small>';
                }
                return '<div class="text-end fw-bold text-dark">'.$formattedNative.$subtext.'</div>';
            })
            // 🟢 ২. মাল্টি-কারেন্সি পেইড অ্যামাউন্ট
            ->editColumn('paid_amount', function ($row) use ($baseCurrencyCode) {
                $currencyCode    = $row->currency->code ?? $baseCurrencyCode;
                $formattedNative = format_currency($row->paid_amount, $row->currency);
                $subtext = '';
                if ($currencyCode !== $baseCurrencyCode && (float) $row->base_paid_amount > 0) {
                    $subtext = '<br><small class="text-muted" style="font-size: 10px;">≈ '.format_currency($row->base_paid_amount).'</small>';
                }
                return '<div class="text-end text-success fw-bold">'.$formattedNative.$subtext.'</div>';
            })
            // 🟢 ৩. মাল্টি-কারেন্সি ডিউ অ্যামাউন্ট
            ->editColumn('due_amount', function ($row) use ($baseCurrencyCode) {
                $currencyCode    = $row->currency->code ?? $baseCurrencyCode;
                $colorClass      = (float) $row->due_amount > 0 ? 'text-danger fw-bold' : 'text-muted';
                $formattedNative = format_currency($row->due_amount, $row->currency);
                $subtext = '';
                if ($currencyCode !== $baseCurrencyCode && (float) $row->base_due_amount > 0) {
                    $subtext = '<br><small class="text-muted" style="font-size: 10px;">≈ '.format_currency($row->base_due_amount).'</small>';
                }
                return '<div class="text-end '.$colorClass.'">'.$formattedNative.$subtext.'</div>';
            })
            ->addColumn('custom_info', function ($row) {
                return $this->renderCustomInfoColumn($row);
            })
            ->addColumn('action', function ($row) {
                $supplierName = addslashes($row->supplier->name ?? 'Supplier');
                $dueAmount    = (float) ($row->due_amount ?? 0);
                $isCancelled  = ($row->status === 'cancelled');

                return $this->actionDropdown([
                    $this->linkAction(
                        label: __('file.view'),
                        href: route('purchases.show', $row->id),
                        icon: 'fa-solid fa-eye text-info'
                    ),
                    $this->buttonAction(
                        label: 'Pay Due',
                        icon: 'fa-solid fa-money-check-dollar text-success',
                        class: 'dropdown-item text-success fw-bold',
                        visible: $dueAmount > 0 && (! $isCancelled),
                        attributes: [
                            'onclick' => "openDocumentPaymentModal({
                                type: 'purchase',
                                id: '{$row->id}',
                                no: '{$row->purchase_no}',
                                due: {$dueAmount},
                                supplierId: '{$row->supplier_id}',
                                supplierName: '{$supplierName}',
                                tableId: '{$this->getTableId()}'
                            })",
                        ]
                    ),
                    $this->linkAction(
                        label: __('file.edit'),
                        href: route('purchases.edit', $row->id),
                        icon: 'fa-solid fa-pen-to-square text-primary',
                        visible: $row->status !== 'cancelled',
                        permission: 'purchase_update',
                    ),
                    $this->divider(),
                    $this->deleteAction(
                        url: route('purchases.destroy', $row->id),
                        tableId: '#purchase-table',
                        item: $row->purchase_no,
                        name: 'Purchase Invoice',
                        permission: 'purchase_delete',
                    ),
                ]);
            })
            ->rawColumns([
                'index', 'purchase_no', 'supplier_name', 'branch_name', 
                'purchase_status', 'payment_status', 'total_amount', 
                'paid_amount', 'due_amount', 'custom_info', 'created_at', 
                'updated_at', 'action'
            ])
            ->setRowId('id');

        $this->applyAucitColumnLogic($dataTable);
        $this->applyCustomFieldFilter($dataTable, Purchase::class);

        return $dataTable;
    }

    public function query(Purchase $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with([
                'supplier:id,name,phone,company_name',
                'branch:id,name',
                'currency:id,code,symbol',
                'creator:id,name',
                'updater:id,name',
            ]);

        if ($this->hasVisibleCustomFields(Purchase::class)) {
            $query->with(['customFieldValues.customField']);
        }

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

        if (request()->filled('supplier_id')) {
            $query->where('supplier_id', request('supplier_id'));
        }

        if (request()->filled('purchase_status')) {
            $status = request('purchase_status');
            if ($status === 'partial') {
                $query->whereIn('purchase_status', ['partial', 'partial_received']);
            } else {
                $query->where('purchase_status', $status);
            }
        }

        if (request()->filled('payment_status')) {
            $query->where('payment_status', request('payment_status'));
        }

        if (request()->filled('from_date') && request()->filled('to_date')) {
            $query->whereBetween('purchase_date', [request('from_date'), request('to_date')]);
        }

        return $query->latest();
    }

    protected function getColumns(): array
    {
        $columns = [
            $this->indexColumn(),
            Column::make('purchase_date')->title(Lang::has('file.field.purchase_date') ? __('file.field.purchase_date') : 'Purchase Date')->addClass('align-middle')->width('110px'),
            Column::make('purchase_no')->title(Lang::has('file.field.purchase_no') ? __('file.field.purchase_no') : 'Purchase No')->addClass('align-middle')->width('160px'),
            Column::make('supplier_name')->title(Lang::has('file.field.supplier') ? __('file.field.supplier') : 'Supplier')->name('supplier.name')->addClass('align-middle')->width('180px'),
            Column::make('branch_name')->title(Lang::has('file.field.branch') ? __('file.field.branch') : 'Branch')->name('branch.name')->addClass('align-middle')->width('130px'),
            Column::make('purchase_status')->title(Lang::has('file.field.purchase_status') ? __('file.field.purchase_status') : 'Purchase Status')->addClass('text-center align-middle')->width('120px'),
            Column::make('payment_status')->title(Lang::has('file.field.payment_status') ? __('file.field.payment_status') : 'Payment Status')->addClass('text-center align-middle')->width('120px'),
            Column::make('total_amount')->title('<div class="text-end">'.(Lang::has('file.field.total_amount') ? __('file.field.total_amount') : 'Total Amount').'</div>')->addClass('align-middle')->width('120px'),
            Column::make('paid_amount')->title('<div class="text-end">'.(Lang::has('file.field.paid_amount') ? __('file.field.paid_amount') : 'Paid Amount').'</div>')->addClass('align-middle')->width('110px'),
            Column::make('due_amount')->title('<div class="text-end">'.(Lang::has('file.field.due_amount') ? __('file.field.due_amount') : 'Due Amount').'</div>')->addClass('align-middle')->width('110px'),
        ];

        if ($this->hasVisibleCustomFields(Purchase::class)) {
            $columns[] = Column::make('custom_info')->title(Lang::has('file.table.custom_fields') ? __('file.table.custom_fields') : 'Custom Fields')->orderable(false)->searchable(false)->addClass('align-middle');
        }

        $columns = array_merge($columns, $this->auditColumns());
        $columns[] = Column::computed('action')->title('<div class="text-end">'.(Lang::has('file.table.action') ? __('file.table.action') : 'Action').'</div>')->exportable(false)->printable(false)->width('90px')->addClass('text-end align-middle');

        return $columns;
    }

    protected function filename(): string
    {
        return 'Purchases_'.date('Ymd_His');
    }
}