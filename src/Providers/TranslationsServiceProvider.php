<?php
namespace Stylemix\Translations\Providers;

use Stylemix\Translations\Console\Commands\ImportCommand;
use Stylemix\Translations\Console\Commands\PublishCommand;
use Stylemix\Translations\Manager;
use Stylemix\Translations\StorageLoader;
use Stylemix\Translations\Translator;
use Illuminate\Translation\TranslationServiceProvider as BaseTranslationServiceProvider;

class TranslationsServiceProvider extends BaseTranslationServiceProvider
{

	public function register()
	{
		$this->mergeConfigFrom(__DIR__ . '/../resources/config/module.php', 'translations');

		$this->registerLoader();

		$this->app->singleton('translation.loader.storage', function ($app) {
			return new StorageLoader($app['files'], $app['config']['translations.path']);
		});

		$this->app->singleton('translator', function($app) {
			// When registering the translator component, we'll need to set the default
			// locale as well as the fallback locale. So, we'll grab the application
			// configuration so we can easily get both of these values from there.
			$locale = $app['config']['app.locale'];

			$trans = new Translator($app['translation.loader'], $app['translation.loader.storage'], $locale);

			$trans->setFallback($app['config']['app.fallback_locale']);
			$trans->setRegistering($app['config']['translations.auto_registering']);

			return $trans;
		});

		$this->app->singleton(Manager::class);

		if ($this->app->runningInConsole()) {
			$this->commands([
				ImportCommand::class,
				PublishCommand::class,
			]);
		}
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['translator', 'translation.loader', 'translation.loader.storage'];
	}

}
