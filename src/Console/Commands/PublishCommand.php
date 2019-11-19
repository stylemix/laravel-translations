<?php

namespace Stylemix\Translations\Console\Commands;

use Stylemix\Translations\Manager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class PublishCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'translations:publish';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Publish translation files for JS';

	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		$groups = Config::get('translations.js_groups', ['*']);
		$locales = Config::get('translations.available_locales', ['en']);

		foreach ($locales as $locale) {
			$strings = $this->manager()->export($groups, $locale);
			$file = $this->createPath($locale . '.json');
			File::put($file, json_encode($strings));
			$this->line('Messages for [' . $locale . '] published.');
		}
	}

	/**
	 * Create full file path.
	 * This method will also generate the directories if they don't exist already.
	 *
	 * @param string $filename
	 *
	 * @return string $path
	 */
	protected function createPath($filename)
	{
		$dir = Config::get('translations.js_path');
		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}

		return rtrim($dir, '/') . '/' . $filename;
	}

	/**
	 * @return \Stylemix\Translations\Manager
	 */
	protected function manager()
	{
		return app(Manager::class);
	}
}
