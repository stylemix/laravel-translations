<?php

use Illuminate\Support\Facades\Route;

Route::apiResource('strings', 'StringsController')
	->only(['index', 'update'])
	->parameter('strings', 'translation_string');

Route::get('locales', 'StringsController@locales')->name('locales');
Route::get('export', 'StringsController@export')->name('export');
