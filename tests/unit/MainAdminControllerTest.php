<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use XH\CSRFProtection as CsrfProtector;
use XH\Pages;

class MainAdminControllerTest extends TestCase
{
    public function testEditGroupActionRendersGroups()
    {
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $csrfProtector = $this->createStub(CsrfProtector::class);
        $dbService = $this->createStub(DbService::class);
        $dbService->method('hasGroupsFile')->willReturn(true);
        $dbService->method('readGroups')->willReturn([new UserGroup("users", "")]);
        $sut = new MainAdminController("./", $conf, $lang, $csrfProtector, $dbService, "/", $this->makePages());
        $response = $sut->editGroupsAction();
        Approvals::verifyHtml($response);
    }

    public function testEditGroupActionFailsIfNoGroupsFile()
    {
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $csrfProtector = $this->createStub(CsrfProtector::class);
        $dbService = $this->createStub(DbService::class);
        $dbService->method('hasGroupsFile')->willReturn(false);
        $pages = $this->createStub(Pages::class);
        $sut = new MainAdminController("./", $conf, $lang, $csrfProtector, $dbService, "/", $pages);
        $response = $sut->editGroupsAction();
        Approvals::verifyHtml($response);
    }

    private function makePages(): Pages
    {
        $pages = $this->createStub(Pages::class);
        $pages->method('getCount')->willReturn(2);
        $pages->method('url')->willReturnMap([[0, "foo"], [1, "bar"]]);
        $pages->method('level')->willReturnMap([[0, 1], [1, 2]]);
        $pages->method('heading')->willReturnMap([[0, "Foo"], [1, "Bar"]]);
        return $pages;
    }
}
