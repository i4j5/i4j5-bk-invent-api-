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

Route::any('send', function () {
    
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $order = $_POST['order'];
    $utm_medium = $_POST['utm_medium'];
    $utm_source = $_POST['utm_source'];
    $utm_campaign = $_POST['utm_campaign'];
    $utm_term = $_POST['utm_term'];
    $utm_content = $_POST['utm_content'];
    $comment = $_POST['comment'];
    $url = $_POST['url'];
    
     
    $comment = $comment . 
        "<br>
        <b>$order</b> <br>
        Имя: $name <br>
        Телефон: $phone <br>
        E-mail: $email <br>
        Страница захвата: $url <br> 
        Ключевое слово: $utm_term <br>
        ";

    $postData = [
        'TITLE' => $order,
        'NAME' => $name,
        'PHONE_MOBILE' => $phone,
        
        'LOGIN' => env('CRM_LOGIN'),
        'PASSWORD' => env('CRM_PASSWORD'),
        
        'UTM_CAMPAIGN' => $utm_campaign,	
        'UTM_CONTENT' => $utm_content,
        'UTM_MEDIUM' => $utm_medium,
        'UTM_SOURCE' => $utm_source,
        'UTM_TERM' => $utm_term,
        
        'COMMENTS' => $comment,
    ];
    
    if (preg_match("/^(?:[a-z0-9]+(?:[-_.]?[a-z0-9]+)?@[a-z0-9_.-]+(?:\.?[a-z0-9]+)?\.[a-z]{2,5})$/i", $email))
    {
      $postData['EMAIL_HOME'] = $email;
    }
    
    $fp = fsockopen("ssl://" . env('CRM_HOST'), env('CRM_PORT'), $errno, $errstr, 30);
    
    if ($fp) {
        $strPostData = '';
        foreach ($postData as $key => $value)
            $strPostData .= ($strPostData == '' ? '' : '&') . $key . '=' . urlencode($value);

        $str = "POST " . env('CRM_PATH') . " HTTP/1.0\r\n";
        $str .= "Host: " . env('CRM_HOST') . "\r\n";
        $str .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $str .= "Content-Length: " . strlen($strPostData) . "\r\n";
        $str .= "Connection: close\r\n\r\n";

        $str .= $strPostData;

        fwrite($fp, $str);

        $result = '';
        while (!feof($fp)) {
            $result .= fgets($fp, 128);
        }
        fclose($fp);

        $response = explode("\r\n\r\n", $result);

        $output = '<pre>' . print_r($response[1], 1) . '</pre>';
        
        return $output;
    } else {
        return 'Connection Failed! ' . $errstr . ' (' . $errno . ')';
    }

    return 'ok';
});


// WebHooks
Route::prefix('webhook')->group(function () {
    
    Route::any('test', function () {
        return 'ok!';
    });
   
    // Исправление ошибок в контактах
    Route::get('find-duplicates/{query?}', 'Webhooks\FindDuplicatesController@handle');
    
    Route::prefix('salesap')->group(function () {

        // Создание папки сделки на Google Drive
        Route::post('create-deal-folders', 'Webhooks\Salesap\CreateLeadFoldersController@handle');
        Route::post('incoming-call', 'API\SalesapController@incomingСall');
    });

    // prefix amo
    Route::prefix('amo')->group(function () {

        // Создание папки сделки на Google Drive
        Route::post('create-lead-folders', 'Webhooks\Amo\CreateLeadFoldersController@handle');
        
        // Создание проекта в asana
        Route::post('create-lead-project', 'Webhooks\Amo\CreatLeadProjectController@handle');
        
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
