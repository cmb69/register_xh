<?php

/**
 * Copyright 2016-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class View
{
    /** @var string */
    private $templateFolder;

    /** @var array<string,string> */
    private $text;

    /** @param array<string,string> $text */
    public function __construct(string $templateFolder, array $text)
    {
        $this->templateFolder = $templateFolder;
        $this->text = $text;
    }

    /** @param scalar $args */
    public function text(string $key, ...$args): string
    {
        return sprintf($this->esc($this->text[$key]), ...$args);
    }

    /** @param scalar $args */
    public function plural(string $key, int $count, ...$args): string
    {
        return sprintf($this->esc($this->text[$this->pluralKey($key, $count)]), $count, ...$args);
    }

    /** @param scalar $args */
    public function message(string $type, string $key, ...$args): string
    {
        return XH_message($type, $this->text[$key], ...$args) . "\n";
    }

    /** @param scalar $args */
    public function messagep(string $type, int $count, string $key, ...$args): string
    {
        return XH_message($type, $this->text[$this->pluralKey($key, $count)], $count, ...$args) . "\n";
    }

    private function pluralKey(string $key, int $count): string
    {
        return $count === 0 ? "{$key}_0" : $key . XH_numberSuffix($count);
    }

    /** @param array<string,mixed> $_data */
    public function render(string $_template, array $_data): string
    {
        $_template = $this->templateFolder . $_template . ".php";
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
