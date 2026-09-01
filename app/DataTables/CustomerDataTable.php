<?php

namespace App\DataTables;

use App\Models\Customer;
use App\Services\CurrencyConversionService;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class CustomerDataTable extends BaseDataTable
{
    protected string $tableId = 'customer-table';

    protected function getExportColumns(): array|string
    {
        return [1, 2, 3, 4];
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $currencyService  = app(CurrencyConversionService::class);
        $defaultCurrency  = view()->shared('default_currency') ?? [];
        $baseCurrencyCode = $defaultCurrency['code'] ?? 'BDT';

        // ইউজারের কারেন্ট ব্রাঞ্চ বা প্রাইমারি ব্রাঞ্চ কারেন্সি নির্ধারণ
        $userBranchId = session('branch_id') ?? (auth()->user()->branch_id ?? null);
        $userBranch   = $userBranchId ? \App\Models\Branch::with('currency')->find($userBranchId) : null;
        $userCurrency = $userBranch?->currency ?? null;

        $dataTable = (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="'.$row->id.'" />';
            })
            ->addColumn('image', function ($row) {
                $url = $row->details ? $row->details->image_url : url('images/preview_image.png');
                return '<img src="'.$url.'" 
                 alt="'.e($row->name).'" 
                 class="rounded-circle shadow-sm" 
                 style="width: 40px; height: 40px; object-fit: cover; border: 1px solid #ddd;">';
            })
            ->editColumn('name', function ($row) {
                $displayName = '<strong>'.e($row->name).'</strong>';
                $companyName = '';
                if (! empty($row->details->company_name)) {
                    $companyName = '<br><small class="text-primary fw-bold"><i class="fa-solid fa-building me-1"></i>'.e($row->details->company_name).'</small>';
                }

                $addressInfo = '';
                if ($row->primaryAddress && ! empty($row->primaryAddress->full_address)) {
                    $addressInfo = '<br><small class="text-muted"><i class="fa-solid fa-location-dot me-1"></i>'.e(Str::limit($row->primaryAddress->full_address, 40)).'</small>';
                }

                return '<div onclick="viewCustomer(\''.(string) $row->id.'\')" style="cursor: pointer;" title="Click to view details">'
                    .$displayName.$companyName.$addressInfo.
                    '</div>';
            })
            ->addColumn('contacts', function ($row) {
                $email = $row->email ? '<a href="mailto:'.e($row->email).'"><i class="fa-solid fa-envelope"></i> '.e($row->email).'</a> <span class="copy-trigger" data-copy="'.e($row->email).'"><i class="fa-solid fa-copy"></i></span>' : '';
                $phone = $row->phone ? '<a href="tel:'.e($row->phone).'"><i class="fa-solid fa-phone"></i> '.e($row->phone).'</a> <span class="copy-trigger" data-copy="'.e($row->phone).'"><i class="fa-solid fa-copy"></i></span>' : '';
                return '<div class="d-flex flex-column align-items-start gap-2"><span>'.$phone.'</span><span>'.$email.'</span></div>';
            })
            ->editColumn('customer_group', function ($row) {
                if ($row->customerGroup) {
                    return '<span class="badge bg-primary">'.e($row->customerGroup->name).'</span>';
                }
                return '<span class="text-muted">N/A</span>';
            })
            ->editColumn('points', function ($row) {
                return '<span class="text-end">'.number_format($row->total_points ?? 0, 2).'</span>';
            })
            // 🟢 কাস্টমার মাল্টি-কারেন্সি ব্যালেন্স ডিসপ্লে
            ->addColumn('balance', function ($row) use ($userCurrency, $baseCurrencyCode, $currencyService) {
                $currentBaseBalance = (float) (($row->total_base_debit ?? 0) - ($row->total_base_credit ?? 0));
                $openingBaseBalance = (float) ($row->opening_balance ?? 0);

                $balanceClass = $currentBaseBalance > 0 ? 'text-success' : ($currentBaseBalance < 0 ? 'text-danger' : 'text-muted');

                $foreignSubtext = '';
                // যদি ইউজার কোনো ফরেন কারেন্সি ব্রাঞ্চে থাকেন (যেমন: USA Branch = USD)
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
            ->filterColumn('contacts', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('phone', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('name', function ($query, $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhereHas('details', function ($sub) use ($keyword) {
                            $sub->where('company_name', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('addresses', function ($sub) use ($keyword) {
                            $sub->where('full_address', 'like', "%{$keyword}%")
                                ->orWhere('district', 'like', "%{$keyword}%")
                                ->orWhere('upazila', 'like', "%{$keyword}%");
                        });
                });
            })
            ->filterColumn('customer_group', function ($query, $keyword) {
                $query->whereHas('customerGroup', function ($sub) use ($keyword) {
                    $sub->where('name', 'like', "%{$keyword}%");
                });
            })
            ->addColumn('action', function ($row) {
                return $this->actionDropdown([
                    $this->editAction(
                        onclick: "editCustomer('{$row->id}')",
                        permission: 'customers_update',
                    ),
                    $this->divider(),
                    $this->deleteAction(
                        url: route('customers.destroy', $row->id),
                        tableId: '#customer-table',
                        item: $row->name,
                        name: 'Customer',
                        permission: 'customers_delete',
                    ),
                ]);
            })
            ->addColumn('custom_info', function ($row) {
                return $this->renderCustomInfoColumn($row);
            });

        $dataTable = $this->applyCustomFieldFilter($dataTable, Customer::class);

        return $dataTable->rawColumns(['index', 'image', 'name', 'contacts', 'customer_group', 'custom_info', 'points', 'balance', 'action']);
    }

    public function query(Customer $model): QueryBuilder
    {
        $permittedBranchIds = ! user_can_access_all_branches() ? get_auth_permitted_branch_ids() : [];

        // 🔒 জেনারেল লেজার সাব-কুয়েরিতে ব্রাঞ্চ ফিল্টারিং প্রয়োগ
        $debitSubQuery = DB::table('general_ledgers')
            ->whereRaw('general_ledgers.sub_ledger_id::text = customers.id::text')
            ->where('sub_ledger_type', Customer::class)
            ->whereIn('status', ['posted', 'reversed']);

        $creditSubQuery = DB::table('general_ledgers')
            ->whereRaw('general_ledgers.sub_ledger_id::text = customers.id::text')
            ->where('sub_ledger_type', Customer::class)
            ->whereIn('status', ['posted', 'reversed']);

        if (! user_can_access_all_branches()) {
            $debitSubQuery->whereIn('branch_id', $permittedBranchIds);
            $creditSubQuery->whereIn('branch_id', $permittedBranchIds);
        }

        $query = $model->newQuery()
            ->select('customers.*')
            ->with(['details', 'primaryAddress', 'customerGroup', 'customFieldValues.customField'])
            ->addSelect([
                'total_base_debit'  => $debitSubQuery->select(DB::raw('COALESCE(sum(base_debit), 0)')),
                'total_base_credit' => $creditSubQuery->select(DB::raw('COALESCE(sum(base_credit), 0)')),
            ]);

        if (request()->filled('status')) {
            $query->where('is_active', request('status'));
        }
        if (request()->filled('group_id')) {
            $query->where('customer_group_id', request('group_id'));
        }

        return $query->orderBy('name', 'asc');
    }

    protected function getAjaxParams(): array
    {
        return [
            'status'   => '$("#filter-status").val()',
            'group_id' => '$("#filter-group").val()',
        ];
    }

    public function getColumns(): array
    {
        $columns = [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('image')->title(__('file.table.image'))->orderable(false)->searchable(false),
            Column::make('name')->title(__('file.table.name')),
            Column::make('contacts')->title(__('file.table.contacts'))->orderable(false)->searchable(true),
        ];

        if ($this->hasVisibleCustomFields(Customer::class)) {
            $columns[] = Column::make('custom_info')->data('custom_info')->name('customFieldValues.value')->title(__('Additional Info'))->orderable(false)->searchable(true);
        }

        $columns = array_merge($columns, [
            Column::make('customer_group')->title(__('file.table.customer_group'))->orderable(false)->searchable(false),
            Column::make('points')->title(__('file.table.points'))->addClass('text-start')->orderable(false)->searchable(false),
            Column::make('balance')->title('<div class="text-end">'.(__('file.table.balance') ?? 'Balance').'</div>')->addClass('text-end')->orderable(false)->searchable(false),
        ],
            $this->auditColumns(),
            [
                Column::make('action')->title(__('file.table.action'))->orderable(false)->searchable(false)->exportable(false)->printable(false)->addClass('text-end')->responsivePriority(1),
            ]);

        return $columns;
    }

    protected function filename(): string
    {
        return 'Customer_'.date('YmdHis');
    }
}