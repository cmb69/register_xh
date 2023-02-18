<?php

/**
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use PHPUnit\Framework\TestCase;
use Register\Infra\CurrentUser;
use Register\Infra\UserRepository;
use XH\CSRFProtection as CsrfProtector;
use XH\PageDataRouter as PageData;

class DicTest extends TestCase
{
    public function setUp(): void
    {
        global $pth, $_XH_csrfProtection, $plugin_cf, $plugin_tx, $pd_router;

        $pth = ["folder" => ["base" => "", "content" => "", "corestyle" => "", "plugins" => ""]];
        $_XH_csrfProtection = $this->createStub(CsrfProtector::class);
        $plugin_cf = XH_includeVar("./config/config.php", "plugin_cf");
        $plugin_tx = XH_includeVar("./languages/en.php", "plugin_tx");
        $pd_router = $this->createStub(PageData::class);
    }

    public function testMakesLoginController(): void
    {
        $this->assertInstanceOf(LoginController::class, Dic::makeLoginController());
    }

    public function testMakesHandleSpecialPages(): void
    {
        $this->assertInstanceOf(HandleSpecialPages::class, Dic::makeHandleSpecialPages());
    }

    public function testMakesUserAdminController(): void
    {
        $this->assertInstanceOf(UserAdminController::class, Dic::makeUserAdminController());
    }

    public function testMakesGroupAdminController(): void
    {
        $this->assertInstanceOf(GroupAdminController::class, Dic::makeGroupAdminController());
    }

    public function testMakesHandleUserRegistration(): void
    {
        $this->assertInstanceOf(HandleUserRegistration::class, Dic::makeHandleUserRegistration());
    }

    public function testMakesHandlePageAccess(): void
    {
        $this->assertInstanceOf(HandlePageAccess::class, Dic::makeHandlePageAccess());
    }

    public function testMakesHandlePageProtection(): void
    {
        $this->assertInstanceOf(HandlePageProtection::class, Dic::makeHandlePageProtection());
    }

    public function testMakesHandlePasswordForgotten(): void
    {
        $this->assertInstanceOf(HandlePasswordForgotten::class, Dic::makeHandlePasswordForgotten());
    }

    public function testMakesShowLoginForm(): void
    {
        $this->assertInstanceOf(ShowLoginForm::class, Dic::makeShowLoginForm());
    }

    public function testMakesShowPageDataTab(): void
    {
        $this->assertInstanceOf(ShowPageDataTab::class, Dic::makeShowPageDataTab());
    }

    public function testMakesShowPluginInf(): void
    {
        $this->assertInstanceOf(ShowPluginInfo::class, Dic::makeShowPluginInfo());
    }
}
