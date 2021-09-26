<?php

/**
 * Copyright 2016-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

class View
{
    /**
     * @param string $key
     * @return string
     */
    public function text($key)
    {
        /**
         * @var array<string,array<string,string>> $plugin_tx
         */
        global $plugin_tx;

        $args = func_get_args();
        array_shift($args);
        return $this->esc(vsprintf($plugin_tx['register'][$key], $args));
    }

    /**
     * @param string $key
     * @param int $count
     */
    public function plural($key, $count): string
    {
        /**
         * @var array<string,array<string,string>> $plugin_tx
         */
        global $plugin_tx;

        if ($count == 0) {
            $key .= '_0';
        } else {
            $key .= XH_numberSuffix($count);
        }
        $args = func_get_args();
        array_shift($args);
        return $this->esc(vsprintf($plugin_tx['register'][$key], $args));
    }

    /**
     * @param array<string,mixed> $_data
     */
    public function render(string $_template, array $_data): string
    {
        /**
         * @var array{folder:array<string,string>,file:array<string,string>} $pth
         */
        global $pth;

        $_template = "{$pth['folder']['plugins']}register/views/{$_template}.php";
        unset($pth);
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
