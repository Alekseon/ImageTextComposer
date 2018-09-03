<?php
/**
 * Copyright © Alekseon sp. z o.o.
 * http://www.alekseon.com/
 */
class TextModificators
{
    /**
     * @param $text
     * @return string
     */
    public function tolower($text)
    {
        return strtolower($text);
    }

    /**
     * @param $text
     * @return string
     */
    public function toupper($text)
    {
        return strtoupper($text);
    }
}
