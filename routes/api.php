<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


// WebHooks
Route::prefix('webhook')->group(function () {
    
    Route::any('test', function () {return 'ok!';});
   
    // Исправление ошибок в контактах
    Route::get('find-duplicates/{query?}', 'Webhooks\FindDuplicatesController@handle');
    
    Route::prefix('salesap')->group(function () {

        // Создание папки сделки на Google Drive
        Route::post('create-deal-folders', 'Webhooks\Salesap\CreateLeadFoldersController@handle');;
    });

    // prefix amo
    Route::prefix('amo')->group(function () {

        // Создание папки сделки на Google Drive
        Route::post('create-lead-folders', 'Webhooks\Amo\CreateLeadFoldersController@handle');
        
        // При переходе на этап НЕОБРАБОТАННЫЙ ЛИД
        Route::post('raw-lead', 'Webhooks\Amo\RawLeadController@handle');

        // При переходе на этап ЗАКРЫТО И НЕ РЕАЛИЗОВАНО
        Route::post('not-implemented', 'Webhooks\Amo\NotImplementedController@handle');

        // Контакты
        Route::post('contacts', 'Webhooks\Amo\ContactsController@handle');
    });
    
    // prefix roistat
    Route::prefix('roistat')->group(function () {

        // Ловец Лидов
        Route::post('lead-hunter', 'Webhooks\Roistat\LeadHunterController@handle');
        
        // Емейлтрекинг
        Route::post('emailtracking', 'Webhooks\Roistat\EmailTrackingController@handle');
    });
    
    Route::prefix('sipuni')->group(function () {
        
        // Обработка входящих звонков
        Route::get('incoming-call', 'Webhooks\Sipuni\IncomingCallController@getName');
        Route::get('incoming-call/redirection', 'Webhooks\Sipuni\IncomingCallController@redirection');
    });
});


// amoCRM
Route::prefix('amo')->group(function () {

    // Создание заявки с сайта
    Route::post('create-lead-from-form', 'API\AmoController@createLeadFromForm');
});

Route::prefix('salesap')->group(function () {

    // Создание заявки с сайта
    Route::post('create-lead-from-form', 'API\SalesapController@createLeadFromForm');
});
