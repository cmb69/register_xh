<?php

/**
 * Copyright 2016-2017 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

class HtmlString
{
    /**
     * @var string
     */
    private $value;

    /**
     * @param string $string
     */
    public function __construct($string)
    {
        $this->value = (string) $string;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }
}
