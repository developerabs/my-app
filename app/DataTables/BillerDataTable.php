<?php

namespace App\DataTables;

use App\Models\Biller;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Str;

class BillerDataTable extends BaseDataTable
{
    protected string $tableId = 'biller-table';


    protected function getExportColumns(): array|string
    {
        return [2, 3, 4];
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder<Biller> $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $dataTable = (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->addColumn('image', function ($row) {
                $url = $row->logo ? $row->logo_url : url('images/preview_image.png');
                return '<img src="' . $url . '" 
                 alt="' . e($row->name) . '" 
                 class="rounded-circle shadow-sm" 
                 style="width: 40px; height: 40px; object-fit: cover; border: 1px solid #ddd;">';
            })
            ->editColumn('name', function ($row) {
                // English: Keeping your existing logic but wrapping in a clickable div
                $displayName = '<strong>' . e($row->name) . '</strong>';

                $companyName = '';
                if (!empty($row->company_name)) {
                    $companyName = '<br><small class="text-primary fw-bold"><i class="fa-solid fa-building me-1"></i>' . e($row->company_name) . '</small>';
                }

                $addressInfo = '';
                if ($row->address) {
                    $addressInfo = '<br><small class="text-muted"><i class="fa-solid fa-location-dot me-1"></i>' . e(Str::limit($row->address, 40)) . '</small>';
                }

                $displayName .= $companyName . $addressInfo;

                // English: Wrapping all elements in a div with a cursor pointer and onclick event
                return '<div style="cursor: pointer;">'
                    . $displayName .
                    '</div>';
            })
            ->addColumn('contacts', function ($row) {
                $email = $row->email ? '<a href="mailto:' . e($row->email) . '"><i class="fa-solid fa-envelope"></i> ' . e($row->email) . '</a> <span class="copy-trigger" data-copy="' . e($row->email) . '"><i class="fa-solid fa-copy"></i></span>' : '';
                $phone = $row->phone ? '<a href="tel:' . e($row->phone) . '"><i class="fa-solid fa-phone"></i> ' . e($row->phone) . '</a> <span class="copy-trigger" data-copy="' . e($row->phone) . '"><i class="fa-solid fa-copy"></i></span>' : '';
                $websiteUrl = $row->website_url ? '<a href="' . e($row->website_url) . '" target="_blank"><i class="fa-solid fa-link"></i> ' . e($row->website_url) . '</a>' : '';
                return  '<div class="d-flex flex-column align-items-start gap-1"><span>' . $phone . '</span><span>' . $email . '</span><span>' . $websiteUrl . '</span></div>';
            })
            ->editColumn('certificate', function ($row) {
                return $row->certificate ? '<a href="' . $row->certificate_url . '" target="_blank" class="text-primary">' . __('View') . '</a>' : 'N/A';
            })
            ->addColumn('action', function ($row) {
                return $this->actionDropdown([
                    $this->editAction(
                        onclick: "editBiller('{$row->id}')",
                        permission: 'billers_update',
                    ),
                    $this->divider(),
                    $this->deleteAction(
                        url: route('billers.destroy', $row->id),
                        tableId: '#biller-table',
                        item: $row->name,
                        name: 'Biller',
                        permission: 'billers_delete',
                    )
                ],
                [
                    'button_text' => '<i class="fa-solid fa-gear me-1"></i> ' . __('Actions'),
                ]);
            })
            ->addColumn('custom_info', function ($row) {
                return $this->renderCustomInfoColumn($row);
            });

        $dataTable = $this->applyCustomFieldFilter($dataTable, Biller::class);

        return $dataTable->rawColumns(['index', 'image', 'name', 'contacts', 'action', 'certificate', 'custom_info']);
    }

    /**
     * Get the query source of dataTable.
     *
     * @return QueryBuilder<Biller>
     */
    public function query(Biller $model): QueryBuilder
    {
        return $model->newQuery();
    }


    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        $columns = [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('image')->title(__('file.table.image'))->orderable(false)->searchable(false),
            Column::make('name')->title(__('file.table.name')),
            Column::make('propiter_name')->title(__('file.table.propiter_name')),
            Column::make('contacts')->title(__('file.table.contacts'))->orderable(false)->searchable(true),
        ];

        // English: Dynamic column inclusion using the BaseDataTable helper
        if ($this->hasVisibleCustomFields(Biller::class)) {
            $columns[] = Column::make('custom_info')->data('custom_info')->name('customFieldValues.value')->title(__('Additional Info'))->orderable(false)->searchable(true);
        }

        $columns = array_merge(
            $columns,
            [
                Column::make('bin')->title(__('file.table.bin_number'))->orderable(false)->searchable(false),
                Column::make('certificate')->title(__('file.table.certificate'))->orderable(false)->searchable(false),
            ],
            $this->auditColumns(),
            [
                Column::make('action')->title(__('file.table.action'))->orderable(false)->searchable(false)->exportable(false)->printable(false)->addClass('text-end')->responsivePriority(1),
            ]
        );

        return $columns;
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Biller_' . date('YmdHis');
    }
}
