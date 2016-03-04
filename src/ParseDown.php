<?php

namespace Katana;

class ParseDown extends \Parsedown
{
    protected $codeBlockLeadingSpaceCount = 0;

    /**
     * Handling blocks of code indicated by 4 leading spaces
     *
     * @param $Line
     * @param null $Block
     */
    protected function blockCode($Line, $Block = null)
    {
        // Here we disable this markdown feature because the
        // new blade directive @markdown might be placed in
        // the document after several spaces initially so
        // if we left that feature all markdown will be
        // rendered as code blocks which is not fine.
        return;
    }

    /**
     * Handling blocks of code indicated by ``` and ```
     *
     * @param $Line
     * @param $Block
     */
    protected function blockFencedCodeContinue($Line, $Block)
    {
        // We check the number leading spaces before the first
        // line of code and add it to a class property.
        if ($this->codeBlockLeadingSpaceCount === 0) {
            preg_match('/^( *)/', $Line['body'], $matches);

            $this->codeBlockLeadingSpaceCount = strlen($matches[1]);
        }

        // Use the new property to trim the first x spaces of each
        // code line to make the code look tidy without extra
        // leading spaces caused by markup.
        $Line['body'] = preg_replace('/^[ ]{'.$this->codeBlockLeadingSpaceCount.'}/m', '', $Line['body']);

        return parent::blockFencedCodeContinue($Line, $Block);
    }

}