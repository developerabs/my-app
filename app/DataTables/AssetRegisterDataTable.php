<?php

namespace App\DataTables;

use App\Models\AssetRegister;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class AssetRegisterDataTable extends BaseDataTable
{
    protected string $tableId = 'asset-register-table';

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
            ->editColumn('register_no', function ($row) {
                return '<a href="javascript:void(0)" onclick="viewAssetRegister(\''.(string) $row->id.'\')" class="fw-bold text-primary text-decoration-none">'.e($row->register_no).'</a>';
            })
            ->editColumn('register_date', function ($row) {
                return formatDate($row->register_date);
            })
            ->editColumn('entry_type', function ($row) {
                $type  = ucfirst($row->entry_type->value ?? $row->entry_type);
                $color = ($row->entry_type->value ?? $row->entry_type) === 'purchase' ? 'success' : 'primary';

                return '<span class="badge bg-'.$color.'-subtle text-'.$color.' border border-'.$color.'">'.$type.'</span>';
            })
            ->addColumn('branch', function ($row) {
                return e($row->branch->name ?? 'N/A');
            })
            // 🟢 মাল্টি-কারেন্সি টোটাল কস্ট
            ->editColumn('total_cost', function ($row) use ($baseCurrencyCode) {
                $currencyCode    = $row->currency->code ?? $baseCurrencyCode;
                $formattedNative = format_currency($row->total_cost, $row->currency);

                $subtext = '';
                if ($currencyCode !== $baseCurrencyCode && (float) $row->base_total_cost > 0) {
                    $subtext = '<small class="text-muted d-block" style="font-size: 11px;">≈ '.format_currency($row->base_total_cost).'</small>';
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
                        'permission' => 'assets_register_manage',
                        'onclick'    => "viewAssetRegister('{$row->id}')",
                    ],
                    $this->divider(),
                    $this->deleteAction(
                        url: route('assets.register.destroy', $row->id),
                        tableId: '#asset-register-table',
                        item: $row->register_no,
                        name: 'Asset Register',
                        permission: 'assets_register_manage',
                    ),
                ]);
            });

        $dataTable = $this->applyAucitColumnLogic($dataTable);

        return $dataTable->rawColumns(['index', 'register_no', 'entry_type', 'total_cost', 'created_at', 'updated_at', 'action']);
    }

    public function query(AssetRegister $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['branch', 'currency', 'creator', 'updater']);

        // 🔒 ব্রাঞ্চ পারমিশন গার্ড
        if (! user_can_access_all_branches()) {
            $permittedBranchIds = get_auth_permitted_branch_ids();
            $query->whereIn('branch_id', $permittedBranchIds);
        }

        // 🔍 ব্রাঞ্চ ফিল্টারিং সাপোর্ট
        if (request()->filled('branch_id')) {
            $requestedBranchId = request('branch_id');
            if (user_can_access_all_branches() || in_array($requestedBranchId, get_auth_permitted_branch_ids())) {
                $query->where('branch_id', $requestedBranchId);
            }
        }

        return $query->latest('register_date')->latest('id');
    }

    public function getColumns(): array
    {
        return [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('register_no')->title(__('file.field.register_no') ?? 'Register No')->responsivePriority(1),
            Column::make('register_date')->title(__('file.field.date') ?? 'Date'),
            Column::make('entry_type')->title(__('file.field.entry_type') ?? 'Type'),
            Column::make('branch')->title(__('file.field.branch') ?? 'Branch'),
            Column::make('total_cost')->title('<div class="text-end">'.(__('file.field.total_cost') ?? 'Total Cost').'</div>')->addClass('text-end')->responsivePriority(2),
            ...$this->auditColumns(),
            Column::make('action')->title(__('file.table.action') ?? 'Actions')->addClass('text-end')->orderable(false)->searchable(false)->exportable(false)->printable(false)->responsivePriority(1),
        ];
    }

    protected function filename(): string
    {
        return 'Asset_Register_'.date('YmdHis');
    }
}