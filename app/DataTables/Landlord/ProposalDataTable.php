<?php

namespace App\DataTables\Landlord;

use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use App\Models\landlord\Proposal;

class ProposalDataTable extends DataTable
{
   public function dataTable(QueryBuilder $query): EloquentDataTable
{
    return (new EloquentDataTable($query))
        ->addIndexColumn()
          ->editColumn('proposal_number', function($row) {
            return $row->proposal_number ?? 'N/A';
        })
          ->editColumn('company_name', function($row) {
            return $row->company_name ?? 'N/A';
        })
         ->editColumn('fees', function($row) {
            return "Reg. Fee: {$row->registration_fee}<br>Monthly Fee: {$row->monthly}<br>Yearly Fee: {$row->yearly}<br>Lifetime Fee: {$row->lifetime}";
        })
        ->editColumn('customer_details', function($row) {
            return $row->customer_name . '<br>' . $row->customer_email . '<br>' . $row->customer_phone;
        })
      ->addColumn('package', function($row) {
            return $row->packageInfo?->name ?? 'N/A';
        })
          ->addColumn('action', function ($row) {
                $editUrl   = route('landlord.proposal.edit', $row->id);
                $deleteUrl = route('landlord.proposals.destroy', $row->id);
                $viewUrl   = route('landlord.proposals.view', $row->id);

                return '
                <div class="dropdown text-start">
                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        Action
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="'.$viewUrl.'" class="dropdown-item"> <i class="fas fa-eye"></i> View</a>
                        </li>
                        <li>
                            <a href="'.$editUrl.'" class="dropdown-item"><i class="fas fa-edit"></i> Edit </a>
                        </li>
                        <li>
                            <a href="javascript:void(0);" onclick="deleteProposal('.$row->id.')" class="dropdown-item"> <i class="fas fa-trash-alt"></i> Delete</a>
                        </li>
                    </ul>
                </div>';
            })
        ->rawColumns(['action','company_name', 'fees', 'package', 'customer_details']);
}

    public function query(Proposal $model): QueryBuilder
    {
        $query = $model->newQuery();
        if ($search = request('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('proposal_number', 'like', "%{$search}%")
                ->orWhere('company_name', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('customer_email', 'like', "%{$search}%")
                ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('proposals-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->parameters([
                'scrollY' => '70vh',
                'scrollCollapse' => true,
                'fixedHeader' => [
                    'header' => true,
                ],
                'responsive' => true,
                'autoWidth' => false,
                'order' => [[1, 'asc']],
                'dom' => "<'row mb-3'<'col-md-2 d-flex align-items-center d-none d-md-block'l><'col-md-4 order-3 order-md-2 d-flex align-items-center justify-content-center'f><'col-md-6 order-2 order-md-3 d-flex align-items-center justify-content-end text-end'B>>"
                        . "<'row'<'col-12'tr>>"
                        . "<'row mt-3'<'col-12 col-md-5'i><'col-12 col-md-7 d-flex justify-content-center justify-content-md-end'p>>",
                'buttons' => [
                    ['extend' => 'excel', 'className' => 'btn btn-success me-2', 'text' => '<i class="fa-solid fa-file-excel"></i>'],
                    ['extend' => 'csv', 'className' => 'btn btn-info me-2', 'text' => '<i class="fa-solid fa-file-csv"></i>'],
                    ['extend' => 'pdf', 'className' => 'btn btn-danger me-2', 'text' => '<i class="fa-solid fa-file-pdf"></i>'],
                    ['extend' => 'print', 'className' => 'btn btn-primary me-2', 'text' => '<i class="fa-solid fa-print"></i>'],
                    ['text' => '<i class="fa-solid fa-rotate-left"></i>', 'className' => 'btn btn-secondary me-2', 'action' => 'function ( e, dt, node, config ) { dt.search("").columns().search("").draw(); }'],
                    ['text' => '<i class="fa-solid fa-arrows-rotate"></i>', 'className' => 'btn btn-warning', 'action' => 'function ( e, dt, node, config ) { dt.ajax.reload(); }']
                ],
                'language' => [
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
                ],
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::make('DT_RowIndex')->title('#')->addClass('text-start')->orderable(false)->searchable(false)->exportable(false)->printable(false)->responsivePriority(1),
            Column::make('proposal_number')->title(__('file.table.proposal_number'))->addClass('text-start')->orderable(false)->searchable(false)->responsivePriority(1),
            Column::make('company_name')->title(__('file.table.company_name'))->addClass('text-start')->orderable(false)->searchable(false)->responsivePriority(2),
            Column::computed('customer_details')->title(__('file.table.customer_details'))->addClass('text-start')->orderable(false)->searchable(false)->responsivePriority(3),
            Column::make('package')->title(__('file.table.package'))->addClass('text-start')->orderable(false)->searchable(false)->responsivePriority(4),
            Column::computed('fees')->title(__('file.table.fees'))->addClass('text-start')->orderable(false)->searchable(false)->responsivePriority(5),
            Column::computed('action')->title(__('file.table.action'))->addClass('text-center')->orderable(false)->searchable(false)->exportable(false)->printable(false)->responsivePriority(1),
        ];
    }

    protected function filename(): string
    {
        return 'Proposal_' . date('YmdHis');
    }
}

