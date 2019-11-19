<?php

namespace Stylemix\Translations\Http\Controllers;

use App\Http\Controllers\Controller;
use Stylemix\Translations\Contracts\TranslationString as TranslationStringContract;
use Stylemix\Translations\Manager;
use Stylemix\Translations\Models\TranslationString;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\Intl\Locales;

class StringsController extends Controller
{

	public function locales()
	{
		return collect(config('translations.available_locales'))
			->map(function ($locale) {
				return [
					'locale' => $locale,
					'name' => Str::ucfirst(Locales::getName($locale, app()->getLocale())),
					'native' => Str::ucfirst(Locales::getName($locale, $locale)),
				];
			});
	}

	public function index(Request $request, TranslationStringContract $string)
	{
		if (!is_array($locales = $request->get('locales'))) {
			$locales = explode(',', $locales);
		}

		$strings = $string->query()
			->get()
			->each(function (TranslationString $string) use ($locales) {
				$values = [];
				foreach ($locales as $locale) {
					$line = $this->translator()->getLineRaw($string->namespace, $string->group, $locale, $string->key);
					// Accidentally incomplete group keys could be registered.
					// They give array result instead of string
					// We can omit them here
					if (is_array($line)) {
						continue;
					}

					$values[$locale] = $line;
				}

				$string->values = $values;
			});

		return $strings;
	}

	/**
	 * @param \Illuminate\Http\Request $request
	 * @param \Stylemix\Translations\Models\TranslationString|TranslationStringContract $string
	 *
	 * @return \Illuminate\Http\JsonResponse
	 * @throws \Illuminate\Validation\ValidationException
	 */
	public function update(Request $request, TranslationStringContract $string)
	{
		$this->validate($request, [
			'values' => 'array',
			'values.*' => 'nullable|string',
		]);

		$updated = false;

		foreach ($request->values as $locale => $value) {
			$updated = $this->manager()->updateLine($string->namespace, $string->group, $locale, $string->key, $value) || $updated;
		}

		if ($updated) {
			$string->touch();
			$this->manager()->restartQueues();
		}

		$string->values = $request->values;

		return $string;
	}

	public function export(Request $request)
	{
		$groups = is_array($request->groups) ? $request->groups : explode(',', $request->groups);

		return $this->manager()->export($groups, $request->get('locale'));
	}

	/**
	 * @return \Stylemix\Translations\Translator
	 */
	protected function translator()
	{
		return app('translator');
	}

	/**
	 * @return \Stylemix\Translations\Manager
	 */
	protected function manager()
	{
		return app(Manager::class);
	}
}
