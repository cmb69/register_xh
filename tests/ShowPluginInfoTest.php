<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use Register\Infra\DbService;
use Register\Infra\Request;
use Register\Infra\SystemChecker;
use Register\Infra\View;

class ShowPluginInfoTest extends TestCase
{
    public function testRendersPluginInfo()
    {
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $dbService = $this->createStub(DbService::class);
        $systemChecker = $this->createStub(SystemChecker::class);
        $systemChecker->method('checkVersion')->willReturn(true);
        $systemChecker->method('checkExtension')->willReturn(true);
        $systemChecker->method('checkWritability')->willReturn(true);
        $subject = new ShowPluginInfo($text, $dbService, $systemChecker, new View("./views/", $text));
        $request = $this->createStub(Request::class);
        $request->method("pluginsFolder")->willReturn("");
        $response = $subject($request);

        Approvals::verifyHtml($response->output());
    }
}
