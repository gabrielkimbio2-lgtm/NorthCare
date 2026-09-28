<?php

use App\Http\Controllers\Admin\AdminCommentController;
use App\Http\Controllers\Admin\AdminSessionController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\DirectoryCatalogController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\ProfileApplicationController;
use App\Http\Controllers\Admin\ProviderProfileController;
use App\Http\Controllers\AppointmentBookingController;
use App\Http\Controllers\AppointmentCommentController;
use App\Http\Controllers\Auth\ApprovalStatusController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\Institution\ProfileController as InstitutionProfileController;
use App\Http\Controllers\Practitioner\PractitionerAppointmentController;
use App\Http\Controllers\Practitioner\ProfileController;
use App\Http\Controllers\Practitioner\ServiceSuggestionController;
use App\Http\Middleware\EnsureActiveInstitution;
use App\Http\Middleware\EnsureActivePractitioner;
use App\Http\Middleware\EnsureAdministrator;
use Illuminate\Support\Facades\Route;

Route::get('/', DirectoryController::class)->name('directory.index');
Route::post('/language', [LanguageController::class, 'switch'])->name('language.switch');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:6,1');
    Route::get('/register', [RegistrationController::class, 'create'])->name('register');
    Route::post('/register', [RegistrationController::class, 'store']);

    Route::get('/admin/login', [AdminSessionController::class, 'create'])->name('admin.login');
    Route::post('/admin/login', [AdminSessionController::class, 'store'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/account/pending-approval', ApprovalStatusController::class)->name('account.pending-approval');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/practitioners/{practitioner}/book', [AppointmentBookingController::class, 'create'])->name('appointments.create');
    Route::post('/practitioners/{practitioner}/book', [AppointmentBookingController::class, 'store'])->name('appointments.store');
    Route::patch('/appointments/{appointment}/cancel', [AppointmentBookingController::class, 'cancel'])->name('appointments.cancel');
    Route::post('/appointments/{appointment}/comments', [AppointmentCommentController::class, 'store'])->name('appointments.comments.store');
});

Route::prefix('admin')->name('admin.')->middleware(EnsureAdministrator::class)->group(function () {
    Route::view('/dashboard', 'admin.dashboard')->name('dashboard');
    Route::post('/administrators', [AdminUserController::class, 'store'])->name('administrators.store');
    Route::get('/profiles/create', [ProviderProfileController::class, 'create'])->name('profiles.create');
    Route::post('/practitioners', [ProviderProfileController::class, 'storePractitioner'])->name('practitioners.store');
    Route::post('/institutions', [ProviderProfileController::class, 'storeInstitution'])->name('institutions.store');
    Route::post('/institutions/{institution}/locations', [ProviderProfileController::class, 'storeInstitutionLocation'])->name('institutions.locations.store');
    Route::get('/catalog', [DirectoryCatalogController::class, 'index'])->name('catalog.index');
    Route::post('/cities', [DirectoryCatalogController::class, 'storeCity'])->name('cities.store');
    Route::post('/regions', [DirectoryCatalogController::class, 'storeRegion'])->name('regions.store');
    Route::post('/categories', [DirectoryCatalogController::class, 'storeCategory'])->name('categories.store');
    Route::post('/services', [DirectoryCatalogController::class, 'storeService'])->name('services.store');
    Route::patch('/services/{service}', [DirectoryCatalogController::class, 'updateService'])->name('services.update');
    Route::patch('/service-suggestions/{serviceSuggestion}', [DirectoryCatalogController::class, 'reviewSuggestion'])->name('service-suggestions.update');
    Route::get('/languages', [LanguageController::class, 'index'])->name('languages.index');
    Route::post('/languages', [LanguageController::class, 'store'])->name('languages.store');
    Route::patch('/languages/{language}', [LanguageController::class, 'update'])->name('languages.update');
    Route::get('/applications', [ProfileApplicationController::class, 'index'])->name('applications.index');
    Route::get('/applications/{profileApplication}', [ProfileApplicationController::class, 'edit'])->name('applications.edit');
    Route::patch('/applications/{profileApplication}', [ProfileApplicationController::class, 'update'])->name('applications.update');
    Route::get('/applications/{profileApplication}/documents/{document}', [ProfileApplicationController::class, 'downloadDocument'])
        ->name('applications.documents.download');
    Route::get('/comments', [AdminCommentController::class, 'index'])->name('comments.index');
    Route::patch('/comments/{comment}', [AdminCommentController::class, 'moderate'])->name('comments.moderate');
});

Route::middleware(['auth', EnsureActivePractitioner::class])->prefix('practitioner')->name('practitioner.')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/services', [ProfileController::class, 'updateServices'])->name('profile.services.update');
    Route::put('/profile/availability', [ProfileController::class, 'updateAvailability'])->name('profile.availability.update');
    Route::post('/profile/unavailable-times', [ProfileController::class, 'storeUnavailableTime'])->name('profile.unavailable-times.store');
    Route::delete('/profile/unavailable-times/{availabilityOverride}', [ProfileController::class, 'destroyUnavailableTime'])->name('profile.unavailable-times.destroy');
    Route::post('/service-suggestions', [ServiceSuggestionController::class, 'store'])->name('service-suggestions.store');
    Route::get('/appointments', [PractitionerAppointmentController::class, 'index'])->name('appointments.index');
    Route::patch('/appointments/{appointment}/approve', [PractitionerAppointmentController::class, 'approve'])->name('appointments.approve');
    Route::patch('/appointments/{appointment}/reject', [PractitionerAppointmentController::class, 'reject'])->name('appointments.reject');
    Route::post('/comments/{comment}/report', [PractitionerAppointmentController::class, 'reportComment'])->name('comments.report');
});

Route::middleware(['auth', EnsureActiveInstitution::class])->prefix('institution')->name('institution.')->group(function () {
    Route::get('/profile', [InstitutionProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [InstitutionProfileController::class, 'update'])->name('profile.update');
    Route::patch('/locations/{institutionLocation}', [InstitutionProfileController::class, 'updateLocation'])->name('locations.update');
});
