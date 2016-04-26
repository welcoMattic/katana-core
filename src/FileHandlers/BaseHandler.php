<?php

namespace Katana\FileHandlers;

use Katana\MarkdownFileBuilder;
use Symfony\Component\Finder\SplFileInfo;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Factory;

class BaseHandler
{
    protected $filesystem;
    protected $viewFactory;

    /**
     * The view file.
     *
     * @var SplFileInfo
     */
    protected $file;

    /**
     * The path to the blade view.
     *
     * @var string
     */
    protected $viewPath;

    /**
     * the path to the generated directory.
     *
     * @var string
     */
    protected $directory;

    /**
     * Data to be passed to every view.
     *
     * @var array
     */
    public $viewsData = [];

    /**
     * The site configurations.
     *
     * @var array
     */
    private $configs;

    /**
     * AbstractHandler constructor.
     *
     * @param Filesystem $filesystem
     * @param Factory    $viewFactory
     */
    public function __construct(Filesystem $filesystem, Factory $viewFactory)
    {
        $this->filesystem = $filesystem;
        $this->viewFactory = $viewFactory;
    }

    /**
     * Convert a blade view into a site page.
     *
     * @param SplFileInfo $file
     * @param array       $configs
     */
    public function handle(SplFileInfo $file, array $configs)
    {
        $this->configs = $configs;

        $this->file = $file;

        $this->viewPath = $this->getViewPath();

        $this->directory = $this->getDirectoryPrettyName();

        $this->appendViewInformationToData();

        if (@$this->viewsData['enableBlog'] && @$this->viewsData['postsListView'] == $this->viewPath) {
            $this->prepareBlogIndexViewData();
        }

        $content = $this->getFileContent();

        $this->filesystem->put(
            sprintf(
                '%s/%s',
                $this->prepareAndGetDirectory(),
                ends_with($file->getFilename(), ['.blade.php', 'md']) ? 'index.html' : $file->getFilename()
            ),
            $content
        );
    }

    /**
     * Get the content of the file after rendering.
     *
     * @return string
     */
    private function getFileContent()
    {
        if (ends_with($this->file->getFilename(), '.blade.php')) {
            return $this->renderBlade();
        } elseif (ends_with($this->file->getFilename(), '.md')) {
            return $this->renderMarkdown();
        }

        return $this->file->getContents();
    }

    /**
     * Render the blade file.
     *
     * @return string
     */
    protected function renderBlade()
    {
        return $this->viewFactory->make($this->viewPath, $this->viewsData)->render();
    }

    /**
     * Render the markdown file.
     *
     * @return string
     */
    protected function renderMarkdown()
    {
        $markdownFileBuilder = new MarkdownFileBuilder($this->filesystem, $this->viewFactory, $this->file, $this->viewsData, $this->configs);

        return $markdownFileBuilder->render();
    }

    /**
     * Prepare and get the directory name for pretty URLs.
     *
     * @return string
     */
    private function prepareAndGetDirectory()
    {
        if (! $this->filesystem->isDirectory($this->directory)) {
            $this->filesystem->makeDirectory($this->directory, 0755, true);
        }

        return $this->directory;
    }

    /**
     * Generate directory path to be used for the file pretty name.
     *
     * @return string
     */
    protected function getDirectoryPrettyName()
    {
        $fileBaseName = $this->getFileName();

        $fileRelativePath = $this->normalizePath($this->file->getRelativePath());

        if (in_array($this->file->getExtension(), ['php', 'md']) && $fileBaseName != 'index') {
            $fileRelativePath .= $fileRelativePath ? "/$fileBaseName" : $fileBaseName;
        }

        return KATANA_PUBLIC_DIR.($fileRelativePath ? "/$fileRelativePath" : '');
    }

    /**
     * Get the path of the view.
     *
     * @return string
     */
    private function getViewPath()
    {
        return str_replace(['.blade.php', '.md'], '', $this->file->getRelativePathname());
    }

    /**
     * Prepare the data for the blog landing page.
     *
     * We will pass only the first n posts and a next page path.
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

    /**
     * Get the file name without the extension.
     *
     * @param SplFileInfo $file
     *
     * @return string
     */
    protected function getFileName(SplFileInfo $file = null)
    {
        $file = $file ?: $this->file;

        return str_replace(['.blade.php', '.php', '.md'], '', $file->getBasename());
    }

    /**
     * Append the view file information to the view data.
     *
     * @return void
     */
    private function appendViewInformationToData()
    {
        $this->viewsData['currentViewPath'] = $this->viewPath;

        $this->viewsData['currentUrlPath'] = ($path = str_replace(KATANA_PUBLIC_DIR, '', $this->directory)) ? $path : '/';
    }
    
    /**
     * Normalize Windows file paths to UNIX style
     *
     * @return string
     */
    protected function normalizePath($path)
    {
        return str_replace("\\", '/', $path);
    }
}
