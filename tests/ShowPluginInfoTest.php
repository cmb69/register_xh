<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use Register\Infra\DbService;
use Register\Infra\SystemChecker;
use Register\Infra\View;

class ShowPluginInfoTest extends TestCase
{
    public function testRendersPluginInfo()
    {
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $dbService = $this->createStub(DbService::class);
        $systemChecker = $this->createStub(SystemChecker::class);
        $systemChecker->method('checkVersion')->willReturn(true);
        $systemChecker->method('checkExtension')->willReturn(true);
        $systemChecker->method('checkWritability')->willReturn(true);
        $subject = new ShowPluginInfo("", $lang, $dbService, $systemChecker, new View("./", $lang));

        $response = $subject();

        Approvals::verifyHtml($response);
    }
}
