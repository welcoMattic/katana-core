<?php

namespace Katana\Commands;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Command\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;
use Katana\SiteBuilder;

class BuildCommand extends Command
{
    /**
     * The FileSystem instance.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The view factory instance.
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
            ->addOption('env', null, InputOption::VALUE_REQUIRED, 'Application Environment.', 'default');
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
            $this->viewFactory,
            $input->getOption('env')
        );

        $siteBuilder->build();

        $output->writeln("<info>,.   ,.");
        $output->writeln("\.\ /,/");
        $output->writeln(" Y Y f");
        $output->writeln(" |. .|");
        $output->writeln("(\"_, l     Website was built successfully...");
        $output->writeln(" ,- , \\    Happy Easter!");
        $output->writeln("(_)(_) Y,.");
        $output->writeln(" _j _j |,'");
        $output->writeln("(_,(__,'</info>");

//        $output->writeln("<info>Site was generated successfully.</info>");
    }
}