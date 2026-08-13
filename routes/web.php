<?php

declare(strict_types=1);

/**
 * Runovia web routes.
 *
 * ! ONE CONTROLLER PER ENDPOINT, grouped by resource. Every class below has a single
 *   __invoke(), so the route table names the operation and a reader does not have to
 *   open a file to learn which of seven actions `CustomerController@destroy` was.
 *
 * ! THREE MIDDLEWARE, and the order they compose in matters:
 *
 *     guest.only    signed-in users bounce off login/register
 *     api.session   there is a live API token in the session
 *     api.business  ...and the user has a business, or they go to onboarding
 *
 *   `api.business` is deliberately NOT on the onboarding routes (that would be
 *   circular) nor on logout (a user with no business must still be able to leave).
 *
 * ! None of this is the security boundary. The API re-authenticates every request
 *   through its own eleven gates and re-checks permissions on each one; these
 *   middleware only turn a refusal that would arrive mid-page into a clean redirect.
 */

use App\Http\Controllers\ActivityLog\ListActivityController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ShowLoginController;
use App\Http\Controllers\Auth\ShowRegisterController;
use App\Http\Controllers\BankAccounts\CreateBankAccountController;
use App\Http\Controllers\BankAccounts\DeleteBankAccountController;
use App\Http\Controllers\BankAccounts\UpdateBankAccountController;
use App\Http\Controllers\Customers\CreateCustomerController;
use App\Http\Controllers\Customers\CreateCustomerFormController;
use App\Http\Controllers\Customers\DeleteCustomerController;
use App\Http\Controllers\Customers\EditCustomerFormController;
use App\Http\Controllers\Customers\ListCustomersController;
use App\Http\Controllers\Customers\ShowCustomerController;
use App\Http\Controllers\Customers\UpdateCustomerController;
use App\Http\Controllers\Dashboard\ShowDashboardController;
use App\Http\Controllers\Invoices\CreateInvoiceController;
use App\Http\Controllers\Invoices\CreateInvoiceFormController;
use App\Http\Controllers\Invoices\DeleteInvoiceController;
use App\Http\Controllers\Invoices\EditInvoiceFormController;
use App\Http\Controllers\Invoices\ListInvoicesController;
use App\Http\Controllers\Invoices\ShowInvoiceController;
use App\Http\Controllers\Invoices\ShowInvoicePdfController;
use App\Http\Controllers\Invoices\UpdateInvoiceController;
use App\Http\Controllers\Onboarding\CreateBusinessController;
use App\Http\Controllers\Onboarding\CreateBusinessFormController;
use App\Http\Controllers\Packages\CreatePackageController;
use App\Http\Controllers\Packages\CreatePackageFormController;
use App\Http\Controllers\Packages\DeletePackageController;
use App\Http\Controllers\Packages\EditPackageFormController;
use App\Http\Controllers\Packages\ListPackagesController;
use App\Http\Controllers\Packages\ShowPackageController;
use App\Http\Controllers\Packages\UpdatePackageController;
use App\Http\Controllers\Payments\CreatePaymentController;
use App\Http\Controllers\Payments\DeletePaymentController;
use App\Http\Controllers\Payments\ListPaymentsController;
use App\Http\Controllers\Platform\ListPlatformBusinessesController;
use App\Http\Controllers\Platform\SetBusinessStatusController;
use App\Http\Controllers\Products\CreateProductController;
use App\Http\Controllers\Products\CreateProductFormController;
use App\Http\Controllers\Products\DeleteProductController;
use App\Http\Controllers\Products\EditProductFormController;
use App\Http\Controllers\Products\ListProductsController;
use App\Http\Controllers\Products\UpdateProductController;
use App\Http\Controllers\Settings\ShowSettingsController;
use App\Http\Controllers\Settings\UpdateSettingsController;
use Illuminate\Support\Facades\Route;

// # ---------------------------------------------------------------------
// # Signed out
// # ---------------------------------------------------------------------

Route::middleware('guest.only')->group(function (): void {
    Route::get('/login', ShowLoginController::class)->name('login');
    Route::post('/login', LoginController::class)->name('login.attempt');

    Route::get('/register', ShowRegisterController::class)->name('register');
    Route::post('/register', RegisterController::class)->name('register.attempt');
});

// # ---------------------------------------------------------------------
// # Signed in, business not required
// # ---------------------------------------------------------------------

// ! No api.business here. A user with no business must be able to create one and to
// ! sign out; requiring a business to reach either would be a dead end.
Route::middleware('api.session')->group(function (): void {
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::get('/welcome/business', CreateBusinessFormController::class)
        ->name('onboarding.business.create');
    Route::post('/welcome/business', CreateBusinessController::class)
        ->name('onboarding.business.store');
});

// # ---------------------------------------------------------------------
// # The business application
// # ---------------------------------------------------------------------

Route::middleware(['api.session', 'api.business'])->group(function (): void {

    Route::get('/', ShowDashboardController::class)->name('dashboard');

    // # Customers
    Route::prefix('customers')->name('customers.')->group(function (): void {
        Route::get('/', ListCustomersController::class)->name('index');
        Route::get('/create', CreateCustomerFormController::class)->name('create');
        Route::post('/', CreateCustomerController::class)->name('store');
        Route::get('/{customer}', ShowCustomerController::class)->name('show');
        Route::get('/{customer}/edit', EditCustomerFormController::class)->name('edit');
        Route::put('/{customer}', UpdateCustomerController::class)->name('update');
        Route::delete('/{customer}', DeleteCustomerController::class)->name('destroy');
    });

    // # Products and services — one resource, discriminated by `type`.
    Route::prefix('products')->name('products.')->group(function (): void {
        Route::get('/', ListProductsController::class)->name('index');
        Route::get('/create', CreateProductFormController::class)->name('create');
        Route::post('/', CreateProductController::class)->name('store');
        Route::get('/{product}/edit', EditProductFormController::class)->name('edit');
        Route::put('/{product}', UpdateProductController::class)->name('update');
        Route::delete('/{product}', DeleteProductController::class)->name('destroy');
    });

    // # Packages
    Route::prefix('packages')->name('packages.')->group(function (): void {
        Route::get('/', ListPackagesController::class)->name('index');
        Route::get('/create', CreatePackageFormController::class)->name('create');
        Route::post('/', CreatePackageController::class)->name('store');
        Route::get('/{package}', ShowPackageController::class)->name('show');
        Route::get('/{package}/edit', EditPackageFormController::class)->name('edit');
        Route::put('/{package}', UpdatePackageController::class)->name('update');
        Route::delete('/{package}', DeletePackageController::class)->name('destroy');
    });

    // # Invoices
    Route::prefix('invoices')->name('invoices.')->group(function (): void {
        Route::get('/', ListInvoicesController::class)->name('index');
        Route::get('/create', CreateInvoiceFormController::class)->name('create');
        Route::post('/', CreateInvoiceController::class)->name('store');
        Route::get('/{invoice}', ShowInvoiceController::class)->name('show');
        Route::get('/{invoice}/edit', EditInvoiceFormController::class)->name('edit');

        // ! Rendered in the browser rather than downloaded. The viewer's own print
        // ! dialog produces the PDF, so this app needs no PDF library — which is one
        // ! fewer native dependency to have an arm64 build of on the host.
        Route::get('/{invoice}/print', ShowInvoicePdfController::class)->name('print');

        Route::put('/{invoice}', UpdateInvoiceController::class)->name('update');

        // ! Deletes a draft or CANCELS anything issued, and the API decides which.
        // ! The controller reads `data.action` to report what actually happened.
        Route::delete('/{invoice}', DeleteInvoiceController::class)->name('destroy');

        // # Recording a payment is nested, because a payment has no meaning
        // # without its invoice — the API models it the same way.
        Route::post('/{invoice}/payments', CreatePaymentController::class)->name('payments.store');
    });

    // # Payments — business-wide reading, plus reversal.
    Route::prefix('payments')->name('payments.')->group(function (): void {
        Route::get('/', ListPaymentsController::class)->name('index');
        Route::delete('/{payment}', DeletePaymentController::class)->name('destroy');
    });

    // # Company settings, and the bank accounts managed alongside them.
    Route::prefix('settings')->name('settings.')->group(function (): void {
        Route::get('/', ShowSettingsController::class)->name('edit');
        Route::put('/', UpdateSettingsController::class)->name('update');
    });

    Route::prefix('bank-accounts')->name('bank-accounts.')->group(function (): void {
        Route::post('/', CreateBankAccountController::class)->name('store');
        Route::put('/{bankAccount}', UpdateBankAccountController::class)->name('update');
        Route::delete('/{bankAccount}', DeleteBankAccountController::class)->name('destroy');
    });

    Route::get('/activity', ListActivityController::class)->name('activity.index');
});

// # ---------------------------------------------------------------------
// # Platform administration
// # ---------------------------------------------------------------------

// ! A platform admin (SA) reaches NONE of the routes above, and no business role
// ! reaches these. Module::forRole() gives the two families disjoint module sets, so
// ! the separation is the API's, not this file's — `platform` middleware only saves
// ! the user from being shown a screen of refusals.
Route::middleware(['api.session', 'platform'])->prefix('platform')->name('platform.')->group(function (): void {
    Route::get('/', ListPlatformBusinessesController::class)->name('dashboard');
    Route::put('/businesses/{business}/status', SetBusinessStatusController::class)->name('businesses.status');
});
