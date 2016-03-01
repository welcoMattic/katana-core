<?php

namespace Katana;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\FileViewFinder;
use Illuminate\Events\Dispatcher;
use Illuminate\View\Factory;

class BuildCommand extends Command
{
    /**
     * The path to the views cache directory
     *
     * @var string
     */
    private $cacheDirectory;

    /**
     * The path to the vies source files
     *
     * @var string
     */
    private $sourceDirectory;

    /**
     * The path to where the site is generated
     *
     * @var string
     */
    private $siteDirectory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The view factory
     *
     * @var Factory
     */
    private $viewFactory;

    /**
     * The blade compiler
     *
     * @var BladeCompiler
     */
    private $bladeCompiler;

    /**
     * BuildCommand constructor.
     *
     * @param string $cacheDirectory
     * @param string $sourceDirectory
     * @param string $siteDirectory
     */
    public function __construct($cacheDirectory, $sourceDirectory, $siteDirectory)
    {
        $this->cacheDirectory = $cacheDirectory;
        $this->sourceDirectory = $sourceDirectory;
        $this->siteDirectory = $siteDirectory;

        $this->filesystem = new Filesystem();

        $this->bladeCompiler = $this->createBladeCompiler();

        $this->viewFactory = $this->createViewFactory();

        parent::__construct();
    }

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('build')
            ->setDescription('Generate the site static files.');
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        error_reporting(error_reporting() & ~E_NOTICE & ~E_WARNING);

        $siteBuilder = new SiteBuilder(
            $this->filesystem,
            $this->viewFactory,
            $this->sourceDirectory,
            $this->siteDirectory
        );

        $siteBuilder->build();

        $output->writeln("<info>It's done your grace.</info>");
    }

    /**
     * Create the view factory with a Blade Compiler.
     *
     * @return Factory
     */
    private function createViewFactory()
    {
        $resolver = new EngineResolver();

        $bladeCompiler = $this->bladeCompiler;

        $resolver->register('blade', function () use ($bladeCompiler) {
            return new CompilerEngine($bladeCompiler);
        });

        return new Factory(
            $resolver,
            new FileViewFinder($this->filesystem, [$this->sourceDirectory]),
            new Dispatcher()
        );
    }

    /**
     * Create the view factory with a Blade Compiler.
     *
     * @return BladeCompiler
     */
    private function createBladeCompiler()
    {
        if (! $this->filesystem->isDirectory($this->cacheDirectory)) {
            $this->filesystem->makeDirectory($this->cacheDirectory);
        }

        $blade = new Blade(
            new BladeCompiler($this->filesystem, $this->cacheDirectory)
        );

        return $blade->getCompiler();
    }
}