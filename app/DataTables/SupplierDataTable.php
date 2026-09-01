<?php

namespace App\DataTables;

use App\Models\Supplier;
use App\Services\CurrencyConversionService;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class SupplierDataTable extends BaseDataTable
{
    protected string $tableId = 'supplier-table';

    protected function getExportColumns(): array|string
    {
        return [1, 2, 3, 4];
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $currencyService  = app(CurrencyConversionService::class);
        $defaultCurrency  = view()->shared('default_currency') ?? [];
        $baseCurrencyCode = $defaultCurrency['code'] ?? 'BDT';

        // ইউজারের ব্রাঞ্চ কারেন্সি ডিটেকশন
        $userBranchId = session('branch_id') ?? (auth()->user()->branch_id ?? null);
        $userBranch   = $userBranchId ? \App\Models\Branch::with('currency')->find($userBranchId) : null;
        $userCurrency = $userBranch?->currency ?? null;

        $dataTable = (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="'.$row->id.'" />';
            })
            ->addColumn('image', function ($row) {
                $url = $row->image ? $row->image_url : url('images/preview_image.png');

                return '<img src="'.$url.'" 
                 alt="'.e($row->name).'" 
                 class="rounded-circle shadow-sm" 
                 style="width: 40px; height: 40px; object-fit: cover; border: 1px solid #ddd;">';
            })
            ->addColumn('name', function ($row) {
                $name = '<div class="fw-bold text-dark">'.$row->name.'</div>';

                if ($row->company_name) {
                    $name .= '<div class="small text-muted" style="font-size: 11px;"><i class="fas fa-building me-1"></i>'.$row->company_name.'</div>';
                }

                $addressData = $row->address;
                $fullAddress = $addressData['full_address'] ?? '';

                if ($fullAddress) {
                    $name .= '<div class="small text-secondary mt-1" style="font-size: 11px; line-height: 1.2;">
                    <i class="fas fa-map-marker-alt me-1 text-danger"></i>'.Str::limit($fullAddress, 40).'
                  </div>';
                }

                return '<div onclick="viewSupplier(\''.(string) $row->id.'\')" style="cursor: pointer;">'.$name.'</div>';
            })
            ->addColumn('contacts', function ($row) {
                $email = $row->email ? '<a href="mailto:'.e($row->email).'"><i class="fa-solid fa-envelope"></i> '.e($row->email).'</a> <span class="copy-trigger" data-copy="'.e($row->email).'"><i class="fa-solid fa-copy"></i></span>' : '';
                $phone = $row->phone ? '<a href="tel:'.e($row->phone).'"><i class="fa-solid fa-phone"></i> '.e($row->phone).'</a> <span class="copy-trigger" data-copy="'.e($row->phone).'"><i class="fa-solid fa-copy"></i></span>' : '';

                return '<div class="d-flex flex-column gap-1"><span>'.$phone.'</span><span>'.$email.'</span></div>';
            })
            ->addColumn('bank_details', function ($row) {
                $bank = $row->bank_details;

                if (! empty($bank) && (is_array($bank) || is_object($bank))) {
                    $bankArray = array_filter((array) $bank);
                    $copyText  = e(implode(' | ', $bankArray));

                    $html = '<div class="copy-trigger cursor-pointer bank-details-wrapper" data-copy="'.$copyText.'" title="Click to copy details">';

                    if (! empty($bank['bank_name'])) {
                        $html .= '<div class="text-dark fw-bold"><i class="fa-solid fa-university me-1 text-muted"></i>'.e($bank['bank_name']).'</div>';
                    }

                    if (! empty($bank['account_name'])) {
                        $html .= '<div class="text-secondary"><i class="fa-solid fa-user me-1"></i>'.e($bank['account_name']).'</div>';
                    }

                    if (! empty($bank['account_number'])) {
                        $html .= '<div class="text-primary fw-medium" style="font-size: 12px;"><i class="fa-solid fa-hashtag me-1"></i>'.e($bank['account_number']).'</div>';
                    }

                    $html .= '</div>';

                    return $html;
                }

                return '<span class="text-muted small">-</span>';
            })
            // 🟢 সাপ্লায়ার মাল্টি-কারেন্সি ব্যালেন্স ডিসপ্লে
            ->addColumn('balance', function ($row) use ($userCurrency, $baseCurrencyCode, $currencyService) {
                $currentBaseBalance = (float) (($row->total_base_credit ?? 0) - ($row->total_base_debit ?? 0));
                $openingBaseBalance = (float) ($row->opening_balance ?? 0);

                $balanceClass = $currentBaseBalance > 0 ? 'text-success' : ($currentBaseBalance < 0 ? 'text-danger' : 'text-muted');

                $foreignSubtext = '';
                if ($userCurrency && $userCurrency->code !== $baseCurrencyCode && abs($currentBaseBalance) > 0) {
                    $foreignAmount = $currencyService->convertFromBase($currentBaseBalance, $userCurrency);
                    $foreignSubtext = '<small class="text-primary fw-semibold d-block" style="font-size: 11px;">≈ '.format_currency($foreignAmount, $userCurrency).' ('.$userCurrency->code.')</small>';
                }

                return '
                    <div class="d-flex flex-column gap-1 text-end">
                        <div class="'.$balanceClass.' fw-bold">Current: '.format_currency($currentBaseBalance).'</div>
                        '.$foreignSubtext.'
                        <div class="text-muted small" style="font-size: 10px;">Opening: '.format_currency($openingBaseBalance).'</div>
                    </div>
                ';
            })
            ->filterColumn('bank_details', function ($query, $keyword) {
                $keyword = strtolower($keyword);
                $query->where(function ($q) use ($keyword) {
                    $q->whereRaw("LOWER(bank_details->>'bank_name') LIKE ?", ["%{$keyword}%"])
                        ->orWhereRaw("LOWER(bank_details->>'account_name') LIKE ?", ["%{$keyword}%"])
                        ->orWhereRaw("LOWER(bank_details->>'account_number') LIKE ?", ["%{$keyword}%"]);
                });
            })
            ->addColumn('action', function ($row) {
                $supplierName = addslashes($row->name);

                return $this->actionDropdown([
                    $this->linkAction(
                        label: 'Make Payment',
                        href: route('supplier-payments.create', ['supplier_id' => $row->id]),
                        icon: 'fa-solid fa-money-bill-wave text-success',
                        class: 'dropdown-item text-success fw-semibold',
                        permission: 'supplier_payment'
                    ),
                    $this->divider(),
                    $this->linkAction(
                        label: __('file.view'),
                        href: route('suppliers.show', $row->id),
                        icon: 'fa-solid fa-eye text-info',
                        permission: 'supplier_view'
                    ),
                    $this->editAction(
                        onclick: "editSupplier('{$row->id}')",
                        permission: 'suppliers_update',
                    ),
                    $this->divider(),
                    $this->deleteAction(
                        url: route('suppliers.destroy', $row->id),
                        tableId: '#supplier-table',
                        item: $row->name,
                        name: 'Supplier',
                        permission: 'suppliers_delete',
                    ),
                ], [
                    'button_text' => '<i class="fa-solid fa-gear me-1"></i> '.__('Actions'),
                ]);
            })
            ->editColumn('created_at', function ($row) {
                $date     = $row->created_at ? $row->created_at->format('d M, Y h:i A') : '-';
                $userName = $row->creator ? e($row->creator->name) : '<span class="text-muted">System</span>';

                return '<div class="text-nowrap">'.$date.'</div>'.
                    '<div class="small text-muted"><i class="fa-solid fa-user-pen me-1"></i>'.$userName.'</div>';
            })
            ->editColumn('updated_at', function ($row) {
                $date     = $row->updated_at ? $row->updated_at->format('d M, Y h:i A') : '-';
                $userName = $row->updater ? e($row->updater->name) : '<span class="text-muted">N/A</span>';

                return '<div class="text-nowrap">'.$date.'</div>'.
                    '<div class="small text-muted"><i class="fa-solid fa-user-check me-1"></i>'.$userName.'</div>';
            })
            ->filterColumn('name', function ($query, $keyword) {
                $keyword = strtolower($keyword);
                $query->where(function ($q) use ($keyword) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$keyword}%"])
                        ->orWhereRaw('LOWER(company_name) LIKE ?', ["%{$keyword}%"])
                        ->orWhereRaw("LOWER(address->>'full_address') LIKE ?", ["%{$keyword}%"]);
                });
            })
            ->addColumn('custom_info', function ($row) {
                return $this->renderCustomInfoColumn($row);
            });

        $dataTable = $this->applyCustomFieldFilter($dataTable, Supplier::class);

        return $dataTable->rawColumns(['index', 'image', 'name', 'contacts', 'custom_info', 'bank_details', 'balance', 'created_at', 'updated_at', 'action']);
    }

    public function query(Supplier $model): QueryBuilder
    {
        $permittedBranchIds = ! user_can_access_all_branches() ? get_auth_permitted_branch_ids() : [];

        // 🔒 জেনারেল লেজার সাব-কুয়েরিতে ব্রাঞ্চ ফিল্টারিং প্রয়োগ
        $debitSubQuery = DB::table('general_ledgers')
            ->whereRaw('general_ledgers.sub_ledger_id::text = suppliers.id::text')
            ->where('sub_ledger_type', Supplier::class)
            ->whereIn('status', ['posted', 'reversed']);

        $creditSubQuery = DB::table('general_ledgers')
            ->whereRaw('general_ledgers.sub_ledger_id::text = suppliers.id::text')
            ->where('sub_ledger_type', Supplier::class)
            ->whereIn('status', ['posted', 'reversed']);

        if (! user_can_access_all_branches()) {
            $debitSubQuery->whereIn('branch_id', $permittedBranchIds);
            $creditSubQuery->whereIn('branch_id', $permittedBranchIds);
        }

        return $model->newQuery()
            ->select('suppliers.*')
            ->with(['creator', 'updater'])
            ->addSelect([
                'total_base_debit'  => $debitSubQuery->select(DB::raw('COALESCE(sum(base_debit), 0)')),
                'total_base_credit' => $creditSubQuery->select(DB::raw('COALESCE(sum(base_credit), 0)')),
            ]);
    }

    public function getColumns(): array
    {
        $columns = [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('image')->title(__('file.table.image'))->width('60px')->orderable(false)->searchable(false)->exportable(false)->printable(false),
            Column::make('name')->title(__('file.table.name'))->responsivePriority(2),
            Column::make('contacts')->title(__('file.table.contacts'))->orderable(false)->searchable(false)->exportable(false)->printable(false),
        ];

        if ($this->hasVisibleCustomFields(Supplier::class)) {
            $columns[] = Column::make('custom_info')->title(__('file.table.custom_info'))->orderable(false)->searchable(true)->exportable(false)->printable(false);
        }

        $columns = array_merge($columns, [
            Column::make('bank_details')->title(__('file.table.bank_details')),
            Column::make('balance')->title('<div class="text-end">'.(__('file.table.balance') ?? 'Balance').'</div>')->addClass('text-end')->orderable(false)->searchable(false),
        ], $this->auditColumns(), [
            Column::make('action')->title(__('file.table.action'))->orderable(false)->searchable(false)->exportable(false)->printable(false)->responsivePriority(3),
        ]);

        return $columns;
    }

    protected function filename(): string
    {
        return 'Supplier_'.date('YmdHis');
    }
}