<?php

use Illuminate\Http\Request;

use \Curl\Curl;

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
    
    Route::any('test', function () {
        return 'ok!';
    });
   
    //Битрикс24
    Route::prefix('bitrix24')->group(function () {
        Route::post('on-crm-deal-update', 'Webhooks\Bitrix24EventsController@onCrmDealUpdate');
    });

});


//Сайт
Route::prefix('site')->group(function () {

    // Создание заявки с сайта
    Route::post('create-lead-from-form', 'API\SiteController@createLeadFromForm');
});
