<?php

use Platform\Sales\Livewire\Dashboard;
use Platform\Sales\Livewire\MyDeals;
use Platform\Sales\Livewire\Board;
use Platform\Sales\Livewire\Deal;
use Platform\Sales\Livewire\TemplateIndex;
use Platform\Sales\Livewire\TemplateShow;
use Platform\Sales\Livewire\Settings\LostReason\Index as LostReasonIndex;
use Platform\Sales\Livewire\Settings\DealSource\Index as DealSourceIndex;
use Platform\Sales\Livewire\Settings\DealType\Index as DealTypeIndex;

Route::get('/', Dashboard::class)->name('sales.dashboard');
Route::get('/my-deals', MyDeals::class)->name('sales.my-deals');

// Model-Binding: Parameter == Modelname in camelCase
Route::get('/boards/{salesBoard}', Board::class)
    ->name('sales.boards.show');

Route::get('/deals/{salesDeal}', Deal::class)
    ->name('sales.deals.show');

// Template-Routes
Route::get('/templates', TemplateIndex::class)->name('sales.templates.index');
Route::get('/templates/{salesBoardTemplate}', TemplateShow::class)->name('sales.templates.show');

// Einstellungen / Stammdaten (Lookups)
Route::get('/settings/deal-sources', DealSourceIndex::class)->name('sales.settings.deal-sources.index');
Route::get('/settings/deal-types', DealTypeIndex::class)->name('sales.settings.deal-types.index');
Route::get('/settings/lost-reasons', LostReasonIndex::class)->name('sales.settings.lost-reasons.index');
