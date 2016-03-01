<?php

namespace Katana;

use Katana\Commands\BuildCommand;
use Symfony\Component\Console\Application as SymfonyConsole;

class Katana
{
    /**
     * @var SymfonyConsole
     */
    private $application;

    /**
     * Cache directory path
     *
     * @var string
     */
    private $cacheDir;

    /**
     * Source directory path
     *
     * @var string
     */
    private $sourceDir;

    /**
     * Site directory path
     *
     * @var string
     */
    private $siteDir;

    /**
     * Katana constructor.
     *
     * @param SymfonyConsole $application
     */
    public function __construct(SymfonyConsole $application)
    {
        $this->application = $application;

        $this->cacheDir = getcwd().'/_cache';
        $this->sourceDir = getcwd().'/source';
        $this->siteDir = getcwd().'/site';
    }

    /**
     * Handle incoming console requests
     *
     * @return void
     */
    public function handle()
    {
        $this->registerCommands();

        $this->application->run();
    }

    /**
     * Register application commands
     *
     * @return void
     */
    private function registerCommands()
    {
        $this->application->addCommands([
            new BuildCommand($this->cacheDir, $this->sourceDir, $this->siteDir)
        ]);
    }
}