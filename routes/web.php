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

Auth::routes();

Route::get('/', function () {
    return redirect('/login');
});

Route::group(['middleware' => 'web'], function () {

    Route::get('home', 'TicketsController@home');

    Route::get('user/edit', 'UsersController@edit');
    
    Route::post('user/update', 'UsersController@update');

    Route::get('tickets','TicketsController@index');

    Route::get('tickets/create','TicketsController@create');
    
    Route::get('tickets/import','ImportController@index');
    
    Route::post('tickets/import','ImportController@create');

    Route::get('tickets/edit/{id}','TicketsController@edit');
    
    Route::get('tickets/claim/{id}','TicketsController@claim');

    Route::get('tickets/clone/{id}','TicketsController@clone');

    Route::get('tickets/{id}','TicketsController@show');

    Route::post('tickets/estimate/{ticket_id}','TicketsController@estimate');    

    Route::post('tickets','TicketsController@store');

    // Route::get('board','TicketsController@board');

    Route::get('tickets/api/{id}','TicketsController@api');

    Route::post('tickets/upload','TicketsController@upload');

    Route::post('tickets/batch','TicketsController@batch');

    Route::post('tickets/update/{id}','TicketsController@update');

    Route::post('notes','TicketsController@note');    

    Route::get('notes/hide/{id}','NotesController@hide');

    Route::get('users/{id}','UsersController@show');

    Route::get('users/watch/{id}','UsersController@watch');

    Route::get('projects','ProjectsController@index');

    Route::get('projects/create','ProjectsController@create');

    Route::get('projects/edit/{id}','ProjectsController@edit');

    Route::post('projects/store/{id}','ProjectsController@store');

    Route::get('projects/show/{id}','ProjectsController@show');

    Route::get('milestone','MilestoneController@index');

    Route::get('milestone/create','MilestoneController@create');

    Route::get('milestone/edit/{id}','MilestoneController@edit');
    
    Route::get('milestone/print/{id}','MilestoneController@print');

    Route::post('milestone/store/{id}','MilestoneController@store');

    Route::get('milestone/show/{id}','MilestoneController@getShow');
    Route::get('milestone/report/{id}','MilestoneController@report');

     
    Route::get('releases','ReleaseController@index');    
    Route::get('release/create','ReleaseController@create');    
    Route::get('release/edit/{id}','ReleaseController@edit');    
    Route::post('release/edit/{id}','ReleaseController@put');    
    Route::post('release/store','ReleaseController@store'); 
    Route::get('release/{id}','ReleaseController@show');   

});
