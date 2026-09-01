<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Map of Models for Dynamic Selects
    |--------------------------------------------------------------------------
    | This map is used in dropdowns to prevent exposing all system models 
    | and to ensure only allowed models can be processed.
    */
    'model_mappings' => [
        'customer' => \App\Models\Customer::class,
        'supplier' => \App\Models\Supplier::class,
        'product' => \App\Models\Product::class,
        'category' => \App\Models\Category::class,
        'brand' => \App\Models\Brand::class,
        'unit' => \App\Models\Unit::class,
        'tax' => \App\Models\Tax::class
        // Add more mappings as needed, but only for models that should be selectable in the UI

    ],
];