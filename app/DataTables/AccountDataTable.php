<?php

namespace App\DataTables;

use App\Models\Account;
use App\Models\Setting;
use App\Services\CurrencyConversionService;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class AccountDataTable extends BaseDataTable
{
    protected string $tableId = 'account-table';

    protected function getExportColumns(): array|string
    {
        return [2, 3, 4, 5];
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $user = auth()->user();
        $defaultCurrency    = view()->shared('default_currency') ?? [];
        $baseCurrencyCode   = $defaultCurrency['code'] ?? 'BDT';
        $baseCurrencySymbol = $defaultCurrency['symbol'] ?? '৳';
        $currencyService    = app(CurrencyConversionService::class);

        $dataTable = (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })

            // 🟢 ১. Name Column with Currency Badge
            ->editColumn('name', function ($row) use ($baseCurrencyCode) {
                $currencyCode = $row->currency->code ?? $baseCurrencyCode;
                $isBase = $currencyCode === $baseCurrencyCode;
                
                $badgeClass = $isBase 
                    ? 'bg-primary-subtle text-primary border border-primary-subtle' 
                    : 'bg-success-subtle text-success border border-success-subtle';
                
                $defaultBadge = $row->is_default 
                    ? '<span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1" style="font-size: 10px;">Default</span>' 
                    : '';

                $accountNo = $row->account_number ? ' | Acc: ' . e($row->account_number) : '';

                return '
                    <div>
                        <span class="fw-bold text-dark fs-6">' . e($row->account_name) . '</span>
                        <span class="badge ' . $badgeClass . ' ms-1" style="font-size: 10px;">' . e($currencyCode) . '</span>
                        ' . $defaultBadge . '
                        <small class="text-muted d-block">' . e($row->account_code) . $accountNo . '</small>
                    </div>
                ';
            })

            // 🟢 ২. Branch Column
            ->addColumn('branch', function ($row) {
                return $row->branch ? e($row->branch->name) : '<span class="text-muted">Head Office (All)</span>';
            })

            // 🟢 ৩. Bank & Branch Details
            ->addColumn('details', function ($row) {
                $bank    = $row->bank_name ?? 'N/A';
                $branch  = $row->branch_name ?? 'N/A';
                $routing = $row->routing_number ?? 'N/A';

                $copyData = "Bank: {$bank}\nBranch: {$branch}\nRouting: {$routing}";

                return '
                    <small class="copy-trigger text-muted" data-copy="' . e($copyData) . '" style="cursor: pointer;" title="Click to copy">
                        <strong class="text-dark">Bank:</strong> ' . e($bank) . '<br>
                        <strong class="text-dark">Branch:</strong> ' . e($branch) . '<br>
                        <strong class="text-dark">Routing:</strong> ' . e($routing) . '
                    </small>
                ';
            })

            // 🟢 ৪. Opening Balance
            ->editColumn('opening_balance', function ($row) use ($baseCurrencyCode) {
                $currencyCode = $row->currency->code ?? $baseCurrencyCode;
                $isForeign    = $currencyCode !== $baseCurrencyCode;

                $formattedNative = format_currency($row->opening_balance, $row->currency);
                $formattedDate   = $row->opening_balance_date ? formatDate($row->opening_balance_date) : 'N/A';

                $subtext = '';
                if ($isForeign && (float)$row->base_opening_balance > 0) {
                    $subtext = '<small class="text-muted d-block" style="font-size: 11px;">≈ ' . format_currency($row->base_opening_balance) . '</small>';
                }

                return '
                    <div class="text-end">
                        <span class="fw-bold text-dark">' . $formattedNative . '</span>
                        ' . $subtext . '
                        <small class="text-muted d-block" style="font-size: 10px;">' . $formattedDate . '</small>
                    </div>
                ';
            })

            // 🟢 ৫. Current Balance: অফিশিয়াল ফিক্সড রেট (১২১.৬০) দ্বারা লাইভ মার্কেট ভ্যালু ক্যালকুলেশন
            ->editColumn('current_balance', function ($row) use ($baseCurrencyCode, $baseCurrencySymbol, $currencyService) {
                $currencyCode = $row->currency->code ?? $baseCurrencyCode;
                $isForeign    = $currencyCode !== $baseCurrencyCode;

                $formattedNative = format_currency($row->current_balance, $row->currency);
                $lastTxn         = $row->last_transaction_date ? formatDate($row->last_transaction_date, true) : 'N/A';

                if (!$isForeign) {
                    return '
                        <div class="text-end">
                            <span class="fw-bold text-dark">' . $formattedNative . '</span>
                            <small class="text-muted d-block" style="font-size: 10px;">Last: ' . $lastTxn . '</small>
                        </div>
                    ';
                }

                // 🟢 অফিশিয়াল স্পট এক্সচেঞ্জ রেট সরাসরি রিড করা (১২১.৬০)
                $currentRate = (float) $currencyService->getExchangeRate($row->currency_id ?? Setting::get('default_currency'));
                $liveMarketValue = round((float)$row->current_balance * $currentRate, 2);
                $bookValue       = (float)($row->base_current_balance ?? 0);
                $unrealizedDiff  = round($liveMarketValue - $bookValue, 2);

                // Tooltip Content: অফিশিয়াল রেটসহ স্পষ্ট বিবরণ
                $gainLossText = $unrealizedDiff > 0 
                    ? "+{$baseCurrencySymbol} " . number_format($unrealizedDiff, 2) . " (Gain)" 
                    : ($unrealizedDiff < 0 ? "-{$baseCurrencySymbol} " . number_format(abs($unrealizedDiff), 2) . " (Loss)" : "Balanced");

                $tooltip = "Actual Bank Balance: {$formattedNative}\n" .
                           "Official Spot Rate: 1 {$currencyCode} = {$baseCurrencySymbol} " . number_format($currentRate, 2) . "\n" .
                           "Live Market Value: {$baseCurrencySymbol} " . number_format($liveMarketValue, 2) . "\n" .
                           "Book Cost at Entry: {$baseCurrencySymbol} " . number_format($bookValue, 2) . "\n" .
                           "Exchange Difference: {$gainLossText}";

                $diffBadge = '';
                if (abs($unrealizedDiff) >= 0.01) {
                    $diffColor = $unrealizedDiff > 0 ? 'text-success' : 'text-danger';
                    $diffSign  = $unrealizedDiff > 0 ? '▲ +' : '▼ -';
                    $diffBadge = '<span class="' . $diffColor . '" style="font-size: 10px;">(' . $diffSign . number_format(abs($unrealizedDiff), 2) . ')</span>';
                }

                return '
                    <div class="text-end">
                        <span class="fw-bold text-dark">' . $formattedNative . '</span>
                        <div style="cursor: help;" data-bs-toggle="tooltip" data-bs-placement="left" title="' . e($tooltip) . '">
                            <small class="text-primary fw-semibold" style="font-size: 11px;">
                                ≈ ' . format_currency($liveMarketValue) . ' ' . $diffBadge . '
                                <i class="fa-solid fa-circle-info text-muted ms-1" style="font-size: 10px;"></i>
                            </small>
                        </div>
                        <small class="text-muted d-block" style="font-size: 10px;">Last: ' . $lastTxn . '</small>
                    </div>
                ';
            })

            ->addColumn('action', function ($row) use ($user) {
                $defaultAcc = Setting::get('default_acc');

                return $this->actionDropdown([
                    $this->editAction(
                        onclick: "editAccount('{$row->id}')",
                        permission: 'accounts_update',
                    ),
                    $this->divider(),
                    $this->deleteAction(
                        url: route('accounts.destroy', $row->id),
                        tableId: '#account-table',
                        item: $row->account_name,
                        name: 'Account',
                        permission: 'accounts_delete',
                        visible: $row->id != $defaultAcc,
                    )
                ]);
            });

        $this->applyAucitColumnLogic($dataTable);
        return $dataTable->rawColumns(['index', 'name', 'branch', 'details', 'opening_balance', 'current_balance', 'created_at', 'updated_at', 'action']);
    }

    /**
     * 🟢 Enterprise Branch Scoped Query Builder
     */
    public function query(Account $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['currency', 'branch'])
            ->where('is_system', false);

        // 🔒 ১. ব্রাঞ্চ এক্সেস পারমিশন গার্ড
        if (!user_can_access_all_branches()) {
            $permittedBranchIds = get_auth_permitted_branch_ids();
            $query->whereIn('branch_id', $permittedBranchIds);
        }

        // 🔍 ২. ব্রাঞ্চ ফিল্টারিং
        if (request()->filled('branch_id')) {
            $requestedBranchId = request('branch_id');
            if (user_can_access_all_branches() || in_array($requestedBranchId, get_auth_permitted_branch_ids())) {
                $query->where('branch_id', $requestedBranchId);
            }
        }

        return $query->orderBy('account_name', 'asc');
    }

    public function getColumns(): array
    {
        $nameTitle     = __('file.table.name');
        if (str_starts_with($nameTitle, 'file.')) $nameTitle = 'Account Name';

        $branchTitle   = __('file.table.branch');
        if (str_starts_with($branchTitle, 'file.')) $branchTitle = 'Branch';

        $detailsTitle  = __('file.table.details');
        if (str_starts_with($detailsTitle, 'file.')) $detailsTitle = 'Bank Details';

        $openingTitle  = __('file.table.opening_balance');
        if (str_starts_with($openingTitle, 'file.')) $openingTitle = 'Opening Balance';

        $currentTitle  = __('file.table.current_balance');
        if (str_starts_with($currentTitle, 'file.')) $currentTitle = 'Current Balance';

        $actionTitle   = __('file.table.action');
        if (str_starts_with($actionTitle, 'file.')) $actionTitle = 'Action';

        $columns = [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('name')->title($nameTitle)->orderable(false)->searchable(true),
            Column::make('branch')->title($branchTitle)->orderable(false)->searchable(true),
            Column::make('details')->title($detailsTitle)->orderable(false)->searchable(true),
            Column::make('opening_balance')->title('<div class="text-end">' . $openingTitle . '</div>')->addClass('text-end'),
            Column::make('current_balance')->title('<div class="text-end">' . $currentTitle . '</div>')->addClass('text-end'),
        ];

        $columns = array_merge($columns, $this->auditColumns());

        $columns[] = Column::make('action')
            ->title($actionTitle)
            ->orderable(false)
            ->searchable(false)
            ->addClass('text-end');

        return $columns;
    }

    protected function filename(): string
    {
        return 'Account_' . date('YmdHis');
    }
}