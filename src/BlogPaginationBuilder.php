<?php

namespace Katana;

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;

class BlogPaginationBuilder
{
    private $filesystem;
    private $viewFactory;
    private $siteDirectory;
    private $viewsData;
    private $pagesData;

    /**
     * BlogPaginationBuilder constructor.
     *
     * @param Filesystem $filesystem
     * @param Factory $viewFactory
     * @param $siteDirectory
     * @param array $viewsData
     */
    public function __construct(Filesystem $filesystem, Factory $viewFactory, $siteDirectory, array $viewsData)
    {
        $this->filesystem = $filesystem;
        $this->viewFactory = $viewFactory;
        $this->siteDirectory = $siteDirectory;
        $this->viewsData = $viewsData;
    }

    /**
     * Build blog pagination directories
     *
     * @return void
     */
    public function build()
    {
        $view = $this->getPostsListView();

        $postsPerPage = @$this->viewsData['postsPerPage'] ?: 5;

        $this->pagesData = array_chunk($this->viewsData['blogPosts'], $postsPerPage);

        foreach ($this->pagesData as $pageIndex => $posts) {
            $this->buildPage($pageIndex, $view, $posts);
        }
    }

    /**
     * Get the name of the view to be used for pages
     *
     * @return mixed
     * @throws \Exception
     */
    private function getPostsListView()
    {
        if (! isset($this->viewsData['postsListView'])) {
            throw new \Exception('The postsListView config value is missing.');
        }

        if (! $this->viewFactory->exists($this->viewsData['postsListView'])) {
            throw new \Exception(sprintf('The "%s" view is not found. Make sure the postsListView configuration key is correct.', $this->viewsData['postsListView']));
        }

        return $this->viewsData['postsListView'];
    }

    /**
     * Build the view with list of posts for every page
     *
     * @param $pageIndex
     * @param $view
     * @param $posts
     *
     * @return void
     */
    private function buildPage($pageIndex, $view, $posts)
    {
        $viewData = array_merge(
            $this->viewsData,
            [
                'paginatedBlogPosts' => $posts,
                'previousPage' => isset($this->pagesData[$pageIndex - 1]) ? '/blog-page/'.($pageIndex) : null,
                'nextPage' => isset($this->pagesData[$pageIndex + 1]) ? '/blog-page/'.($pageIndex + 2) : null,
            ]
        );

        $pageContent = $this->viewFactory->make($view, $viewData)->render();

        $directory = sprintf('%s/blog-page/%d', $this->siteDirectory, $pageIndex + 1);

        $this->filesystem->makeDirectory($directory, 0755, true);

        $this->filesystem->put(
            sprintf('%s/%s', $directory, 'index.html'),
            $pageContent
        );
    }
}