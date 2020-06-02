<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


// Авторизация
Auth::routes(['register' => false]);
Route::get('login', ['as' => 'login', 'uses' => 'Auth\LoginController@redirectToProvider']);
Route::get('login/google/callback', 'Auth\LoginController@handleProviderCallback')->name('login.google.callback');

Route::get('/phonebook/xml', 'PhoneBookController@xml')->name('phonebook.xml');
Route::get('/phonebook/update', 'PhoneBookController@update')->name('phonebook.update');


Route::get('email-banner/{contactid}', function ($contactid)
{
    $path = null;
    
    $files = Storage::disk('local')->files('public/mail/banners');

    foreach ($files as $key => $value) {
        if ($value == 'public/mail/banners/.gitignore') {
            unset($files[$key]);
        }
    }

    if(!$files) {
        $path = storage_path('app/public/mail/banner.png');
    } else {
        $i = array_rand($files);
        $path = storage_path('app/' . $files[$i]);
    }

    if (!File::exists($path)) {
        abort(404);
    }

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = Response::make($file, 200);
    $response->header('Content-Type', $type);

    return $response;
});

Route::group(['middleware' => 'auth'], function(){

    Route::get('/home', 'HomeController@index')->name('home');

    // Route::resource('phonebook', 'PhoneBookController', [
    //     //'only' => ['create', 'show']
    //     'only' => ['create', 'store']
    // ]);

    Route::get('/phonebook', 'PhoneBookController@index')->name('phonebook');
    Route::post('/phonebook', 'PhoneBookController@index')->name('phonebook.search');
    Route::get('/phonebook/create', 'PhoneBookController@create')->name('phonebook.create');
    Route::post('/phonebook/store', 'PhoneBookController@store')->name('phonebook.store');


    app(\App\PageRoutes::class)->routes();

    Route::get('storage/{filename}', function ($filename)
    {
        $path = storage_path('app/public/' . $filename);

        if (!File::exists($path)) {
            abort(404);
        }

        $file = File::get($path);
        $type = File::mimeType($path);

        $response = Response::make($file, 200);
        $response->header("Content-Type", $type);

        return $response;
    });

   

    Route::group(['middleware' => 'admin'], function(){
        Route::match(['get', 'post'], 'pages/sidebar', 'PagesController@sidebar')->name('pages.sidebar');
        Route::resource('pages', 'PagesController');
        Route::post('pages/image-upload', 'PagesController@imageUpload')->name('pages.image-upload');
        Route::get('tools/api/log', 'ToolsController@webhooksLog')->name('api.log');
    });
});
