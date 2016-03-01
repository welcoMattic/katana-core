<?php

namespace Katana\FileHandlers;

use Symfony\Component\Finder\SplFileInfo;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;

class BaseHandler
{
    protected $filesystem;
    protected $viewFactory;
    protected $siteDirectory;

    /**
     * The view file
     *
     * @var SplFileInfo
     */
    protected $file;

    /**
     * The path to the blade view
     *
     * @var string
     */
    protected $viewPath;

    /**
     * Data to be passed to every view
     *
     * @var array
     */
    public $viewsData = [];

    /**
     * AbstractHandler constructor.
     *
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem, Factory $viewFactory, $siteDirectory)
    {
        $this->filesystem = $filesystem;
        $this->viewFactory = $viewFactory;
        $this->siteDirectory = $siteDirectory;
    }

    /**
     * @param SplFileInfo $file
     *
     * @return void
     */
    public function handle(SplFileInfo $file)
    {
        $this->file = $file;

        $this->viewPath = $this->getViewPath();

        if (@$this->viewsData['enableBlog'] && @$this->viewsData['postsListView'] == $this->viewPath) {
            $this->prepareBlogIndexViewData();
        }

        $content = $this->getFileContent();

        $this->filesystem->put(
            sprintf(
                '%s/%s',
                $this->prepareAndGetDirectory(),
                $file->getExtension() == 'php' ? 'index.html' : $file->getFilename()
            ),
            $content
        );
    }

    /**
     * Get the content of a file after rendering
     *
     * @param SplFileInfo $file
     *
     * @return string
     */
    private function getFileContent()
    {
        if (ends_with($this->file->getFilename(), '.blade.php')) {
            return $this->renderBlade($this->file);
        }

        return $this->file->getContents();
    }

    /**
     * Render the blade file
     *
     * @return string
     */
    protected function renderBlade()
    {
        return $this->viewFactory->make($this->viewPath, $this->viewsData)->render();
    }

    /**
     * Prepare and get the directory name for pretty links
     *
     * @return string
     */
    private function prepareAndGetDirectory()
    {
        $directory = $this->getDirectoryPrettyName();

        if (! $this->filesystem->isDirectory($directory)) {
            $this->filesystem->makeDirectory($directory, 0755, true);
        }

        return $directory;
    }

    /**
     * Generate directory path to be used for the file pretty name
     *
     * @return string
     */
    protected function getDirectoryPrettyName()
    {
        $fileBaseName = $this->file->getBasename('.blade.php');

        $fileRelativePath = $this->file->getRelativePath();

        if ($this->file->getExtension() == 'php' && $fileBaseName != 'index') {
            $fileRelativePath .= $fileRelativePath ? "/$fileBaseName" : $fileBaseName;
        }

        return $this->siteDirectory.($fileRelativePath ? "/$fileRelativePath" : '');
    }

    /**
     * Get the path of the view
     *
     * @return string
     */
    private function getViewPath()
    {
        return str_replace('.blade.php', '', $this->file->getRelativePathname());
    }

    /**
     * Prepare the data for the blog landing page
     *
     * We will pass only the first n posts and a next page path
     *
     * @return void
     */
    private function prepareBlogIndexViewData()
    {
        $postsPerPage = @$this->viewsData['postsPerPage'] ?: 5;

        $this->viewsData['nextPage'] = count($this->viewsData['blogPosts']) > $postsPerPage ? '/blog-page/2' : null;

        $this->viewsData['previousPage'] = null;

        $this->viewsData['paginatedBlogPosts'] = array_slice($this->viewsData['blogPosts'], 0, $postsPerPage, true);
    }
}