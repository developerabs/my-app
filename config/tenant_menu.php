<?php

return [
    [
        'id' => 'dashboard',
        'title' => 'file.menu.Dashboard',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M5 5h4v6H5zm10 8h4v6h-4zM5 17h4v2H5zM15 5h4v2h-4z" opacity=".3"/><path d="M3 13h8V3H3v10zm2-8h4v6H5V5zm8 16h8V11h-8v10zm2-8h4v6h-4v-6zM13 3v6h8V3h-8zm6 4h-4V5h4v2zM3 21h8v-6H3v6zm2-4h4v2H5v-2z"/></svg>',
        'route' => 'dashboard',
    ],
    [
        'id' => 'categories',
        'title' => 'file.menu.categories',
        'icon' => '<svg class="side-menu__icon" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M174.9 272c10.7 0 20.7 5.3 26.6 14.2l11.8 17.8 26.7 0c26.5 0 48 21.5 48 48l0 112c0 26.5-21.5 48-48 48L48 512c-26.5 0-48-21.5-48-48L0 352c0-26.5 21.5-48 48-48l26.7 0 11.8-17.8c5.9-8.9 15.9-14.2 26.6-14.2l61.7 0zm278.6-12c5.6-4.9 13.9-5.3 19.9-.9s8.3 12.4 5.3 19.3L440.3 368 496 368c6.7 0 12.6 4.1 15 10.4s.6 13.3-4.4 17.7l-128 112c-5.6 4.9-13.9 5.3-19.9 .9s-8.3-12.4-5.3-19.3l38.5-89.7-55.8 0c-6.7 0-12.6-4.1-15-10.4s-.6-13.3 4.4-17.7l128-112zM144 360a48 48 0 1 0 0 96 48 48 0 1 0 0-96zM483.8 .4c6.5-1.1 13.1 .4 18.5 4.4 6.1 4.5 9.7 11.7 9.7 19.2l0 152-.3 4.9c-3.3 24.2-30.5 43.1-63.7 43.1-35.3 0-64-21.5-64-48s28.7-48 64-48c5.5 0 10.9 .6 16 1.6l0-49.3-112 33.6 0 110.2-.3 4.9c-3.3 24.2-30.5 43.1-63.7 43.1-35.3 0-64-21.5-64-48s28.7-48 64-48c5.5 0 10.9 .6 16 1.6L304 72c0-10.6 7-20 17.1-23l160-48 2.7-.6zM188.9 0C226 0 256 30 256 67.1l0 6.1c0 56.1-75.2 112.1-110.3 135.3-10.8 7.1-24.6 7.1-35.4 0-35.1-23.1-110.3-79.2-110.3-135.3l0-6.1C0 30 30 0 67.1 0 88.2 0 108 9.9 120.7 26.8l7.3 9.8 7.3-9.8C148 9.9 167.8 0 188.9 0z"/></svg>',
        'route' => 'categories.index',
        'feature' => 'categories_active',
        'permission' => ['category_view', 'category_create', 'category_update'],
    ],
    [
        'id' => 'products_menu',
        'title' => 'file.menu.products',
        'icon' => '<svg class="side-menu__icon" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M284-1.3c-17.3-10-38.7-10-56 0L143.8 47.3c-17.3 10-28 28.5-28 48.5l0 101.9-88.3 51c-17.3 10-28 28.5-28 48.5l0 97.3c0 20 10.7 38.5 28 48.5l84.3 48.6c17.3 10 38.7 10 56 0l88.3-51 88.3 51c17.3 10 38.7 10 56 0L484.5 443c17.3-10 28-28.5 28-48.5l0-97.3c0-20-10.7-38.5-28-48.5l-88.3-51 0-101.9c0-20-10.7-38.5-28-48.5L284-1.3zM232 292.6l0 106.5-88.3 51c-1.2 .7-2.6 1.1-4 1.1l0-105.3 92.3-53.3zm231.4 .6c.7 1.2 1.1 2.6 1.1 4l0 97.3c0 2.9-1.5 5.5-4 6.9l-84.3 48.6c-1.2 .7-2.6 1.1-4 1.1l0-105.3 91.2-52.6zM348.3 95.8l0 101.9-92.3 53.3 0-106.5 91.2-52.6c.7 1.2 1.1 2.6 1.1 4z"/></svg>',
        'sub_menu' => [
            [
                'id' => 'add_new_product',
                'title' => 'file.menu.add_new_product',
                'route' => 'products.create',
                'feature' => 'products_active',
                'permission' => ['products_create'],
            ],
            [
                'id' => 'products',
                'title' => 'file.menu.products',
                'route' => 'products.index',
                'feature' => 'products_active',
                'permission' => ['products_view', 'products_create', 'products_update'],
            ],
            [
                'id' => 'manage_attributes',
                'title' => 'file.menu.manage_attributes',
                'route' => 'attributes.index',
                'feature' => 'products_active',
                'permission' => ['manage_attributes'],
            ],
            [
                'id' => 'generics',
                'title' => 'file.menu.generics',
                'route' => 'generics.index',
                'feature' => 'pharmacy_active',
                'permission' => ['products_generic_manage'],
            ],
            [
                'id' => 'brands',
                'title' => 'file.menu.brands',
                'route' => 'brands.index',
                'feature' => 'products_active',
                'permission' => ['brands_view', 'brands_create', 'brands_update'],
            ],
            [
                'id' => 'units',
                'title' => 'file.menu.units',
                'route' => 'units.index',
                'feature' => 'products_active',
                'permission' => ['units_view', 'units_create', 'units_update'],
            ],
            [
                'id' => 'unit_groups',
                'title' => 'file.menu.unit_groups',
                'route' => 'unit-groups.index',
                'feature' => 'products_active',
                'permission' => ['unit_view', 'unit_create', 'unit_update'],
            ],
        ],
    ],
    [
        'id' => 'purchases_menu',
        'title' => 'file.menu.purchases',
        'icon' => '<svg fill="currentColor" class="side-menu__icon" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.2.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M0 72C0 58.7 10.7 48 24 48L69.3 48C96.4 48 119.6 67.4 124.4 94L124.8 96L312 96L312 198.1L281 167.1C271.6 157.7 256.4 157.7 247.1 167.1C237.8 176.5 237.7 191.7 247.1 201L319.1 273C328.5 282.4 343.7 282.4 353 273L425 201C434.4 191.6 434.4 176.4 425 167.1C415.6 157.8 400.4 157.7 391.1 167.1L360.1 198.1L360.1 96L537.5 96C557.5 96 572.6 114.2 568.9 133.9L537.8 299.8C532.1 330.1 505.7 352 474.9 352L171.3 352L176.4 380.3C178.5 391.7 188.4 400 200 400L456 400C469.3 400 480 410.7 480 424C480 437.3 469.3 448 456 448L200.1 448C165.3 448 135.5 423.1 129.3 388.9L77.2 102.6C76.5 98.8 73.2 96 69.3 96L24 96C10.7 96 0 85.3 0 72zM160 528C160 501.5 181.5 480 208 480C234.5 480 256 501.5 256 528C256 554.5 234.5 576 208 576C181.5 576 160 554.5 160 528zM384 528C384 501.5 405.5 480 432 480C458.5 480 480 501.5 480 528C480 554.5 458.5 576 432 576C405.5 576 384 554.5 384 528z"/></svg>',
        'sub_menu' => [
            [
                'id' => 'add_purchase',
                'title' => 'file.menu.add_purchase',
                'route' => 'purchases.create',
                'feature' => 'purchases_active',
                'permission' => ['purchases_create'],
            ],
            [
                'id' => 'purchases',
                'title' => 'file.menu.purchase_list',
                'route' => 'purchases.index',
                'feature' => 'purchases_active',
                'permission' => ['purchases_view'],
            ]
        ],
    ],
    [
        'id' => 'expenses_menu',
        'title' => 'file.menu.bill_n_expense',
        'icon' => '<svg fill="currentColor" class="side-menu__icon" width="24px" height="24px" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
	 viewBox="0 0 489.003 489.003" xml:space="preserve">
<g>
	<path d="M350.902,253.314c-9.3,5.4-24.4,5.4-33.8,0c-9.3-5.4-9.4-14.2-0.1-19.6c9.3-5.4,24.4-5.4,33.8,0
		C360.202,239.114,360.302,247.914,350.902,253.314z M172.802,130.914c-9.4-5.4-24.5-5.4-33.8,0c-9.3,5.4-9.2,14.2,0.1,19.6
		c9.4,5.4,24.5,5.4,33.8,0C182.202,145.114,182.102,136.314,172.802,130.914z M477.602,243.714l-142.8,83c-15.3,8.9-40.3,8.9-55.7,0
		l-266.6-153.9c-15.4-8.9-15.5-23.4-0.2-32.3l142.8-83c15.3-8.9,40.3-8.9,55.7,0l266.5,153.9
		C492.802,220.314,492.902,234.814,477.602,243.714z M423.702,247.314c-2.8-1-5.5-2.1-7.9-3.5c-15.6-9-15.7-23.6-0.2-32.7
		c2.4-1.4,4.9-2.5,7.7-3.5l-207.5-119.8c-1.3,1.2-2.9,2.3-4.7,3.3c-15.5,9-40.8,9-56.4,0c-1.7-1-3.2-2-4.5-3.1l-85.3,49.6
		c1.9,0.8,3.7,1.6,5.4,2.6c15.6,9,15.7,23.6,0.2,32.6c-1.8,1-3.7,1.9-5.7,2.7l206.9,119.5c1-0.7,2.1-1.5,3.3-2.2
		c15.5-9,40.7-9,56.4,0c2.4,1.4,4.4,2.9,6.1,4.5L423.702,247.314z M5.002,217.714l279.2,161.2l3.9,2.3l5.6,3.2
		c7.9,4.6,20.6,4.6,28.5,0l160.1-93c6.5-3.8,6.5-10-0.1-13.8s-17.2-3.8-23.8,0l-150.6,87.5l-278.5-160.8c-6.5-3.7-17-4.3-23.8-0.8
		C-1.698,207.314-1.798,213.814,5.002,217.714z M482.502,327.914c-6.6-3.8-17.2-3.8-23.8,0l-150.6,87.6l-278.5-160.9
		c-6.5-3.7-17-4.3-23.8-0.8c-7.2,3.8-7.4,10.2-0.6,14.1l279.3,161.3l4,2.3l5.6,3.2c7.9,4.6,20.6,4.6,28.5,0l160.1-93
		C489.202,337.914,489.102,331.714,482.502,327.914z M305.802,227.214c-33.5,19.5-87.9,19.6-121.6,0.1
		c-33.7-19.4-33.7-50.9-0.2-70.4s87.9-19.6,121.6-0.1C339.302,176.214,339.302,207.814,305.802,227.214z M297.002,186.614l-0.6-1.1
		c-3.1-4-7.1-7.6-12.3-10.6c-2-1.2-4.2-2.2-6.5-3.2c-6-2.5-12.6-4.3-19.9-4.8c-9.3-0.7-18.1,0.5-26.3,3l-1,0.3l-7.4-4.3
		c-1.2-0.7-3.1-0.7-4.2,0l-4.7,2.7c-1.2,0.7-1.2,1.8,0,2.5l5.8,3.3c-1.3,0.7-2.6,1.5-3.8,2.2l-5.8-3.3c-1.2-0.7-3.1-0.7-4.2,0
		l-4.7,2.7c-1.2,0.7-1.2,1.8,0,2.5l7,4c-1.2,1.3-2.5,2.5-3.5,3.9c-4.9,7-5,13.9,1,20.8c2.6,2.9,5.8,5.4,9.5,7.6
		c3.6,2.1,7.8,3.9,12.4,5.4c2.5,0.8,5.1,1.5,7.7,2.1c2.6,0.5,4.4,0,5.4-1.4c1.2-1.5,2.3-3.1,3.2-4.7c0.7-1.1,0.5-1.8-0.6-2.5
		c-0.3-0.2-0.7-0.4-1.2-0.5c-2.9-1-5.8-1.9-8.6-3c-2.5-1-4.8-2-6.9-3.2c-2.4-1.4-4.4-2.9-6-4.7c-3.8-4.2-2.7-8.2,0.6-12.2l14.9,8.6
		c1.2,0.7,3.1,0.7,4.2,0l4.7-2.7c1.2-0.7,1.2-1.8,0-2.5l-16.4-9.4c1.3-0.8,2.6-1.5,3.8-2.2l16.4,9.4c1.2,0.7,3.1,0.7,4.2,0l4.7-2.7
		c1.2-0.7,1.2-1.8,0-2.5l-14.3-8.2l0.2-0.1c5.6-1.3,11.2-1.7,17-0.2c3.8,0.9,7,2.3,9.8,3.9c2.1,1.2,4,2.6,5.6,4.1
		c1.9,1.6,3.5,3.4,5.3,5c0.3,0.2,0.5,0.4,0.8,0.6c1.1,0.7,2.5,0.8,4.3,0.4c2.7-0.6,5.3-1.2,7.9-1.9
		C296.902,189.114,297.702,188.114,297.002,186.614z"/>
</g>
</svg>',
        'sub_menu' => [
            [
                'id' => 'expneses',
                'title' => 'file.menu.expenses',
                'route' => 'expenses.index',
                'feature' => 'expenses_active',
                'permission' => ['expenses_view', 'expenses_create', 'expenses_update'],
            ],
            [
                'id' => 'bills',
                'title' => 'file.menu.bills',
                'route' => 'bills.index',
                'feature' => 'bills_active',
                'permission' => ['bill_view', 'bill_create', 'bill_update', 'bill_delete'],
            ],
        ],
    ],
    [
        'id' => 'accounting_menu',
        'title' => 'file.menu.accounting',
        'icon' => '<svg fill="currentColor" class="side-menu__icon" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.3.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M192 64C156.7 64 128 92.7 128 128L128 512C128 547.3 156.7 576 192 576L448 576C483.3 576 512 547.3 512 512L512 128C512 92.7 483.3 64 448 64L192 64zM224 128L416 128C433.7 128 448 142.3 448 160L448 192C448 209.7 433.7 224 416 224L224 224C206.3 224 192 209.7 192 192L192 160C192 142.3 206.3 128 224 128zM240 296C240 309.3 229.3 320 216 320C202.7 320 192 309.3 192 296C192 282.7 202.7 272 216 272C229.3 272 240 282.7 240 296zM320 320C306.7 320 296 309.3 296 296C296 282.7 306.7 272 320 272C333.3 272 344 282.7 344 296C344 309.3 333.3 320 320 320zM448 296C448 309.3 437.3 320 424 320C410.7 320 400 309.3 400 296C400 282.7 410.7 272 424 272C437.3 272 448 282.7 448 296zM216 416C202.7 416 192 405.3 192 392C192 378.7 202.7 368 216 368C229.3 368 240 378.7 240 392C240 405.3 229.3 416 216 416zM344 392C344 405.3 333.3 416 320 416C306.7 416 296 405.3 296 392C296 378.7 306.7 368 320 368C333.3 368 344 378.7 344 392zM424 416C410.7 416 400 405.3 400 392C400 378.7 410.7 368 424 368C437.3 368 448 378.7 448 392C448 405.3 437.3 416 424 416zM192 488C192 474.7 202.7 464 216 464L328 464C341.3 464 352 474.7 352 488C352 501.3 341.3 512 328 512L216 512C202.7 512 192 501.3 192 488zM424 464C437.3 464 448 474.7 448 488C448 501.3 437.3 512 424 512C410.7 512 400 501.3 400 488C400 474.7 410.7 464 424 464z"/></svg>',
        'sub_menu' => [
            [
                'id' => 'accounts',
                'title' => 'file.menu.accounts',
                'route' => 'accounts.index',
                'feature' => 'accounts_active',
                'permission' => ['acc_accounts_view', 'acc_accounts_create', 'acc_accounts_update'],
            ],
            [
                'id' => 'fund_transfer',
                'title' => 'file.menu.fund-transfer',
                'route' => 'fund-transfers.index',
                'feature' => 'fund_transfers_active',
                'permission' => ['acc_transfer_view', 'acc_transfer_create', 'acc_transfer_update', 'acc_transfer_delete'],
            ],
            [
                'id' => 'opening-balance',
                'title' => 'file.menu.opening_balance',
                'route' => 'opening-balances.create',
                'feature' => 'opening_balance_active',
                'permission' => ['acc_opening_balance_manage'],
            ],
            [
                'id' => 'balance-sheet',
                'title' => 'file.menu.balance-sheet',
                'route' => 'reports.balance-sheet',
                'feature' => 'balance_sheet_active',
                'permission' => ['acc_balance_sheet_view'],
            ],
            [
                'id' => 'trial-balance',
                'title' => 'file.menu.trial-balance',
                'route' => 'reports.trial-balance',
                'feature' => 'trial_balance_active',
                'permission' => ['acc_trial_balance_view'],
            ],
            [
                'id' => 'profit_and_loss',
                'title' => 'file.menu.profit_and_loss',
                'route' => 'reports.profit-loss',
                'feature' => 'profit_loss_active',
                'permission' => ['acc_profit_loss_view'],
            ],
        ],
    ],
     [
        'id' => 'crm_menu',
        'title' => 'file.menu.crm',
        'icon' => '<svg fill="currentColor" class="side-menu__icon" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.3.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M320 64C355.3 64 384 92.7 384 128C384 163.3 355.3 192 320 192C284.7 192 256 163.3 256 128C256 92.7 284.7 64 320 64zM416 376C416 401 403.3 423 384 435.9L384 528C384 554.5 362.5 576 336 576L304 576C277.5 576 256 554.5 256 528L256 435.9C236.7 423 224 401 224 376L224 336C224 283 267 240 320 240C373 240 416 283 416 336L416 376zM160 96C190.9 96 216 121.1 216 152C216 182.9 190.9 208 160 208C129.1 208 104 182.9"/></svg>',
        'sub_menu' => [
            [
                'id' => 'todays_followup',
                'title' => 'file.menu.todays_followup',
                'route' => 'leads.today-follow-up',
                'feature' => 'leads_active',
                'permission' => ['crm_leads_view', 'crm_leads_create', 'crm_leads_update'],
            ],
            [
                'id' => 'all_leads',
                'title' => 'file.menu.all_leads',
                'route' => 'leads.index',
                'feature' => 'leads_active',
                'permission' => ['crm_leads_view', 'crm_leads_create', 'crm_leads_update'],
            ],
            [
                'id' => 'all_deals',
                'title' => 'file.menu.all_deals',
                'route' => 'deals.index',
                'feature' => 'leads_active',
                'permission' => ['crm_leads_view', 'crm_leads_create', 'crm_leads_update'],
            ],
            [
                'id' => 'meeting_calendar',
                'title' => 'file.menu.meeting_calendar',
                'route' => 'meetings.index',
                'feature' => 'leads_active',
                'permission' => ['crm_leads_notes_view'],
            ],
            [
                'id' => 'lead_subjects',
                'title' => 'file.menu.lead_subjects',
                'route' => 'lead-subjects.index',
                'feature' => 'leads_active',
                'permission' => ['crm_leads_manage_subject'],
            ],
            [
                'id' => 'lead_sources',
                'title' => 'file.menu.lead_sources',
                'route' => 'lead-sources.index',
                'feature' => 'leads_active',
                'permission' => ['crm_leads_manage_source'],
            ],
            [
                'id' => 'statuses',
                'title' => 'file.menu.statuses',
                'route' => 'statuses.index',
                'feature' => 'leads_active',
                'permission' => ['crm_leads_manage_status'],
            ],
            [
                'id' => 'public-forms',
                'title' => 'file.menu.public_forms',
                'route' => 'public-forms.index',
                'permission' => ['manage_public_forms'],
            ],
        ]
    ],
    [
        'id' => 'assets_menu',
        'title' => 'file.menu.assets_manage',
        'icon' => '<svg fill="currentColor" class="side-menu__icon" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><!--!Font Awesome Free v7.3.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.--><path d="M416 32L304 32C295.2 32 288 39.2 288 48L288 192L112 192C94.3 192 80 206.3 80 224C80 241.7 94.3 256 112 256L128 256L128 480L76.8 518.4C68.7 524.4 64 533.9 64 544C64 561.7 78.3 576 96 576L544 576C561.7 576 576 561.7 576 544C576 533.9 571.3 524.4 563.2 518.4L512 480L512 256L528 256C545.7 256 560 241.7 560 224C560 206.3 545.7 192 528 192L336 192L336 128L416 128C424.8 128 432 120.8 432 112L432 48C432 39.2 424.8 32 416 32zM464 256L464 480L400 480L400 256L464 256zM352 256L352 480L288 480L288 256L352 256zM240 256L240 480L176 480L176 256L240 256z"/></svg>',
        'sub_menu' => [
            [
                'id' => 'assets',
                'title' => 'file.menu.assets',
                'route' => 'assets.index',
                'feature' => 'assets_active',
                'permission' => ['assets_view', 'assets_create', 'assets_update'],
            ],
            [
                'id' => 'assets_register',
                'title' => 'file.menu.assets_register',
                'route' => 'assets.register.index',
                'feature' => 'assets_active',
                'permission' => ['assets_register_manage'],
            ],
        ],
    ],
    [
        'id' => 'peoples_menu',
        'title' => 'file.menu.peoples',
        'icon' => '<svg fill="currentColor" class="side-menu__icon" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M320 64C355.3 64 384 92.7 384 128C384 163.3 355.3 192 320 192C284.7 192 256 163.3 256 128C256 92.7 284.7 64 320 64zM416 376C416 401 403.3 423 384 435.9L384 528C384 554.5 362.5 576 336 576L304 576C277.5 576 256 554.5 256 528L256 435.9C236.7 423 224 401 224 376L224 336C224 283 267 240 320 240C373 240 416 283 416 336L416 376zM160 96C190.9 96 216 121.1 216 152C216 182.9 190.9 208 160 208C129.1 208 104 182.9 104 152C104 121.1 129.1 96 160 96zM176 336L176 368C176 400.5 188.1 430.1 208 452.7L208 528C208 529.2 208 530.5 208.1 531.7C199.6 539.3 188.4 544 176 544L144 544C117.5 544 96 522.5 96 496L96 439.4C76.9 428.4 64 407.7 64 384L64 352C64 299 107 256 160 256C172.7 256 184.8 258.5 195.9 262.9C183.3 284.3 176 309.3 176 336zM432 528L432 452.7C451.9 430.2 464 400.5 464 368L464 336C464 309.3 456.7 284.4 444.1 262.9C455.2 258.4 467.3 256 480 256C533 256 576 299 576 352L576 384C576 407.7 563.1 428.4 544 439.4L544 496C544 522.5 522.5 544 496 544L464 544C451.7 544 440.4 539.4 431.9 531.7C431.9 530.5 432 529.2 432 528zM480 96C510.9 96 536 121.1 536 152C536 182.9 510.9 208 480 208C449.1 208 424 182.9 424 152C424 121.1 449.1 96 480 96z"/></svg>',
        'sub_menu' => [
            [
                'id' => 'billers',
                'title' => 'file.menu.billers',
                'route' => 'billers.index',
                'feature' => 'billers_active',
                'permission' => ['billers_view', 'billers_create', 'billers_update'],
            ],
            [
                'id' => 'customers',
                'title' => 'file.menu.customers',
                'route' => 'customers.index',
                'feature' => 'customers_active',
                'permission' => ['customer_view', 'customer_create', 'customer_update'],
            ],
            [
                'id' => 'suppliers',
                'title' => 'file.menu.suppliers',
                'route' => 'suppliers.index',
                'feature' => 'suppliers_active',
                'permission' => ['supplier_view', 'supplier_create', 'supplier_update'],
            ],
            [
                'id' => 'customer_groups',
                'title' => 'file.menu.customer_groups',
                'route' => 'customer_groups.index',
                'feature' => 'customers_active',
                'permission' => ['manage_customer_group'],
            ],
        ],
    ],
    [
        'id' => 'marketing_menu',
        'title' => 'file.menu.marketing',
        'icon' => '<svg fill="currentColor" class="side-menu__icon" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M525.2 82.9C536.7 88 544 99.4 544 112L544 528C544 540.6 536.7 552 525.2 557.1C513.7 562.2 500.4 560.3 490.9 552L444.3 511.3C400.7 473.2 345.6 451 287.9 448.3L287.9 544C287.9 561.7 273.6 576 255.9 576L223.9 576C206.2 576 191.9 561.7 191.9 544L191.9 448C121.3 448 64 390.7 64 320C64 249.3 121.3 192 192 192L276.5 192C338.3 191.8 397.9 169.3 444.4 128.7L491 88C500.4 79.7 513.9 77.8 525.3 82.9zM288 384L288 384.2C358.3 386.9 425.8 412.7 480 457.6L480 182.3C425.8 227.2 358.3 253 288 255.7L288 384z"/></svg>',
        'sub_menu' => [
            [
                'id' => 'memberships',
                'title' => 'file.menu.memberships',
                'route' => 'memberships.index',
                'feature' => 'membership_active',
                'permission' => ['manage_membership_plans'],
            ],
        ],
    ],
    [
        'id' => 'user_role_menu',
        'title' => 'file.menu.UserRoleManagement',
        'icon' => '<svg fill="currentColor" class="side-menu__icon" width="24px" height="24px" viewBox="0 0 52 52" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M38.3,27.2A11.4,11.4,0,1,0,49.7,38.6,11.46,11.46,0,0,0,38.3,27.2Zm2,12.4a2.39,2.39,0,0,1-.9-.2l-4.3,4.3a1.39,1.39,0,0,1-.9.4,1,1,0,0,1-.9-.4,1.39,1.39,0,0,1,0-1.9l4.3-4.3a2.92,2.92,0,0,1-.2-.9,3.47,3.47,0,0,1,3.4-3.8,2.39,2.39,0,0,1,.9.2c.2,0,.2.2.1.3l-2,1.9a.28.28,0,0,0,0,.5L41.1,37a.38.38,0,0,0,.6,0l1.9-1.9c.1-.1.4-.1.4.1a3.71,3.71,0,0,1,.2.9A3.57,3.57,0,0,1,40.3,39.6Z"></path> <circle cx="21.7" cy="14.9" r="12.9"></circle> <path d="M25.2,49.8c2.2,0,1-1.5,1-1.5h0a15.44,15.44,0,0,1-3.4-9.7,15,15,0,0,1,1.4-6.4.77.77,0,0,1,.2-.3c.7-1.4-.7-1.5-.7-1.5h0a12.1,12.1,0,0,0-1.9-.1A19.69,19.69,0,0,0,2.4,47.1c0,1,.3,2.8,3.4,2.8H24.9C25.1,49.8,25.1,49.8,25.2,49.8Z"></path> </g></svg>',
        'sub_menu' => [
            [
                'id' => 'users',
                'title' => 'file.menu.Users',
                'route' => 'users',
                'permission' => ['manage_user'],
            ],
            [
                'id' => 'roles-permissions',
                'title' => 'file.menu.RolePermission',
                'route' => 'roles-permissions',
                'permission' => ['manage_role'],
            ],
        ],
    ],
    [
        'id' => 'settings',
        'title' => 'file.menu.settings',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24" fill="currentColor"><path d="M19.43 12.98c.04-.32.07-.65.07-.98s-.03-.66-.07-.98l2.11-1.65a.5.5 0 0 0 .11-.65l-2-3.46a.5.5 0 0 0-.61-.22l-2.49 1a7.14 7.14 0 0 0-1.7-.98l-.38-2.65A.5.5 0 0 0 14 2h-4a.5.5 0 0 0-.49.42l-.38 2.65c-.63.25-1.21.57-1.75.95l-2.49-1a.5.5 0 0 0-.61.22l-2 3.46a.5.5 0 0 0 .11.65L4.57 11c-.04.32-.07.65-.07.98s.03.66.07.98L2.46 14.6a.5.5 0 0 0-.11.65l2 3.46a.5.5 0 0 0 .61.22l2.49-1c.54.38 1.12.7 1.75.95l.38 2.65A.5.5 0 0 0 10 22h4a.5.5 0 0 0 .49-.42l.38-2.65c.63-.25 1.21-.57 1.75-.95l2.49 1a.5.5 0 0 0 .61-.22l2-3.46a.5.5 0 0 0-.11-.65l-2.18-1.65zM12 15.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7z"/></svg>',
        'sub_menu' => [
            [
                'id' => 'branch-settings',
                'title' => 'file.menu.branch_settings',
                'route' => 'branches.index',
                'feature' => 'branches_active',
                'permission' => ['branch_view', 'branch_create', 'branch_update'],
            ],
            [
                'id' => 'rack-settings',
                'title' => 'file.menu.rack_settings',
                'route' => 'racks-shelves.index',
                'feature' => 'rack_and_shelfs_active',
                'permission' => ['rack_and_shelfs_view', 'rack_and_shelfs_create', 'rack_and_shelfs_update'],
            ],
            [
                'id' => 'custom-field-settings',
                'title' => 'file.menu.custom_field_settings',
                'route' => 'custom-fields.index',
                'permission' => ['manage_custom_fields'],
            ],
            [
                'id' => 'tax-settings',
                'title' => 'file.menu.tax_settings',
                'route' => 'taxes.index',
                'permission' => ['manage_vat_tax'],
            ],
            [
                'id' => 'general-settings',
                'title' => 'file.menu.general_settings',
                'route' => 'settings',
                'permission' => ['manage_general_settings', 'manage_email_settings', 'manage_currency_settings', 'manage_analytics_settings', 'manage_ai_settings'],
            ],
            [
                'id' => 'clear-cache',
                'title' => 'file.menu.clear_cache',
                'route' => 'tenant.clear-cache',
                'method' => 'GET',
            ],
        ],
    ],
    [
        'id' => 'trash',
        'title' => 'file.menu.trash',
        'icon' => '<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 24 24" fill="currentColor" viewBox="0 0 24 24"><title>trash-can</title><path d="M9,3V4H4V6H5V19A2,2 0 0,0 7,21H17A2,2 0 0,0 19,19V6H20V4H15V3H9M9,8H11V17H9V8M13,8H15V17H13V8Z" /></svg>',
        'route' => 'trash.index',
        'permission' => ['manage_trash'],
    ],
];
