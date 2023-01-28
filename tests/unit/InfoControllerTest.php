<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use PHPUnit\Framework\TestCase;

class InfoControllerTest extends TestCase
{
    public function testExecute()
    {
        $systemChecker = $this->createStub(SystemChecker::class);
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $systemCheckService = new SystemCheckService("", $lang, "", $systemChecker);
        $view = $this->createMock(View::class);
        $subject = new InfoController("2.0", $systemCheckService, $view);
        $view->expects($this->once())->method("render")->with("info");
        $subject->execute();
    }
}
