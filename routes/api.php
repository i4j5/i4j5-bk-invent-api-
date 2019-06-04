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

Route::prefix('webhooks')->group(function () {
    Route::post('amocrm2google-drive', 'Google\GoogleDriveController@amoWebhook');
    Route::post('amocrm2google-drive--delete', 'Google\GoogleDriveController@amoWebhookDelete');
    Route::post('amocrm-fix-phone', 'Amo\WebhooksController@rawLead');
});

Route::prefix('amo')->group(function () {
    Route::post('add', 'Amo\UnsortedController@add');
    Route::get('fix-all-phones', 'Amo\ContactController@fixAllPhones');
});