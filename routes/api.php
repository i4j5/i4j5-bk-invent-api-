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
   
    Route::get('yandex-forms', 'Webhooks\YandexFormsController@balans');

    //amoCRM
    Route::prefix('amocrm')->group(function () {
        Route::get('moving-deal-folders', 'Webhooks\AmoCRMController@movingDealFolder');
        Route::post('create-deal-folders', 'Webhooks\AmoCRMController@createDealFolders');
        Route::post('create-deal-project', 'Webhooks\AmoCRMController@createDealProject');
        Route::post('update-deal-project', 'Webhooks\AmoCRMController@updateDealProject');
        Route::post('raw-lead', 'Webhooks\AmoCRMController@rawLead');
        Route::post('distribution-lead', 'Webhooks\AmoCRMController@distributionLead');
        Route::get('watcher/{action}', 'Webhooks\AmoCRMController@watcher');

        Route::post('unsorted', 'Webhooks\AmoCRMController@unsorted');

        Route::post('success-deal', 'Webhooks\AmoCRMController@successDeal');
        Route::get('email-banner', 'Webhooks\AmoCRMController@emailBanner');
        Route::any('deal/{event}', 'Webhooks\AmoCRMController@deal');
        Route::any('dd', 'Webhooks\AmoCRMController@dd');
    });

    Route::prefix('asana')->group(function () {
        Route::any('{deal_id}/{project_id}', 'Webhooks\AmoCRMController@asanaWebhook');
    });

    Route::get('find-duplicates', 'Webhooks\FindDuplicatesController@handle');
    
    Route::any('amocrm-whatsapp/{scope_id}', 'API\WhatsAppController@amocrmWebhook');
    Route::any('whatsapp', 'API\WhatsAppController@whatsappWebhook');
    // Route::any('dd', 'API\WhatsAppController@dd');

});


//Сайт
Route::prefix('site')->group(function () {

    // Создание заявки с сайта
    Route::post('create-lead-from-form', 'API\SiteController@createLeadFromForm');
    
    // Отзыв
    Route::post('create-review', 'API\SiteController@createReview');
    
    // Вопрос
    Route::post('create-question', 'API\SiteController@createQuestion');

    Route::get('e', 'API\SiteController@e');
});

Route::prefix('analytic')->group(function () {

    Route::post('create-visit', 'API\AnalyticController@createVisit');
    Route::post('update-visit', 'API\AnalyticController@updateVisit');
    Route::post('create-call', 'API\AnalyticController@createCall');

    Route::post('crm/{event}', 'API\AnalyticController@crm');
});

Route::prefix('amo')->group(function () {
    Route::get('auth', 'API\AmoController@auth');
    Route::get('refresh-token', 'API\AmoController@refreshToken');
});