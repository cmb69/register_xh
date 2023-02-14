<?php

/**
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use PHPUnit\Framework\TestCase;
use Register\Infra\UserRepository;
use XH\CSRFProtection as CsrfProtector;

class DicTest extends TestCase
{
    public function setUp(): void
    {
        global $pth, $h, $c, $_XH_csrfProtection, $cf, $tx, $plugin_cf, $plugin_tx, $sn, $sl, $su;

        $pth = ["folder" => ["content" => "", "corestyle" => "", "plugins" => ""]];
        $h = [];
        $c = [];
        $_XH_csrfProtection = $this->createStub(CsrfProtector::class);
        $cf = XH_includeVar("../../cmsimple/config.php", "cf");
        $tx = XH_includeVar("../../cmsimple/languages/en.php", "tx");
        $plugin_cf = XH_includeVar("./config/config.php", "plugin_cf");
        $plugin_tx = XH_includeVar("./languages/en.php", "plugin_tx");
        $sn = "/";
        $sl = "en";
        $su = "";
    }

    public function testMakesLoginController(): void
    {
        $this->assertInstanceOf(LoginController::class, Dic::makeLoginController());
    }

    public function testMakesSpecialPageController(): void
    {
        $this->assertInstanceOf(SpecialPageController::class, Dic::makeSpecialPageController());
    }

    public function testMakesUserAdminController(): void
    {
        $this->assertInstanceOf(UserAdminController::class, Dic::makeUserAdminController());
    }

    public function testMakesGroupAdminController(): void
    {
        $this->assertInstanceOf(GroupAdminController::class, Dic::makeGroupAdminController());
    }

    public function testMakesShowRegistrationForm(): void
    {
        $this->assertInstanceOf(ShowRegistrationForm::class, Dic::makeShowRegistrationForm());
    }

    public function testMakesRegisterUser(): void
    {
        $this->assertInstanceOf(RegisterUser::class, Dic::makeRegisterUser());
    }

    public function testMakesActivateUser(): void
    {
        $this->assertInstanceOf(ActivateUser::class, Dic::makeActivateUser());
    }

    public function testMakesForgotPasswordController(): void
    {
        $this->assertInstanceOf(ForgotPasswordController::class, Dic::makeForgotPasswordController());
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

    public function testMakesUserRepository(): void
    {
        $this->assertInstanceOf(UserRepository::class, Dic::makeUserRepository());
    }
}
