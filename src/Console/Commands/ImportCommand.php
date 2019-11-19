<?php

namespace Stylemix\Translations\Console\Commands;

use Stylemix\Translations\Manager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class ImportCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'translations:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import translations from the lang directory';

    /** @var \Stylemix\Translations\Manager */
    protected $manager;

    public function __construct(Manager $manager)
    {
		parent::__construct();
		$this->manager = $manager;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
		$this->comment('Importing from lang files ...');
		$counter = $this->manager->importStrings();
		$this->info('Imported ' . $counter . ' new strings.');

		$this->comment('Searching for strings in source files ...');
		$counter = $this->manager->findStrings($this->option('path'));
		$this->info('Imported '. $counter . ' new strings.');
    }

	protected function getOptions()
	{
		return [
			['path', null, InputOption::VALUE_OPTIONAL, 'Base path where to search string in files'],
		];
	}

}
