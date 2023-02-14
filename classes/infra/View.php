<?php

/**
 * Copyright 2016-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

use Register\Value\HtmlString;

class View
{
    /** @var string */
    private $pluginFolder;

    /** @var array<string,string> */
    private $lang;

    /** @param array<string,string> $lang */
    public function __construct(string $pluginFolder, array $lang)
    {
        $this->pluginFolder = $pluginFolder;
        $this->lang = $lang;
    }

    /** @param scalar $args */
    public function text(string $key, ...$args): string
    {
        return $this->esc(sprintf($this->lang[$key], ...$args));
    }

    /** @param scalar $args */
    public function plural(string $key, int $count, ...$args): string
    {
        if ($count == 0) {
            $key .= '_0';
        } else {
            $key .= XH_numberSuffix($count);
        }
        return $this->esc(sprintf($this->lang[$key], ...$args));
    }

    /** @param array<string,mixed> $_data */
    public function render(string $_template, array $_data): string
    {
        $_template = "{$this->pluginFolder}views/{$_template}.php";
        extract($_data);
        ob_start();
        include $_template;
        return (string) ob_get_clean();
    }

    /** @param scalar|HtmlString $value */
    public function esc($value): string
    {
        if ($value instanceof HtmlString) {
            return (string) $value;
        }
        return XH_hsc((string) $value);
    }

    /** @param scalar $value */
    public function raw($value): string
    {
        return (string) $value;
    }

    /** @param scalar $args */
    public function message(string $type, string $message, ...$args): string
    {
        return XH_message($type, $message, ...$args);
    }
}
