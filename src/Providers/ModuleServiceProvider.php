<?php

namespace Stylemix\Translations\Providers;

use Konekt\Concord\BaseModuleServiceProvider;
use Stylemix\Translations\Models\TranslationString;

class ModuleServiceProvider extends BaseModuleServiceProvider
{

	protected $models = [
		TranslationString::class,
	];

	public function register()
	{
		parent::register();
	}

	public function boot()
	{
		parent::boot();

		$this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'translations');
	}

}
