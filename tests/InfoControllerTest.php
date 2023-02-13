<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;

class InfoControllerTest extends TestCase
{
    public function testRendersPluginInfo()
    {
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $systemChecker = $this->createStub(SystemChecker::class);
        $systemChecker->method('checkVersion')->willReturn(true);
        $systemChecker->method('checkExtension')->willReturn(true);
        $systemChecker->method('checkWritability')->willReturn(true);
        $subject = new InfoController("", $lang, "", $systemChecker, new View("./", $lang));

        $response = $subject->execute();

        Approvals::verifyHtml($response);
    }
}
