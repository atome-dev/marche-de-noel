<?php

use App\Http\Controllers\Admin\DownloadSnapshot;
use App\Http\Controllers\Admin\StopImpersonation;
use App\Http\Controllers\Cookies\ExportOrders;
use App\Http\Controllers\Documents\ShowDocument;
use App\Http\Controllers\Help\ShowTutorial;
use App\Http\Controllers\Planning\PrintPlanning;
use App\Http\Controllers\TreeOrders\DownloadDocument as DownloadTreeOrderDocument;
use App\Http\Controllers\TreeOrders\ExportTreeOrders;
use App\Http\Controllers\TreeOrders\PaymentAborted;
use App\Http\Controllers\TreeOrders\PaymentNotification;
use App\Http\Controllers\TreeOrders\PaymentReturn;
use App\Http\Controllers\TreeOrders\PrintPickupList;
use App\Http\Controllers\TreeOrders\ShopPayment;
use App\Http\Middleware\LogHelloAssoCall;
use App\Livewire\Admin\Snapshots;
use App\Livewire\Admin\Users;
use App\Livewire\Auth\AcceptInvitation;
use App\Livewire\Auth\CompleteRegistration;
use App\Livewire\Auth\Register;
use App\Livewire\Cookies\Categories as CookieCategories;
use App\Livewire\Cookies\Index as CookiesIndex;
use App\Livewire\Cookies\Orders as CookieOrders;
use App\Livewire\Cookies\RecipeForm;
use App\Livewire\Cookies\Show as CookieShow;
use App\Livewire\Documents\Index as DocumentsIndex;
use App\Livewire\Planning\Board;
use App\Livewire\Raffle\Index as RaffleIndex;
use App\Livewire\Stands\Index as StandsIndex;
use App\Livewire\Stands\StandForm;
use App\Livewire\TreeOrders\Manage as TreeOrdersManage;
use App\Livewire\TreeOrders\OrderForm as TreeOrderForm;
use App\Livewire\TreeOrders\Pickup as TreeOrdersPickup;
use App\Livewire\TreeOrders\Summary as TreeOrdersSummary;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard')->name('home');

Route::view('donnees-personnelles', 'privacy')->name('privacy');
Route::get('mode-d-emploi/{guide?}', ShowTutorial::class)->name('tutorial');

// Server-to-server call of HelloAsso: no session, no CSRF token (see bootstrap/app.php).
Route::post('sapins/paiement/notification', PaymentNotification::class)
    ->middleware('throttle:60,1')
    ->name('trees.payment.notification');

// Same notification, at the address declared in the HelloAsso back office.
// Every call (GET or POST) is logged in storage/logs/helloasso-AAAA-MM-JJ.log.
Route::match(['get', 'post'], 'helloasso', PaymentNotification::class)
    ->middleware([LogHelloAssoCall::class, 'throttle:60,1'])
    ->name('trees.payment.notification.helloasso');

Route::middleware('guest')->group(function () {
    Route::livewire('register', Register::class)->name('register');

    Route::livewire('register/complete', CompleteRegistration::class)
        ->middleware('signed')
        ->name('register.complete');

    Route::livewire('invitation/{user}', AcceptInvitation::class)
        ->middleware('signed')
        ->name('invitation.accept');
});

Route::middleware('auth')->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('planning', Board::class)->name('planning');
    Route::get('planning/print', PrintPlanning::class)->name('planning.print');

    Route::livewire('stands', StandsIndex::class)->name('stands.index');
    Route::livewire('stands/create', StandForm::class)->name('stands.create');
    Route::livewire('stands/{stand}/edit', StandForm::class)->name('stands.edit');

    Route::prefix('petits-gateaux')->name('cookies.')->group(function () {
        Route::livewire('/', CookiesIndex::class)->name('index');

        Route::middleware('can:manage-cookies')->group(function () {
            Route::livewire('recettes/creer', RecipeForm::class)->name('create');
            Route::livewire('recettes/{recipe}/modifier', RecipeForm::class)->name('edit');
            Route::livewire('familles', CookieCategories::class)->name('categories');
            Route::livewire('commandes', CookieOrders::class)->name('orders');
            Route::get('commandes/export.xlsx', [ExportOrders::class, 'xlsx'])->name('orders.xlsx');
            Route::get('commandes/export.pdf', [ExportOrders::class, 'pdf'])->name('orders.pdf');
        });

        Route::livewire('recettes/{recipe}', CookieShow::class)->name('show');
    });

    Route::livewire('tombola', RaffleIndex::class)->name('raffle.index');

    Route::prefix('sapins')->name('trees.')->group(function () {
        Route::livewire('/', TreeOrderForm::class)->name('index');
        Route::get('commandes/{treeOrder}/bon-de-commande.pdf', [DownloadTreeOrderDocument::class, 'orderForm'])->name('documents.order-form');
        Route::get('commandes/{treeOrder}/bon-de-retrait.pdf', [DownloadTreeOrderDocument::class, 'pickupSlip'])->name('documents.pickup-slip');

        Route::get('commandes/{treeOrder}/paiement', ShopPayment::class)->name('shop-payment');

        Route::get('paiement/{treeOrder}/retour', PaymentReturn::class)->name('payment.return');
        Route::get('paiement/{treeOrder}/abandon', PaymentAborted::class)->name('payment.back');
        Route::get('paiement/{treeOrder}/erreur', PaymentAborted::class)->name('payment.error');

        Route::middleware('can:manage-tree-orders')->prefix('gestion')->group(function () {
            Route::livewire('/', TreeOrdersManage::class)->name('manage');
            Route::livewire('synthese', TreeOrdersSummary::class)->name('summary');
            Route::get('synthese/export.xlsx', [ExportTreeOrders::class, 'xlsx'])->name('summary.xlsx');
            Route::get('synthese/export.pdf', [ExportTreeOrders::class, 'pdf'])->name('summary.pdf');
            Route::livewire('retrait', TreeOrdersPickup::class)->name('pickup');
            Route::get('retrait/impression', PrintPickupList::class)->name('pickup.print');
        });
    });

    // Reachable while acting as a volunteer: not behind the admin gate.
    Route::post('agir-en-tant-que/quitter', StopImpersonation::class)->name('impersonation.stop');

    Route::livewire('documents', DocumentsIndex::class)->name('documents.index');
    Route::get('documents/{document}', ShowDocument::class)->name('documents.show');

    Route::middleware('can:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::livewire('users', Users::class)->name('users');
        Route::livewire('sauvegardes', Snapshots::class)->name('snapshots');
        Route::get('sauvegardes/{name}', DownloadSnapshot::class)->name('snapshots.download');
    });
});

require __DIR__.'/settings.php';
