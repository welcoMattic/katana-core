<?php

namespace Katana\Commands;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Command\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Katana\SiteBuilder;

class BuildCommand extends Command
{
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
     * BuildCommand constructor.
     *
     * @param Factory $viewFactory
     * @param Filesystem $filesystem
     */
    public function __construct(Factory $viewFactory, Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;

        $this->viewFactory = $viewFactory;

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
            ->setDescription('Generate the site static files.')
            ->addArgument('base_url', InputArgument::OPTIONAL, 'The site base URL.', '/');
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
        $siteBuilder = new SiteBuilder(
            $this->filesystem,
            $this->viewFactory
        );

        $siteBuilder->setConfig('base_url', $input->getArgument('base_url'));

        $siteBuilder->build();

        $output->writeln("<info>Site was generated successfully.</info>");
    }
}