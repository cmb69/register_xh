<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class Logger
{
    /**
     * @return void
     */
    public function logInfo(string $category, string $description)
    {
        XH_logMessage("info", "register", $category, $description);
    }

    /**
     * @return void
     */
    public function logWarning(string $category, string $description)
    {
        XH_logMessage("warning", "register", $category, $description);
    }

    /**
     * @return void
     */
    public function logError(string $category, string $description)
    {
        XH_logMessage("error", "register", $category, $description);
    }
}
