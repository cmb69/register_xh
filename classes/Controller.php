<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

abstract class Controller
{
    /**
     * @var array<string,string>
     */
    protected $config;

    /**
     * @var array<string,string>
     */
    protected $lang;

    public function __construct()
    {
        /**
         * @var array<string,array<string,string>> $plugin_cf
         * @var array<string,array<string,string>> $plugin_tx
         */
        global $plugin_cf, $plugin_tx;

        $this->config = $plugin_cf['register'];
        $this->lang = $plugin_tx['register'];
    }
}
