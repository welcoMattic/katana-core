<?php

namespace Katana;

class Markdown
{
    /**
     * Parse markdown
     *
     * @param $text
     *
     * @return string
     */
    static function parse($text)
    {
        $parser = new \Parsedown();

        $text = static::cleanLeadingSpace($text);

        return $parser->text($text);
    }

    /**
     * Remove initial leading space from each line
     *
     * Since @markdown can be inside any HTML element, there
     * might be a leading space, we remove that to be able
     * to render markdown in a clean and correct way.
     *
     * @param $text
     *
     * @return string
     */
    private static function cleanLeadingSpace($text)
    {
        $firstLine = explode("\n", $text)[0];

        preg_match('/^( *)/', $firstLine, $matches);

        $spaceCount = strlen($matches[1]);

        return preg_replace('/^[ ]{'.$spaceCount.'}/m', '', $text);
    }
}