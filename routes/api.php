<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MobileAppController;

Route::prefix('v1')->group(function () {
    // Village Info & Setup
    Route::get('/village-info', [MobileAppController::class, 'villageInfo']);
    Route::get('/letter-types', [MobileAppController::class, 'letterTypes']);
    Route::get('/news', [MobileAppController::class, 'getNews']);
    
    // NIK & NOP Verification
    Route::get('/check-nik', [MobileAppController::class, 'checkNik']);
    Route::get('/search-nop', [MobileAppController::class, 'searchNop']);
    
    // Service Submission
    Route::post('/letters', [MobileAppController::class, 'storeLetter'])->middleware('throttle:service-submit');
    Route::post('/pbb', [MobileAppController::class, 'storePbb'])->middleware('throttle:service-submit');
    Route::post('/complaints', [MobileAppController::class, 'storeComplaint'])->middleware('throttle:service-submit');
    
    // Tracking/Search
    Route::get('/letters/search', [MobileAppController::class, 'searchLetterByTicket']);
    Route::get('/pbb/search', [MobileAppController::class, 'searchPbbByTicket']);
    Route::get('/complaints/search', [MobileAppController::class, 'searchComplaintByTicket']);
});
