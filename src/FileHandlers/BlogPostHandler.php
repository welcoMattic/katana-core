<?php

namespace Katana\FileHandlers;

use Symfony\Component\Finder\SplFileInfo;

class BlogPostHandler extends BaseHandler
{
    /**
     * Get the blog post data
     *
     * @param SplFileInfo $file
     *
     * @return \stdClass
     */
    public function getPostData(SplFileInfo $file)
    {
        $view = $this->viewFactory->make(str_replace('.blade.php', '', $file->getRelativePathname()));

        $postData = [];

        $view->render(function ($view) use (&$postData) {
            $postData = array_where($view->getFactory()->getSections(), function ($title) {
                return starts_with($title, 'post::');
            });
        });

        // Remove 'post::' from $postData keys
        foreach ($postData as $key => $val) {
            $postData[str_replace('post::', '', $key)] = $val;

            unset($postData[$key]);
        }

        $postData['path'] = '/'.$this->getBlogPostSlug($file->getBasename('.blade.php'));

        return json_decode(json_encode($postData), false);
    }

    /**
     * Generate directory path to be used for the file pretty name
     *
     * @return string
     */
    protected function getDirectoryPrettyName()
    {
        $fileBaseName = $this->file->getBasename('.blade.php');

        $fileRelativePath = $this->getBlogPostSlug($fileBaseName);

        return $this->siteDirectory."/$fileRelativePath";
    }

    /**
     * Generate blog post slug
     *
     * @param string $fileBaseName
     *
     * @return string
     */
    private function getBlogPostSlug($fileBaseName)
    {
        preg_match('/^(\d{4}-\d{2}-\d{2})-(.*)/', $fileBaseName, $matches);

        return $matches[2].'-'.str_replace('-', '', $matches[1]);
    }
}