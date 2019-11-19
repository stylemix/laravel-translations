<?php

namespace Stylemix\Translations;

use Illuminate\Translation\FileLoader;

class StorageLoader extends FileLoader
{

	/**
	 * @param array $lines
	 * @param string $locale
	 * @param string $group
	 * @param string $namespace
	 *
	 * @return boolean
	 */
	public function store($lines, $locale, $group, $namespace = null)
	{
		if ($group === '*' && $namespace === '*') {
			$file = "{$this->path}/{$locale}.json";
			$written = $this->files->put($file, json_encode($lines, JSON_PRETTY_PRINT));
		}
		else {
			$path = $locale . DIRECTORY_SEPARATOR . $group . '.php';
			$this->ensurePath($path);

			$output = "<?php\n\nreturn " . var_export($lines, true) . ";" . \PHP_EOL;
			$written = $this->files->put($this->path . DIRECTORY_SEPARATOR . $path, $output);
		}

		if ($written === false) {
			throw new \RuntimeException('Failed store translation into filesystem. Locale: ' . $locale . ', Group: ' . $group);
		}

		return $written;
	}

	protected function ensurePath($path)
	{
		$subfolders = explode(DIRECTORY_SEPARATOR, $path);
		array_pop($subfolders);

		$subfolder_level = '';
		foreach ($subfolders as $subfolder) {
			$subfolder_level = $subfolder_level . $subfolder . DIRECTORY_SEPARATOR;

			$temp_path = rtrim($this->path . DIRECTORY_SEPARATOR . $subfolder_level, DIRECTORY_SEPARATOR);
			if (!is_dir($temp_path)) {
				mkdir($temp_path, 0777, true);
			}
		}
	}
}
