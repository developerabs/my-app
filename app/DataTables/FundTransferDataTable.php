<?php

namespace App\DataTables;

use App\Models\FundTransfer;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class FundTransferDataTable extends BaseDataTable
{
    protected string $tableId = 'fund-transfer-table';

    protected function getExportColumns(): array|string
    {
        return [1, 2, 3, 4, 5, 6];
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $dataTable = (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->editColumn('transfer_no', function ($row) {
                return '<a href="javascript:void(0)" onclick="viewTransfer(\'' . (string) $row->id . '\')" class="fw-bold text-primary text-decoration-none">' . e($row->transfer_no) . '</a>';
            })
            ->editColumn('transfer_date', function ($row) {
                return formatDate($row->transfer_date);
            })
            ->addColumn('from_account', function ($row) {
                $accName = e($row->fromAccount->account_name ?? 'N/A');
                return '<span class="fw-semibold text-danger"><i class="fa-solid fa-arrow-up-from-bracket me-1"></i>' . $accName . '</span>';
            })
            ->addColumn('to_account', function ($row) {
                $accName = e($row->toAccount->account_name ?? 'N/A');
                return '<span class="fw-semibold text-success"><i class="fa-solid fa-arrow-down-to-bracket me-1"></i>' . $accName . '</span>';
            })
            ->editColumn('amount', function ($row) {
                return '<span class="fw-bold text-dark me-2">' . format_currency($row->amount) . '</span>';
            })
            ->addColumn('action', function ($row) {
                return $this->actionDropdown([
                    [
                        'type' => 'link',
                        'label' => __('file.view') ?? 'View Details',
                        'icon' => 'fa-solid fa-eye text-primary',
                        'permission' => 'acc_transfer_view',
                        'onclick' => "viewTransfer('{$row->id}')",
                    ],
                    [
                        'type' => 'link',
                        'label' => __('file.edit') ?? 'Edit Transfer',
                        'icon' => 'fa-solid fa-pen-to-square text-warning',
                        'permission' => 'acc_transfer_update',
                        'onclick' => "editTransfer('{$row->id}')",
                    ],
                    $this->divider(),
                    $this->deleteAction(
                        url: route('fund-transfers.destroy', $row->id),
                        tableId: '#fund-transfer-table',
                        item: $row->transfer_no,
                        name: 'Fund Transfer',
                        permission: 'acc_transfer_delete',
                    ),
                ]);
            });

        $dataTable = $this->applyAucitColumnLogic($dataTable);

        return $dataTable->rawColumns(['index', 'transfer_no', 'from_account', 'to_account', 'amount', 'created_at', 'updated_at', 'action']);
    }

    public function query(FundTransfer $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['fromAccount', 'toAccount', 'branch', 'currency', 'creator', 'updater'])
            ->latest('transfer_date')
            ->latest('id');
    }

    public function getColumns(): array
    {
        return [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('transfer_no')->title(__('Transfer No'))->responsivePriority(1),
            Column::make('transfer_date')->title(__('Date')),
            Column::make('from_account')->title(__('From (Source Account)')),
            Column::make('to_account')->title(__('To (Destination Account)')),
            Column::make('amount')->title('<div class="text-end">' . __('Amount') . '</div>')->addClass('text-end')->responsivePriority(2),
            ...$this->auditColumns(),
            Column::make('action')->title(__('Actions'))->addClass('text-end')->orderable(false)->searchable(false)->exportable(false)->printable(false)->responsivePriority(1),
        ];
    }

    protected function filename(): string
    {
        return 'FundTransfer_' . date('YmdHis');
    }
}