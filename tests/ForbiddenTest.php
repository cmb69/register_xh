<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use Register\Infra\View;

class ForbiddenTest extends TestCase
{
    public function testDeniesPageAccess(): void
    {
        $view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["register"]);
        $sut = new Forbidden($view);
        $response = $sut();
        $this->assertTrue($response->isForbidden());
        $this->assertEquals("Access Restricted", $response->title());
        Approvals::verifyHtml($response->output());
    }
}
