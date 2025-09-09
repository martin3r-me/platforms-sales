<?php

return [
    'routing' => [
        'mode' => env('SALES_MODE', 'path'),
        'prefix' => 'sales',
    ],
    'guard' => 'web',

    'navigation' => [
        'route' => 'sales.dashboard',
        'icon'  => 'heroicon-o-chart-bar-square',
        'order' => 20,
    ],

    'sidebar' => [
        [
            'group' => 'Allgemein',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'sales.dashboard',
                    'icon'  => 'heroicon-o-chart-bar',
                ],
                [
                    'label' => 'Meine Deals',
                    'route' => 'sales.my-deals',
                    'icon'  => 'heroicon-o-home',
                ],
                [
                    'label' => 'Board-Templates',
                    'route' => 'sales.templates.index',
                    'icon'  => 'heroicon-o-document-duplicate',
                ],
                [
                    'label' => 'Vertriebsboard anlegen',
                    'dispatch' => 'create-sales-board',
                    'icon'  => 'heroicon-o-plus',
                ],  
            ],
        ],
        [
            'group' => 'Vertriebsboards',
            'dynamic' => [
                // Nur Model und Parameter, keine Closures
                'model'     => Platform\Sales\Models\SalesBoard::class,
                'team_based' => true, // sagt der Sidebar, nach aktuellem Team filtern
                'order_by'  => 'name',
                'route'     => 'sales.boards.show', // Basisroute
                'icon'      => 'heroicon-o-folder',
                'label_key' => 'name', // Feldname, das als Label genutzt wird
            ],
        ],

    ],
    'billables' => [
        [
            // Pflicht: Das zu überwachende Model
            'model' => \Platform\Sales\Models\SalesDeal::class,

            // Abrechnungsart: Einzelobjekt pro Zeitraum (alternativ: 'flat_fee')
            'type' => 'per_item',

            // Für UI, Listen, Erklärungen:
            'label' => 'Sales-Deal',
            'description' => 'Jeder erstellte Deal im Vertriebsboard verursacht tägliche Kosten nach Nutzung.',

            // PREISSTAFFELUNG: Ein Array mit mehreren Preisstufen!
            'pricing' => [
                [
                    'cost_per_day' => 0.0025,           // 2.5 Cent pro Ticket pro Tag
                    'start_date' => '2025-01-01',
                    'end_date' => null,
                ]
            ],

            // Kostenloses Kontingent (z.B. pro Tag)
            'free_quota' => null,               
            'min_cost' => null,               
            'max_cost' => null,              

            // Abrechnung & Zeitraum (idR identisch mit Pricing, aber für UI/Backend)
            'billing_period' => 'daily',      
            // Optional, falls ganzes Billable irgendwann endet
            'start_date' => '2026-01-01',
            'end_date' => null,               

            // Sonderlogik
            'trial_period_days' => 0,         
            'discount_percent' => 0,          
            'exempt_team_ids' => [],          

            // Interne Ordnung/Hilfen
            'priority' => 100,                
            'active' => true,                 
        ],
        [
            // Pflicht: Das zu überwachende Model
            'model' => \Platform\Sales\Models\SalesBoard::class,

            // Abrechnungsart: Einzelobjekt pro Zeitraum (alternativ: 'flat_fee')
            'type' => 'per_item',

            // Für UI, Listen, Erklärungen:
            'label' => 'Vertriebsboard',
            'description' => 'Jedes erstellte Board im Vertriebsboard verursacht tägliche Kosten nach Nutzung.',

            // PREISSTAFFELUNG: Ein Array mit mehreren Preisstufen!
            'pricing' => [
                [
                    'cost_per_day' => 0.005,           // 5 Cent pro Board pro Tag
                    'start_date' => '2025-01-01',
                    'end_date' => null,
                ]
            ],

            // Kostenloses Kontingent (z.B. pro Tag)
            'free_quota' => null,               
            'min_cost' => null,               
            'max_cost' => null,              

            // Abrechnung & Zeitraum (idR identisch mit Pricing, aber für UI/Backend)
            'billing_period' => 'daily',      
            // Optional, falls ganzes Billable irgendwann endet
            'start_date' => '2026-01-01',
            'end_date' => null,               

            // Sonderlogik
            'trial_period_days' => 0,         
            'discount_percent' => 0,          
            'exempt_team_ids' => [],          

            // Interne Ordnung/Hilfen
            'priority' => 100,                
            'active' => true,                 
        ]
    ]
];