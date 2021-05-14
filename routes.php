<?php

use App\Routing\Route;

Route::get('', 'MainController@index')->name('index');

Route::get('category/{id}', 'MainController@category')->name('category');

Route::get('product/{id}', 'MainController@product')->name('product');

Route::get('test', 'MainController@test');

Route::any('', function (){
    return 'Not Found';
})->name('not-found');