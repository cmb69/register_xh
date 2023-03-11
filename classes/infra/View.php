<?php

/**
 * Copyright 2016-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

use Register\Value\Html;

class View
{
    /** @var string */
    private $pluginFolder;

    /** @var array<string,string> */
    private $text;

    /** @param array<string,string> $text */
    public function __construct(string $pluginFolder, array $text)
    {
        $this->pluginFolder = $pluginFolder;
        $this->text = $text;
    }

    /** @param scalar $args */
    public function text(string $key, ...$args): string
    {
        return $this->esc(sprintf($this->text[$key], ...$args));
    }

    /** @param scalar $args */
    public function plural(string $key, int $count, ...$args): string
    {
        if ($count == 0) {
            $key .= '_0';
        } else {
            $key .= XH_numberSuffix($count);
        }
        return $this->esc(sprintf($this->text[$key], $count, ...$args));
    }

    /** @param scalar $args */
    public function message(string $type, string $key, ...$args): string
    {
        return "\n<p class=\"xh_$type\">" . $this->text($key, ...$args) . "</p>";
    }

    /** @param scalar $args */
    public function messagep(string $type, int $count, string $key, ...$args): string
    {
        return "\n<p class=\"xh_$type\">" . $this->plural($key, $count, ...$args) . "</p>";
    }

    /** @param array<string,mixed> $_data */
    public function render(string $_template, array $_data): string
    {
        $_template = "{$this->pluginFolder}views/{$_template}.php";
        array_walk($_data, function (&$value) {
            if (is_string($value)) {
                $value = $this->esc($value);
            }
        });
        extract($_data);
        ob_start();
        include $_template;
        return (string) ob_get_clean();
    }

    /** @param scalar $value */
    public function esc($value): string
    {
        return XH_hsc((string) $value);
    }
}
