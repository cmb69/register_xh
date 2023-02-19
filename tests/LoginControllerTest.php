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
use Register\Infra\Password;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserGroupRepository;
use Register\Infra\UserRepository;

class LoginControllerTest extends TestCase
{
    /** @var array<string,string> */
    private $conf;

    /** @var array<string,string> */
    private $text;

    /** @var UserRepository&MockObject */
    private $userRepository;

    /** @var UserGroupRepository&MockObject */
    private $userGroupRepository;

    /** @var Logger&MockObject */
    private $logger;

    /** @var Request&MockObject */
    private $request;

    /** @var CurrentUser&MockObject */
    private $currentUser;

    public function setUp(): void
    {
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $this->conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $this->text = $plugin_tx['register'];
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->userGroupRepository = $this->createStub(UserGroupRepository::class);
        $this->logger = $this->createStub(Logger::class);
        $this->currentUser = $this->createStub(CurrentUser::class);
        $this->request = $this->createStub(Request::class);
        $this->request->expects($this->any())->method("url")->willReturn(new Url("/", "irrelevant page"));
    }

    public function testLoginActionSuccessRedirects(): void
    {
        $this->userRepository->method('findByUsername')->willReturn($this->jane());
        $password = $this->createStub(Password::class);
        $password->method("verify")->willReturn(true);
        $sut = new LoginController(
            $this->conf,
            $this->text,
            $this->userRepository,
            $this->userGroupRepository,
            $this->logger,
            $this->currentUser,
            $password
        );
        $this->request->expects($this->any())->method("function")->willReturn("registerlogin");
        $response = $sut($this->request);
        $this->assertEquals("http://example.com/?Logged-in", $response->location());
    }

    public function testLoginActionFailureRedirects(): void
    {
        $sut = new LoginController(
            $this->conf,
            $this->text,
            $this->userRepository,
            $this->userGroupRepository,
            $this->logger,
            $this->currentUser,
            $this->createStub(Password::class)
        );
        $this->request->expects($this->any())->method("function")->willReturn("registerlogin");
        $response = $sut($this->request);
        $this->assertEquals("http://example.com/?Login-Error", $response->location());
    }

    public function testLogoutActionRedirects(): void
    {
        $sut = new LoginController(
            $this->conf,
            $this->text,
            $this->userRepository,
            $this->userGroupRepository,
            $this->logger,
            $this->currentUser,
            $this->createStub(Password::class)
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
            "activated",
            "secret"
        );
    }
}
