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

Route::group(['middleware' => 'auth'], function(){

    Route::get('/home', 'HomeController@index')->name('home');

    Route::get('/phonebook', 'PhoneBookController@index')->name('phonebook');
    Route::get('/phonebook/update', 'PhoneBookController@update')->name('phonebook.update');
    Route::post('/phonebook', 'PhoneBookController@index')->name('phonebook.search');

    app(\App\PageRoutes::class)->routes();

    Route::get('storage/{filename}', function ($filename)
    {
        $path = storage_path('app\public\\' . $filename);

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
        Route::resource('pages', 'PagesController');
        Route::post('pages/image-upload', 'PagesController@imageUpload')->name('pages.image-upload');
    });
});
