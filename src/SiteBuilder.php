<?php

namespace Katana;

use Symfony\Component\Finder\SplFileInfo;
use Katana\FileHandlers\BlogPostHandler;
use Illuminate\Filesystem\Filesystem;
use Katana\FileHandlers\BaseHandler;
use Illuminate\View\Factory;
use Illuminate\Support\Str;

class SiteBuilder
{
    private $filesystem;
    private $viewFactory;
    private $blogPostHandler;
    private $fileHandler;

    /**
     * The site configurations
     *
     * @var array
     */
    private $configs;

    /**
     * The data included in every view file of a post
     *
     * @var array
     */
    private $postsData;

    /**
     * The data to pass to every view
     *
     * @var array
     */
    private $viewsData;

    /**
     * The directory that contains blade sub views
     *
     * @var array
     */
    protected $includesDirectory = '_includes';

    /**
     * The directory that contains blog posts
     *
     * @var array
     */
    protected $blogDirectory = '_blog';

    /**
     * SiteBuilder constructor.
     *
     * @param Filesystem $filesystem
     * @param Factory $viewFactory
     */
    public function __construct(Filesystem $filesystem, Factory $viewFactory)
    {
        $this->filesystem = $filesystem;

        $this->viewFactory = $viewFactory;

        $this->fileHandler = new BaseHandler($filesystem, $viewFactory);

        $this->blogPostHandler = new BlogPostHandler($filesystem, $viewFactory);
    }

    /**
     * Build the site from blade views
     *
     * @return void
     */
    public function build()
    {
        $files = $this->getSiteFiles();

        $blogPostsFiles = array_filter($files, function ($file) {
            return $file->getRelativePath() == $this->blogDirectory;
        });

        $otherFiles = array_filter($files, function ($file) {
            return $file->getRelativePath() != $this->blogDirectory;
        });

        $this->readConfigs();

        if (@$this->configs['enableBlog']) {
            $this->readBlogPostsData($blogPostsFiles);
        }

        $this->buildViewsData();

        $this->filesystem->cleanDirectory(KATANA_PUBLIC_DIR);

        $this->handleSiteFiles($otherFiles);

        if (@$this->configs['enableBlog']) {
            $this->handleBlogPostsFiles($blogPostsFiles);
            $this->buildBlogPagination();
        }
    }

    /**
     * Set a configuration value
     *
     * @param $key
     * @param $value
     *
     * @return void
     */
    public function setConfig($key, $value)
    {
        $this->configs[$key] = $value;
    }

    /**
     * Handle site pages
     *
     * @param array $files
     *
     * @return void
     */
    private function handleSiteFiles($files)
    {
        foreach ($files as $file) {
            $this->fileHandler->handle($file);
        }
    }

    /**
     * Handle blog posts
     *
     * @param array $files
     *
     * @return void
     */
    private function handleBlogPostsFiles($files)
    {
        foreach ($files as $file) {
            $this->blogPostHandler->handle($file);
        }
    }

    /**
     * Get the site pages that'll be generated
     *
     * @return SplFileInfo[]
     */
    private function getSiteFiles()
    {
        return array_filter($this->filesystem->allFiles(KATANA_CONTENT_DIR), function (SplFileInfo $file) {
            return ! Str::startsWith($file->getRelativePathName(), $this->includesDirectory);
        });
    }

    /**
     * Read the data of every blog post
     *
     * @param array $files
     *
     * @return void
     */
    private function readBlogPostsData($files)
    {
        foreach ($files as $file) {
            $this->postsData[] = $this->blogPostHandler->getPostData($file);
        }
    }

    /**
     * Read site configs
     *
     * @return void
     */
    private function readConfigs()
    {
        $this->configs = array_merge(include getcwd().'/config.php', $this->configs);
    }

    /**
     * Build array of data to be passed to every view
     *
     * @return void
     */
    private function buildViewsData()
    {
        $this->viewsData = $this->configs + ['blogPosts' => array_reverse((array) $this->postsData)];

        $this->fileHandler->viewsData = $this->viewsData;

        $this->blogPostHandler->viewsData = $this->viewsData;
    }

    /**
     * Build the blog pagination directories
     *
     * @return void
     */
    private function buildBlogPagination()
    {
        $builder = new BlogPaginationBuilder(
            $this->filesystem,
            $this->viewFactory,
            $this->viewsData
        );

        $builder->build();
    }
}