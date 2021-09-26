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
        $systemCheckService = $this->createStub(SystemCheckService::class);
        $view = $this->createMock(View::class);
        $subject = new InfoController("2.0", $systemCheckService, $view);
        $view->expects($this->once())->method("render")->with("info");
        $subject->execute();
    }
}
