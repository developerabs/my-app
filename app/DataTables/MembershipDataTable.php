<?php

namespace App\DataTables;

use App\Models\Membership;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class MembershipDataTable extends BaseDataTable
{
    protected string $tableId = 'membership-table';

    protected function getExportColumns(): array|string
    {
        return [1, 2, 3, 4];
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Membership> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->addColumn('discount', function ($row) {
                if (!$row->discount_value || $row->discount_value <= 0) {
                    return '<span class="text-muted small">No Discount</span>';
                }

                // Determine the symbol based on type
                // English comment: Use percentage sign or currency symbol based on discount type
                $symbol = (strtolower($row->discount_type) === 'percentage') ? '%' : ' TK';

                // Format: "10%" or "500 TK"
                $displayValue = number_format($row->discount_value, ($symbol === '%' ? 0 : 2)) . $symbol;

                return '<span class="fw-bold text-dark">' . $displayValue . '</span> ' .
                    '<span class="text-muted small">(' . ucfirst($row->discount_type) . ')</span>';
            })
            ->addColumn('benefits', function ($row) {
                $benefits = '';
                // As the field is cast as an array in the model, we can directly iterate over it
                if (!empty($row->benefits) && is_array($row->benefits)) {
                    foreach ($row->benefits as $benefit) {
                        // Formatting the JSON keys into a readable format (e.g., free_shipping to Free Shipping)
                        $label = ucwords(str_replace('_', ' ', $benefit));

                        $benefits .= '<span class="badge bg-secondary me-1 mb-1">' . e($label) . '</span>';
                    }
                }

                return $benefits ?: '<span class="text-muted">No benefits</span>';
            })
            ->addColumn('status', function ($row) {
                if ($row->is_active) {
                    return '<span class="badge bg-success">' . __('file.option.active') . '</span>';
                } else {
                    return '<span class="badge bg-danger">' . __('file.option.inactive') . '</span>';
                }
            })
            ->addColumn('action', function ($row) {
                return $this->actionDropdown([
                    $this->editAction(
                        onclick: "editMembership('{$row->id}')",
                    ),
                    $this->divider(),
                    $this->deleteAction(
                        url: route('memberships.destroy', (string) $row->id),
                        tableId: '#membership-table',
                        item: $row->name,
                        name: 'Membership',
                    )
                ]);
            })
            ->rawColumns(['index', 'discount', 'benefits', 'status', 'action']);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Membership>
     */
    public function query(Membership $model): QueryBuilder
    {
        return $model->newQuery()->orderBy('name', 'asc');
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('name')->title(__('file.table.name')),
            Column::make('code')->title(__('file.table.code')),
            Column::make('discount')->title(__('file.table.discount'))->orderable(false)->searchable(false),
            Column::make('benefits')->title(__('file.table.benefits'))->orderable(false)->searchable(false),
            Column::make('status')->title(__('file.table.status'))->orderable(false)->searchable(false),
            Column::computed('action')->title(__('file.table.action'))->exportable(false)->printable(false)->addClass('text-end')->responsivePriority(1),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Membership_' . date('YmdHis');
    }
}
