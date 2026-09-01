<?php

namespace App\DataTables;

use App\Enums\DrugType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Column;

class ProductDataTable extends BaseDataTable
{
    protected string $tableId = 'product-table';

    protected function getExportColumns(): array|string
    {
        return [2, 3, 4, 5, 6];
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        $user = auth()->user();

        return (new EloquentDataTable($query))
            ->addColumn('index', function ($row) {
                return '<input type="checkbox" class="row-checkbox mt-2" value="' . $row->id . '" />';
            })
            ->addColumn('image', function ($row) {
                return '<img style="height: 50px; width: 50px; object-fit: cover;" src="' . $row->thumb_url . '" class="rounded border shadow-sm" alt="' . e($row->name) . '" />';
            })
            ->addColumn('code', function ($row) {
                return '<div class="fw-bold text-dark mb-0 copy-trigger" data-copy="' . e($row->code) . '"  title="' . e($row->code) . '">#' . e($row->code) . '</div>';
            })
            ->editColumn('name', function ($row) {
                // English Comment: Basic badges and clickable name layout
                $typeBadge = $row->type ? "<span class='badge bg-light text-dark' style='font-size: 10px'>" . ucfirst($row->type) . "</span>" : '';
                $hasVariant = $row->has_variants ? "<span class='badge bg-info-transparent text-info ms-1' style='font-size: 10px'>has variants</span>" : '';

                $pharmacyPrefix = '';
                $genericLine = '';

                // English Comment: Check if pharmacy feature is active globally
                if (is_feature_active('pharmacy')) {

                    // 1. 🔥 English Comment: Dynamic Enum Shortname Resolution using your DrugType Class
                    if (!empty($row->drug_type)) {
                        // English Comment: tryFrom safely maps string to enum case, avoiding crashes if database value drifts
                        $pharmacyPrefix = '<span class="text-secondary fw-semibold me-1">' . e($row->drug_type->shortName()) . '.</span>';
                    }

                    // 2. English Comment: Fetch Generic Name via Relationship and place it directly below the name
                    if ($row->generic_id && $row->generic) {
                        $genericLine = '<div class="text-primary mt-0" style="font-size: 11px; font-weight: 500;" title="Generic Name">'
                            . e($row->generic->name)
                            . '</div>';
                    }
                }

                // English Comment: Combine shortname with the actual product brand name
                $nameHtml = '<div class="fw-bold text-dark mb-0" onclick="viewProduct(\'' . (string) $row->id . '\')" title="' . e($row->name) . '" style="cursor: pointer;">'
                    . $pharmacyPrefix . str($row->name)->limit(45)
                    . '</div>';

                // English Comment: Handle standard product specifications metadata
                $specs = $row->specifications->take(3)->map(function ($spec) {
                    return '<span class="text-muted" style="font-size: 10px;">' . e($spec->key) . ': ' . e($spec->value) . '</span>';
                })->implode('<span class="mx-1 text-silver">|</span>');

                $specHtml = $specs ? '<div class="d-flex align-items-center flex-wrap mt-0">' . $specs . '</div>' : '';

                // English Comment: Return final structured HTML content
                return $nameHtml . $genericLine . $specHtml . $typeBadge . $hasVariant;
            })
            ->editColumn('sku', function ($row) {
                return '<div class="fw-bold text-dark mb-0 copy-trigger" data-copy="' . e($row->sku) . '"  title="' . e($row->sku) . '">' . e($row->sku) . '</div>';
            })
            ->addColumn('categories', function ($row) {
                // If no categories are associated, return 'Uncategorized' badge
                if ($row->categories->isEmpty()) {
                    return '<span class="badge bg-light text-secondary border fw-normal" style="font-size: 10px;">' . __('Uncategorized') . '</span>';
                }

                // Return mapped categories as badges
                return $row->categories->map(function ($cat) {
                    return '<span class="badge bg-light text-secondary border fw-normal" style="font-size: 10px;">' . e($cat->name) . '</span>';
                })->implode(' ');
            })
            ->editColumn('brand', function ($row) {
                // Check if the brand exists through the relationship
                if ($row->brand && $row->brand->name) {
                    return e($row->brand->name);
                }

                // Return N/A or No Brand as a fallback
                return '<span class="text-muted">' . __('N/A') . '</span>';
            })
            ->editColumn('price', function ($row) use ($user) {
                // পারমিশন চেক (যদি প্রাইস দেখার পারমিশন না থাকে)
                if (!$user->can('products_view_price')) {
                    return '<div class="text-end">-</div>';
                }

                // প্রতিটা প্রাইসের জন্য ছোট লেবেলসহ কম্প্যাক্ট ডিজাইন
                $priceHtml = '<div class="text-end">';

                // ১. কস্ট প্রাইস (একটু ছোট এবং গ্রে কালার)
                if ($row->cost) {
                    $priceHtml .= '<div style="font-size: 11px;" class="text-muted">' . __('Cost') . ': ' . format_currency($row->cost) . '</div>';
                }

                // ২. মেইন প্রাইস (বোল্ড এবং বড়)
                $priceHtml .= '<div class="fw-bold text-dark" style="font-size: 14px;" title="Selling Price">' . format_currency($row->price) . '</div>';

                // ৩. হোলসেল প্রাইস (নীল বা অন্য কালার যাতে আলাদা বোঝা যায়)
                if ($row->wholesale_price) {
                    $priceHtml .= '<div style="font-size: 11px;" class="text-primary mt-1">' . __('Wholesale') . ': ' . format_currency($row->wholesale_price) . '</div>';
                }

                $priceHtml .= '</div>';

                return $priceHtml;
            })
            ->editColumn('stock', function ($row) {

                // If stock or unit details are missing, return N/A
                if (
                    !isset($row->total_stock) ||
                    empty($row->unit_details) ||
                    !is_array($row->unit_details)
                ) {
                    return '<div class="text-end">N/A</div>';
                }

                // Format stock safely
                $stock = format_stock_with_unit($row->total_stock, $row->unit_details);

                // Get base unit safely
                $baseUnit = $row->unit_details[$row->base_unit_id] ?? null;

                $baseUnitName = $baseUnit
                    ? ($baseUnit['short_name'] ?? $baseUnit['name'] ?? null)
                    : null;

                $baseUnitPrecision = $baseUnit['precision'] ?? 0;

                // Show base stock only if unit name exists
                $baseStockHtml = $baseUnitName
                    ? '<br><small class="text-muted">' . number_format((float) $row->total_stock, $baseUnitPrecision) . ' ' . $baseUnitName . '</small>'
                    : '';

                return '<div class="text-end">' . ($stock ?: 'N/A') . $baseStockHtml . '</div>';
            })
            ->editColumn('status', function ($row) {
                switch ($row->status) {
                    case 'active':
                        return '<span class="badge bg-success-transparent text-success"><i class="fas fa-check-circle me-1"></i> Active</span>';

                    case 'pending':
                        return '<span class="badge bg-warning-transparent text-warning"><i class="fas fa-clock me-1"></i> Pending</span>';

                    case 'incomplete':
                        return '<span class="badge bg-info-transparent text-info"><i class="fas fa-exclamation-circle me-1"></i> Incomplete</span>';

                    case 'draft':
                        return '<span class="badge bg-secondary-transparent text-secondary"><i class="fas fa-file-alt me-1"></i> Draft</span>';

                    case 'deactivated':
                        return '<span class="badge bg-danger-transparent text-danger"><i class="fas fa-ban me-1"></i> Deactivated</span>';

                    default:
                        return '<span class="badge bg-light text-dark">' . ucfirst($row->status) . '</span>';
                }
            })
            ->addColumn('action', function ($row) {
                return $this->actionDropdown([
                    $this->editAction(
                        onclick: "viewProduct('{$row->id}')",
                        permission: 'products_view',
                        label: __('file.view') . ' ' . __('file.product'),
                        icon: 'fa-solid fa-eye text-info',
                    ),
                    $this->linkAction(
                        label: __('file.update_variant'),
                        href: route('products.variants.manage', $row->id),
                        icon: 'fa-solid fa-copy text-secondary',
                        permission: 'products_create||products_update',
                        visible: $row->has_variants
                    ),
                    $this->linkAction(
                        label: __('file.opening_stock'),
                        href: route('products.openingStock.manage', $row->id),
                        icon: 'fa-solid fa-boxes-stacked text-secondary',
                        permission: 'products_update',
                        visible: $row->has_opening_stock
                    ),
                    $this->linkAction(
                        label: __('file.edit') . ' ' . __('file.product'),
                        href: route('products.edit', $row->id),
                        icon: 'fa-solid fa-pen-to-square text-primary',
                        permission: 'products_update',
                    ),
                    $this->divider(),

                    $this->deleteAction(
                        url: route('products.destroy', $row->id),
                        tableId: '#product-table',
                        item: $row->name,
                        name: 'Product',
                        permission: 'products_delete'
                    ),
                ],
                [
                    'button_text' => '<i class="fa-solid fa-gear me-1"></i> ' . __('Actions'),
                ]);
            })
            ->addColumn('custom_info', function ($row) {
                return $this->renderCustomInfoColumn($row);
            })
            ->rawColumns(['index', 'image', 'code', 'name', 'categories', 'brand', 'sku', 'stock', 'custom_info', 'status', 'action', 'price']);
    }

    public function query(Product $model): QueryBuilder
    {
        // Eager loading নিশ্চিত করা হয়েছে যাতে পারফরম্যান্স ভালো থাকে
        return $model->newQuery()->with(['categories', 'specifications', 'brand'])->orderBy('code', 'desc');
    }

    protected function getCustomButtons(): array
    {
        return [
            [
                'text' => '<i class="fa-solid fa-trash"></i><span class="bulk-count d-none">(0)</span>',
                'className' => 'btn btn-danger btn-bulk-delete me-1 disabled',
                'attr' => [
                    'id' => 'bulk-delete-btn',
                    'data-url' => route('products.bulk-delete'),
                ],
                'init' => 'function(dt, node, config){ $(node).attr("title", "Bulk Delete").tooltip({placement:"top"}); }'
            ]
        ];
    }

    public function getColumns(): array
    {
        $columns = [
            $this->indexColumn()->titleAttr('Select'),
            Column::make('image')->title(__('file.table.image'))->orderable(false)->searchable(false)->addClass('align-middle'),
            Column::make('code')->title(__('file.table.code'))->addClass('text-start align-middle'),
            Column::make('name')->title(__('file.table.name'))->addClass('align-middle'),
            Column::make('brand')->title(__('file.table.brand'))->data('brand')->name('brand.name')->addClass('align-middle'),
            Column::make('categories')->title(__('file.table.categories'))->addClass('align-middle'),
            Column::make('sku')->title(__('file.table.sku'))->addClass('align-middle'),
            Column::make('stock')->title('<div class="text-end">' . __('file.table.stock') . '</div>')->addClass(' align-middle')->orderable(false)->searchable(false),
            Column::make('pricing')->data('price')->name('price')
                ->title('<div class="text-end">' . __('file.table.price') . '</div>')
                ->addClass('text-end align-middle'),
            Column::make('status')->title('<div class="text-center">' . __('file.table.status') . '</div>')->addClass('align-middle text-center'),
        ];

        if ($this->hasVisibleCustomFields(Product::class)) {
            $columns[] = Column::make('custom_info')->data('custom_info')->name('customFieldValues.value')->title(__('Additional Info'))->orderable(false)->searchable(true)->addClass('align-middle');
        }

        return array_merge($columns, [
            ...$this->auditColumns(),
            Column::make('action')
                ->title('<div class="text-end">' . __('file.table.action') . '</div>')
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-end align-middle')
                ->responsivePriority(1),
        ]);
    }

    protected function filename(): string
    {
        return 'Product_' . date('YmdHis');
    }
}
