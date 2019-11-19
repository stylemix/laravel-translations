<?php

namespace Stylemix\Translations;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\Arr;
use Illuminate\Translation\Translator as LaravelTranslator;

class Translator extends LaravelTranslator
{

	protected $registering = true;

	/**
	 * @var \Illuminate\Contracts\Translation\Loader
	 */
	protected $storage;

	public function __construct(Loader $loader, Loader $storage, string $locale)
	{
		parent::__construct($loader, $locale);

		$this->storage = $storage;
	}

	/**
	 * @inheritdoc
	 */
	protected function getLine($namespace, $group, $locale, $item, array $replace)
	{
		$line = parent::getLine($namespace, $group, $locale, $item, $replace);

		if ($this->registering) {
			// To avoid registering keys that give array results
			// Check for incomplete key
			if (!$this->isIncompleteKey($namespace, $group, $item)) {
				$this->manager()->registerString($group, $item, $namespace);
			}
		}

		return $line;
	}

	public function getFromJson($key, array $replace = [], $locale = null)
	{
		$locale = $locale ?: $this->locale;

		// For JSON translations, there is only one file per locale, so we will simply load
		// that file and then we will be ready to check the array for the key. These are
		// only one level deep so we do not need to do any fancy searching through it.
		$this->load('*', '*', $locale);

		$line = $this->loaded['*']['*'][$locale][$key] ?? null;

		// If we can't find a translation for the JSON key, we will attempt to translate it
		// using the typical translation file. This way developers can always just use a
		// helper such as __ instead of having to pick between trans or __ with views.
		if (! isset($line) && $this->isGroupKey($key)) {
			$fallback = $this->get($key, $replace, $locale);

			if ($fallback !== $key) {
				return $fallback;
			}
		}

		if ($this->registering) {
			$this->manager()->registerString('*', $key, '*');
		}

		return $this->makeReplacements($line ?: $key, $replace);
	}

	/**
	 * Load the specified language group.
	 *
	 * @param string $namespace
	 * @param string $group
	 * @param string $locale
	 * @param bool $force Load even if already loaded
	 *
	 * @return void
	 */
	public function load($namespace, $group, $locale, $force = false)
	{
		if (!$force && $this->isLoaded($namespace, $group, $locale)) {
			return;
		}

		// The loader is responsible for returning the array of language lines for the
		// given namespace, group, and locale. We'll set the lines in this array of
		// lines that have already been loaded so that we can easily access them.
		$lines = $this->loader->load($locale, $group, $namespace);

		// The storage loader return custom strings from storage files
		// We'll recursively merge into existing lines
		$overridden = $this->storage->load($locale, $group, $namespace);
		$lines = array_replace_recursive($lines, $overridden);

		$this->loaded[$namespace][$group][$locale] = $lines;
	}

	/**
	 * Get translation line without applying replacements
	 *
	 * @param string $namespace
	 * @param string $group
	 * @param string $locale
	 * @param string $item
	 *
	 * @return mixed
	 */
	public function getLineRaw($namespace, $group, $locale, $item)
	{
		$this->load($namespace, $group, $locale);

		return Arr::get($this->loaded[$namespace][$group][$locale], $item);
	}

	/**
	 * @return bool
	 */
	public function isRegistering() : bool
	{
		return $this->registering;
	}

	/**
	 * @param bool $registering
	 */
	public function setRegistering(bool $registering) : void
	{
		$this->registering = $registering;
	}

	/**
	 * @return \Stylemix\Translations\Manager
	 */
	protected function manager()
	{
		return app(Manager::class);
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
	 * @param $namespace
	 * @param $group
	 * @param $item
	 *
	 * @return bool
	 */
	protected function isIncompleteKey($namespace, $group, $item) : bool
	{
		if ($group === '*') {
			return false;
		}

		$this->load($namespace, $group, $this->fallback);

		return is_array(Arr::get($this->loaded[$namespace][$group][$this->fallback], $item));
	}

}
