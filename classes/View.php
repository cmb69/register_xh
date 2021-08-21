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
     * @var string
     */
    private $template;

    /**
     * @var array<string,mixed>
     */
    private $data = array();

    /**
     * @param string $template
     */
    public function __construct($template)
    {
        $this->template = $template;
    }

    /**
     * @param array<string,mixed> $data
     * @return void
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @param string $name
     * @return string
     */
    public function __get($name)
    {
        return $this->data[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * @param string $name
     * @param mixed[] $args
     * @return string
     */
    public function __call($name, array $args)
    {
        return $this->escape($this->data[$name]);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        ob_start();
        $this->render();
        return ob_get_clean();
    }
    
    /**
     * @param string $key
     * @return string
     */
    protected function text($key)
    {
        global $plugin_tx;

        $args = func_get_args();
        array_shift($args);
        return $this->escape(vsprintf($plugin_tx['register'][$key], $args));
    }

    /**
     * @param string $key
     * @param int $count
     */
    protected function plural($key, $count): string
    {
        global $plugin_tx;

        if ($count == 0) {
            $key .= '_0';
        } else {
            $key .= XH_numberSuffix($count);
        }
        $args = func_get_args();
        array_shift($args);
        return $this->escape(vsprintf($plugin_tx['register'][$key], $args));
    }

    /**
     * @return void
     */
    public function render()
    {
        global $pth;

        echo "<!-- {$this->template} -->", PHP_EOL;
        include "{$pth['folder']['plugins']}register/views/{$this->template}.php";
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function escape($value)
    {
        if ($value instanceof HtmlString) {
            return $value;
        } else {
            return XH_hsc($value);
        }
    }
}
