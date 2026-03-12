<?php

use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AiContentController;
use App\Http\Controllers\Admin\ComplaintReportController;
use App\Http\Controllers\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Admin\LandRecordController;
use App\Http\Controllers\Admin\LandTransactionController;
use App\Http\Controllers\Admin\LetterServiceRequestController;
use App\Http\Controllers\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\Admin\OperatorController;
use App\Http\Controllers\Admin\PbbPaymentRequestController;
use App\Http\Controllers\Admin\PbbTaxObjectController;
use App\Http\Controllers\Admin\PopulationRecordController;
use App\Http\Controllers\Admin\ServiceArchiveController;
use App\Http\Controllers\Admin\VillageActivityController;
use App\Http\Controllers\Admin\WebsiteSettingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublicMediaController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\PublicServiceController;
use Illuminate\Support\Facades\Route;

$adminLoginPath = trim((string) config('security.admin_login_path', 'masuk-admin'), '/');
$adminLoginPath = $adminLoginPath !== '' ? $adminLoginPath : 'masuk-admin';

Route::get('/media/public/{path}', [PublicMediaController::class, 'show'])
    ->where('path', '.*')
    ->name('media.public');

Route::controller(PublicController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('/profil-desa', 'profile')->name('profile');

    Route::prefix('informasi-publik')->name('information.')->group(function () {
        Route::get('/kependudukan', 'population')->name('population');
        Route::get('/pertanahan', 'land')->name('land');
        Route::get('/kegiatan-desa', 'activities')->name('activities');
    });

    Route::get('/berita', 'newsIndex')->name('news.index');
    Route::get('/berita/{news:slug}', 'newsShow')->name('news.show');
    Route::get('/gallery', 'galleryIndex')->name('gallery.index');
    Route::get('/pengumuman', 'announcementIndex')->name('announcements.index');
    Route::get('/pengumuman/{announcement}', 'announcementShow')->name('announcements.show');
});

Route::controller(PublicServiceController::class)->prefix('layanan')->name('services.')->group(function () {
    Route::get('/pbb', 'pbbForm')->name('pbb');
    Route::post('/pbb', 'pbbStore')->middleware('throttle:service-submit')->name('pbb.store');
    Route::get('/pbb/cari', 'searchPbbByTicket')->middleware('throttle:service-lookup')->name('pbb.search');
    Route::get('/surat-online', 'letterForm')->name('letter');
    Route::post('/surat-online', 'letterStore')->middleware('throttle:service-submit')->name('letter.store');
    Route::get('/surat/sukses/{ticket}', 'letterSuccess')->name('letter.success');
    Route::get('/surat/download/{ticket}', 'downloadLetter')->middleware('throttle:service-lookup')->name('letter.download');
    Route::get('/surat/cari', 'searchLetterByTicket')->middleware('throttle:service-lookup')->name('letter.search');
    Route::get('/pengaduan', 'complaintForm')->name('complaint');
    Route::post('/pengaduan', 'complaintStore')->middleware('throttle:service-submit')->name('complaint.store');
    Route::get('/pengaduan/cari', 'searchComplaintByTicket')->middleware('throttle:service-lookup')->name('complaint.search');
    Route::get('/pengaduan/lampiran/{ticket}', 'complaintEvidence')->middleware('throttle:service-lookup')->name('complaint.evidence');
    
    // API Routes untuk lookup NIK & NOP
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/check-nik', 'checkNik')->middleware('throttle:service-lookup')->name('check-nik');
        Route::get('/search-nop', 'searchNop')->middleware('throttle:service-lookup')->name('search-nop');
    });
});

Route::middleware('guest')->group(function () use ($adminLoginPath) {
    Route::get('/' . $adminLoginPath, [AuthController::class, 'showLogin'])->name('login');
    Route::post('/' . $adminLoginPath, [AuthController::class, 'login'])->middleware('throttle:login')->name('login.attempt');

    if ($adminLoginPath !== 'login') {
        Route::any('/login', static fn () => abort(404));
    }
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::prefix('dashboard')->name('dashboard.')->middleware(['auth', 'role:admin,operator'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/monitoring/summary', [DashboardController::class, 'monitoringSummary'])->name('monitoring.summary');

    Route::get('land-transactions/history', [LandTransactionController::class, 'history'])
        ->name('land-transactions.history');
    Route::get('land-archives', [LandTransactionController::class, 'archives'])
        ->name('land-transactions.archives');
    Route::get('land-transactions/files/{landTransactionFile}', [LandTransactionController::class, 'showFile'])
        ->name('land-transactions.files.show');
    Route::delete('land-transactions/files/{landTransactionFile}', [LandTransactionController::class, 'destroyFile'])
        ->name('land-transactions.files.destroy');
    Route::resource('land-transactions', LandTransactionController::class);

    Route::resources([
        'news' => AdminNewsController::class,
        'announcements' => AnnouncementController::class,
        'galleries' => AdminGalleryController::class,
        'population-records' => PopulationRecordController::class,
        'land-records' => LandRecordController::class,
        'village-activities' => VillageActivityController::class,
    ], ['except' => ['show']]);

    Route::resource('pbb-payment-requests', PbbPaymentRequestController::class)
        ->only(['index', 'update', 'destroy']);
    Route::get('pbb-payment-requests/{pbbPaymentRequest}', [PbbPaymentRequestController::class, 'show'])
        ->name('pbb-payment-requests.show');
    Route::resource('letter-service-requests', LetterServiceRequestController::class)
        ->only(['index', 'update', 'destroy']);
    Route::get('letter-service-requests/{letterServiceRequest}/download', [LetterServiceRequestController::class, 'download'])
        ->name('letter-service-requests.download');
    Route::resource('complaint-reports', ComplaintReportController::class)
        ->only(['index', 'show', 'update', 'destroy']);
    Route::get('complaint-reports/{complaintReport}/evidence', [ComplaintReportController::class, 'evidence'])
        ->name('complaint-reports.evidence');

    Route::prefix('service-archives')->name('service-archives.')->group(function () {
        Route::get('/', [ServiceArchiveController::class, 'index'])->name('index');
        Route::get('/letters/{letterServiceRequest}/pdf', [ServiceArchiveController::class, 'letterPdf'])
            ->name('letters.pdf');
    });

    Route::prefix('ai')->name('ai.')->group(function () {
        Route::post('/generate/news', [AiContentController::class, 'generateNews'])->name('generate.news');
        Route::post('/generate/announcement', [AiContentController::class, 'generateAnnouncement'])->name('generate.announcement');
    });

    Route::post('population-records/import', [PopulationRecordController::class, 'import'])
        ->name('population-records.import');
    Route::get('population-records/template/download', [PopulationRecordController::class, 'template'])
        ->name('population-records.template');
        
    Route::resource('pbb-tax-objects', PbbTaxObjectController::class);
    Route::post('pbb-tax-objects/import', [PbbTaxObjectController::class, 'import'])->name('pbb-tax-objects.import');
    Route::get('pbb-tax-objects/template/download', [PbbTaxObjectController::class, 'template'])
        ->name('pbb-tax-objects.template');

    Route::middleware('role:admin')->group(function () {
        Route::resource('operators', OperatorController::class)->except('show');

        Route::prefix('website-settings')->name('website-settings.')->group(function () {
            Route::get('/', [WebsiteSettingController::class, 'edit'])->name('edit');
            Route::put('/info', [WebsiteSettingController::class, 'updateInfo'])->name('update-info');
            Route::put('/media', [WebsiteSettingController::class, 'updateMedia'])->name('update-media');
            Route::put('/headman', [WebsiteSettingController::class, 'updateHeadman'])->name('update-headman');
            Route::post('/staff', [WebsiteSettingController::class, 'storeStaff'])->name('staff.store');
            Route::put('/staff/{villageStaff}', [WebsiteSettingController::class, 'updateStaff'])->name('staff.update');
            Route::delete('/staff/{villageStaff}', [WebsiteSettingController::class, 'destroyStaff'])->name('staff.destroy');
        });
    });

    Route::get('/module/{module}', [DashboardController::class, 'placeholder'])->name('module.placeholder');
});
