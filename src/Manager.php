<?php

namespace Stylemix\Translations;

use Stylemix\Translations\Models\TranslationString;
use Stylemix\Translations\Models\TranslationStringProxy;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Queue\Console\RestartCommand;
use Illuminate\Support\Arr;
use Laravel\Horizon\Console\TerminateCommand;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class Manager
{

	/**
	 * @var \Illuminate\Contracts\Foundation\Application
	 */
	protected $app;

	protected $config;

	/** @var \Illuminate\Filesystem\Filesystem */
	protected $files;

	/** @var \Stylemix\Translations\StorageLoader */
	protected $storage;

	public function __construct(Application $app, Filesystem $files)
	{
		$this->app     = $app;
		$this->files   = $files;
		$this->config  = $app['config']['translations'];
		$this->storage = $app['translation.loader.storage'];
	}

	/**
	 * Import language lines from lang directory
	 *
	 * @param string $base Base path for searching files
	 *
	 * @return int Number of found lines
	 */
	public function importStrings($base = null)
	{
		$counter = 0;
		//allows for vendor lang files to be properly recorded through recursion.
		$vendor = true;
		if ($base == null) {
			$base   = $this->app['path.lang'];
			$vendor = false;
		}

		foreach ($this->files->directories($base) as $langPath) {
			$locale = basename($langPath);

			//import langfiles for each vendor
			if ($locale == 'vendor') {
//				foreach ($this->files->directories($langPath) as $vendor) {
//					$counter += $this->importTranslations($vendor);
//				}
				continue;
			}

			$vendorName = $this->files->name($this->files->dirname($langPath));

			foreach ($this->files->allfiles($langPath) as $file) {
				$info  = pathinfo($file);
				$group = $info['filename'];

				if (in_array($group, $this->config['exclude_groups'])) {
					continue;
				}
				$subLangPath = str_replace($langPath . DIRECTORY_SEPARATOR, '', $info['dirname']);
				$subLangPath = str_replace(DIRECTORY_SEPARATOR, '/', $subLangPath);
				$langPath    = str_replace(DIRECTORY_SEPARATOR, '/', $langPath);

				if ($subLangPath != $langPath) {
					$group = $subLangPath . '/' . $group;
				}

				if (!$vendor) {
					$translations = $this->app['translation.loader']->load($locale, $group);
				}
				else {
					$translations = include($file);
					$group        = "vendor/" . $vendorName;
				}

				if ($translations && is_array($translations)) {
					foreach (array_dot($translations) as $key => $value) {
						$imported = $this->registerString($group, $key);
						$counter  += $imported ? 1 : 0;
					}
				}
			}
		}

		foreach ($this->files->files($this->app['path.lang']) as $jsonTranslationFile) {
			if (strpos($jsonTranslationFile, '.json') === false) {
				continue;
			}
			$locale = basename($jsonTranslationFile, '.json');
			$translations = $this->app['translation.loader']->load($locale, '*', '*'); // Retrieves JSON entries of the given locale only

			if ($translations && is_array($translations)) {
				foreach ($translations as $key => $value) {
					$imported = $this->registerString('*', $key);
					$counter  += $imported ? 1 : 0;
				}
			}
		}

		return $counter;
	}

	/**
	 * Find translations strings in path
	 *
	 * @param string $path
	 *
	 * @return int
	 */
	public function findStrings($path = null)
	{
		$paths = Arr::wrap($path ?: $this->config['find_paths']);
		if (empty($paths)) {
			return 0;
		}

		$groupKeys  = [];
		$stringKeys = [];
		$functions  = array_map('preg_quote', Arr::wrap($this->config['trans_functions']));

		$pattern =
			"[^\w]" .                                       // Must not have an alphanum before real method
			'(' . implode('|', $functions) . ')' .    // Must start with one of the functions
			"\(" .                                          // Match opening parenthesis
			"(?P<quote>['\"])" .                            // Match " or ' and store in {quote}
			"(?P<string>(?:\\\k{quote}|(?!\k{quote}).)*)" . // Match any string that can be {quote} escaped
			"\k{quote}" .                                   // Match " or ' previously matched
			"[\),]";                                        // Close parentheses or new parameter

		// Find all PHP + Twig files in the app folder, except for storage
		$finder = new Finder();
		foreach ($paths as $path) {
			$finder->in(base_path($path));
		}

		$finder
			->ignoreVCS(true)
			->exclude('storage')
			->exclude('vendor')
			->name(Arr::wrap($this->config['find_names']))
			->files();

		/** @var \Symfony\Component\Finder\SplFileInfo $file */
		foreach ($finder as $file) {
			if (preg_match_all("/$pattern/siU", $file->getContents(), $matches)) {
				foreach ($matches['string'] as $key) {
					if ($this->isGroupKey($key)) {
						$groupKeys[] = $key;
					}
					else {
						$stringKeys[] = $key;
					}
				}
			}
		}

		// Remove duplicates
		$groupKeys  = array_unique($groupKeys);
		$stringKeys = array_unique($stringKeys);
		$counter    = 0;

		// Add the translations to the database
		foreach ($groupKeys as $key) {
			// Split the group and item
			list($group, $item) = explode('.', $key, 2);
			$imported = $this->registerString($group, $item);
			$counter  += (int) $imported;
		}

		foreach ($stringKeys as $key) {
			$imported = $this->registerString('*', $key);
			$counter  += (int) $imported;
		}

		// Return the number of found translations
		return $counter;
	}

	/**
	 * Register new string into database
	 *
	 * @param string $group
	 * @param string $key
	 * @param string $namespace
	 *
	 * @return bool
	 */
	public function registerString($group, $key, $namespace = '*')
	{
		if (!$group || !$key) {
			return false;
		}

		if ($namespace && !in_array($namespace, ['*'])) {
			return false;
		}

		/** @var TranslationString $string */
		$string = TranslationStringProxy::modelClass()::firstOrCreate([
			'namespace' => $namespace,
			'group' => $group,
			'key' => $key,
		]);

		return $string->wasRecentlyCreated;
	}

	public function updateLine($namespace, $group, $locale, $item, $value)
	{
		$lines = $this->storage->load($locale, $group, $namespace);

		if (Arr::get($lines, $item) === $value) {
			return true;
		}

		Arr::set($lines, $item, $value);

		$stored = $this->storage->store($lines, $locale, $group, $namespace);

		return $stored;
	}

	/**
	 * Export translated lines for given locale without applying replacements.
	 * Used to provide translations to another framework.
	 *
	 * @param array $groups
	 * @param string $locale
	 *
	 * @return array
	 */
	public function export($groups = ['*'], $locale = null)
	{
		/** @var \Stylemix\Translations\Translator $translator */
		$translator = $this->app['translator'];

		$result = [];

		foreach ($groups as $group) {
			$_namespace = '*';
			$_group = $group;
			if (strpos($group, '::') !== false) {
				list($_namespace, $_group) = explode('::', $group);
			}

			$lines = $translator->getLineRaw($_namespace, $_group, $locale ?? $this->app->getLocale(), null);
			if ($group == '*') {
				$result = array_merge($result, $lines);
			}
			else {
				$result += [$group => $lines];
			}
		}

		return $result;
	}

	/**
	 * Guesses that key type is group like 'group.key' not like 'Some complex sentence'.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	protected function isGroupKey($key)
	{
		return strpos($key, ' ') === false
			&& preg_match("/[a-z0-9_-]\.[a-z0-9_-]/i", $key);
	}

	/**
	 * Restart queues workers (Horizon, Simple queue)
	 */
	public function restartQueues()
	{
		$output = new BufferedOutput();

		if (class_exists('Laravel\Horizon\Console\TerminateCommand')) {
			$this->runConsoleCommand(TerminateCommand::class, $output);
		}

		if (class_exists('Illuminate\Queue\Console\RestartCommand')) {
			$this->runConsoleCommand(RestartCommand::class, $output);
		}
	}

	protected function runConsoleCommand($command, OutputInterface $output)
	{
		$cmd = app($command);
		$cmd->setLaravel(app());
		$code = $cmd->run(new StringInput(''), $output);

		if ($code !== 0) {
			throw new \RuntimeException($output->fetch());
		}
	}
}
