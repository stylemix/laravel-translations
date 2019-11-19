<?php

return [

	/**
	 * List of available locales for managing translations
	 */
	'available_locales' => [
		'en',
	],

	/**
	 * Path where admin translated strings are stored.
	 */
	'path' => storage_path('app/lang'),

	/**
	 * When true, every called translation string will be registered in database if not yet registered.
	 * Useful to handle dynamically generated strings or when new strings appears frequently.
	 */
	'auto_registering' => env('TRANSLATIONS_AUTO_REGISTERING', false),

	'routes' => [
		'prefix'     => 'api/translations',
		'middleware' => ['api', 'auth'],
		'files'      => ['api']
	],

	/**
	 * Exclude specific groups from Laravel Translation Manager.
	 * This is useful if, for example, you want to avoid editing the official Laravel language files.
	 *
	 * @type array
	 *
	 *    array(
	 *        'pagination',
	 *        'reminders',
	 *        'validation',
	 *    )
	 */
	'exclude_groups' => [],

	/**
	 * By default string finder will search in this paths
	 */
	'find_paths' => [
		'app',
		'resources/views',
	],

	/**
	 * By default string finder will search in this paths
	 */
	'find_names' => [
		'*.php',
		'*.js',
		'*.vue',
	],

	/**
	 * When searching strings in source files, the finder will check these functions.
	 */
	'trans_functions' => [
		// php
		'trans',
		'trans_choice',
		'Lang::get',
		'Lang::choice',
		'Lang::trans',
		'Lang::transChoice',

		// blade
		'@lang',
		'@choice',
		'__',

		// javascript
		'$t',
		'$trans.get',
	],

	/**
	 * Directory path where exported messages for JS will be stored
	 */
	'js_path' => public_path('lang'),

];
