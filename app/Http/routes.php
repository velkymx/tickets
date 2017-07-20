<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::group(['middleware' => 'web'], function () {

    Route::auth();

    Route::get('/home', 'HomeController@index');

    Route::get('tickets','TicketsController@index');

    Route::get('tickets/create','TicketsController@create');

    Route::get('tickets/edit/{id}','TicketsController@edit');

    Route::get('tickets/{id}','TicketsController@show');

    Route::post('tickets','TicketsController@store');

    Route::post('tickets/upload','TicketsController@upload');

    Route::post('tickets/update/{id}','TicketsController@update');

    Route::post('notes','NotesController@store');

    Route::get('notes/hide/{id}','NotesController@hide');

    Route::get('users/{id}','UsersController@show');

    Route::get('projects','ProjectsController@index');

    Route::get('projects/create','ProjectsController@create');

    Route::post('projects/store/{id}','ProjectsController@store');

    Route::get('projects/show/{id}','ProjectsController@show');

});
