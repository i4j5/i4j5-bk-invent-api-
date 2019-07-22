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

Route::get('/home', 'HomeController@index')->name('home');

Route::get('/phonebook', 'PhoneBookController@index')->name('phonebook');
Route::get('/phonebook/update', 'PhoneBookController@update')->name('phonebook.update');
Route::post('/phonebook', 'PhoneBookController@index')->name('phonebook.search');
