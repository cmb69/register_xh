<?php

/**
 * Copyright 2016-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Value\HtmlString;

class View
{
    /** @var string */
    private $pluginFolder;

    /** @var array<string,string> */
    private $lang;

    /**
     * @param array<string,string> $lang
     */
    public function __construct(string $pluginFolder, array $lang)
    {
        $this->pluginFolder = $pluginFolder;
        $this->lang = $lang;
    }

    /**
     * @param string $key
     * @return string
     */
    public function text($key)
    {
        $args = func_get_args();
        array_shift($args);
        return $this->esc(vsprintf($this->lang[$key], $args));
    }

    /**
     * @param string $key
     * @param int $count
     */
    public function plural($key, $count): string
    {
        if ($count == 0) {
            $key .= '_0';
        } else {
            $key .= XH_numberSuffix($count);
        }
        $args = func_get_args();
        array_shift($args);
        return $this->esc(vsprintf($this->lang[$key], $args));
    }

    /**
     * @param array<string,mixed> $_data
     */
    public function render(string $_template, array $_data): string
    {
        $_template = "{$this->pluginFolder}views/{$_template}.php";
        extract($_data);
        ob_start();
        include $_template;
        return (string) ob_get_clean();
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function esc($value)
    {
        if ($value instanceof HtmlString) {
            return $value;
        } else {
            return XH_hsc($value);
        }
    }

    /**
     * @param mixed $args
     */
    public function message(string $type, string $message, ...$args): string
    {
        return XH_message($type, $message, ...$args);
    }
}
