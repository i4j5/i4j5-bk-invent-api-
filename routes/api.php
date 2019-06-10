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
Route::prefix('webhooks')->group(function () {
    
    // Исправление ошибок в контактах
    Route::get('fix-all-contacts', 'Webhooks\FixAllContactsController@handle');

    // prefix amo
    Route::prefix('amo')->group(function () {

        // Создание папки сделки на Google Drive
        Route::post('create-lead-folders', 'Webhooks\Amo\CreateLeadFoldersController@handle');
        
        // При переходе на этап НЕОБРАБОТАННЫЙ ЛИД
        Route::post('raw-lead', 'Webhooks\Amo\RawLeadController@handle');
    });
    
    // prefix roistat
    Route::prefix('roistat')->group(function () {
        Route::post('lead-hunter', 'Webhooks\Roistat\LeadHunterController@handle');
});


// amoCRM
Route::prefix('amo')->group(function () {
    Route::post('create-lead-from-form', 'API\AmoController@createLeadFromForm');
});