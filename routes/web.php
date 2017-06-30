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

Route::get('/wxbg', 'Admin\AdminController@index');
Route::get('/dl', 'Admin\AdminController@dl');
Route::get('/test', 'Admin\AdminController@test');
Route::get('/api/getdata', 'Admin\AdminController@getdata');
Route::get('/git/push', 'Git\GitOriginController@push');
