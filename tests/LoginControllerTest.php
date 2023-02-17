<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use PHPUnit\Framework\TestCase;
use Register\Infra\CurrentUser;
use Register\Value\User;
use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\Request;
use Register\Infra\Session;
use Register\Infra\Url;
use Register\Infra\UserGroupRepository;
use Register\Infra\UserRepository;

class LoginControllerTest extends TestCase
{
    /** @var array<string,string> */
    private $conf;

    /** @var array<string,string> */
    private $lang;

    /** @var UserRepository&MockObject */
    private $userRepository;

    /** @var UserGroupRepository&MockObject */
    private $userGroupRepository;

    /** @var LoginManager&MockObject */
    private $loginManager;

    /** @var Logger&MockObject */
    private $logger;

    /** @var Session&MockObject */
    private $session;

    /** @var Request&MockObject */
    private $request;

    /** @var CurrentUser&MockObject */
    private $currentUser;

    public function setUp(): void
    {
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $this->conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $this->lang = $plugin_tx['register'];
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->userGroupRepository = $this->createStub(UserGroupRepository::class);
        $this->loginManager = $this->createStub(LoginManager::class);
        $this->logger = $this->createStub(Logger::class);
        $this->session = $this->createStub(Session::class);
        $this->currentUser = $this->createStub(CurrentUser::class);
        $this->request = $this->createStub(Request::class);
        $this->request->expects($this->any())->method("url")->willReturn(new Url("/", "irrelevant page"));
    }

    public function testLoginActionSuccessRedirects(): void
    {
        $this->userRepository->method('findByUsername')->willReturn($this->jane());
        $this->loginManager->method('isUserAuthenticated')->willReturn(true);
        $sut = new LoginController(
            $this->conf,
            $this->lang,
            $this->userRepository,
            $this->userGroupRepository,
            $this->loginManager,
            $this->logger,
            $this->session,
            $this->currentUser
        );
        $this->request->expects($this->any())->method("function")->willReturn("registerlogin");
        $response = $sut($this->request);
        $this->assertEquals("http://example.com/?Logged-in", $response->location());
    }

    public function testLoginActionFailureRedirects(): void
    {
        $sut = new LoginController(
            $this->conf,
            $this->lang,
            $this->userRepository,
            $this->userGroupRepository,
            $this->loginManager,
            $this->logger,
            $this->session,
            $this->currentUser
        );
        $this->request->expects($this->any())->method("function")->willReturn("registerlogin");
        $response = $sut($this->request);
        $this->assertEquals("http://example.com/?Login-Error", $response->location());
    }

    public function testLogoutActionRedirects(): void
    {
        $sut = new LoginController(
            $this->conf,
            $this->lang,
            $this->userRepository,
            $this->userGroupRepository,
            $this->loginManager,
            $this->logger,
            $this->session,
            $this->currentUser
        );
        $this->currentUser->method("get")->willReturn($this->jane());
        $this->request->expects($this->any())->method("function")->willReturn("registerlogout");
        $response = $sut($this->request);
        $this->assertEquals("http://example.com/?Logged-out", $response->location());
    }

    private function jane(): User
    {
        return new User(
            "jane",
            '$2y$10$gOae/VL5wrESo5Uf6ZcWhuNlAEycCGW5Ov5opny5PWxa.gbl4SHQW',
            ["admin"],
            "Jane Doe",
            "jane@example.com",
            "activated"
        );
    }
}
