<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class Logger
{
    /** @return void */
    public function logInfo(string $category, string $message)
    {
        $this->logMessage("info", "register", $category, $message);
    }

    /**
     * @return void
     * @codeCoverageIgnore
     */
    protected function logMessage(string $type, string $module, string $category, string $message)
    {
        XH_logMessage($type, $module, $category, $message);
    }
}
