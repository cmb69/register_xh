<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Plib\DocumentStore;
use Plib\View;
use Register\Infra\SystemChecker;

class ShowPluginInfoTest extends TestCase
{
    public function testRendersPluginInfo()
    {
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $store = $this->createStub(DocumentStore::class);
        $store->method("folder")->willReturn("./content/register/");
        $systemChecker = $this->createStub(SystemChecker::class);
        $systemChecker->method('checkVersion')->willReturn(true);
        $systemChecker->method('checkExtension')->willReturn(true);
        $systemChecker->method('checkWritability')->willReturn(true);
        $systemChecker->method('checkAccessProtection')->willReturn(true);
        $subject = new ShowPluginInfo("./plugins/register/", $store, $systemChecker, new View("./views/", $text));
        $response = $subject();
        Approvals::verifyHtml($response->output());
    }
}
