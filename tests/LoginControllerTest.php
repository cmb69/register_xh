<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Register\Infra\FakeDbService;
use Register\Infra\FakePassword;
use Register\Value\User;
use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\Random;
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

    private $loginManager;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $this->conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $this->text = $plugin_tx['register'];
        $dbService = new FakeDbService("vfs://root/register/", "guest", $this->createMock(Random::class));
        $dbService->writeUsers([$this->jane()]);
        $this->userRepository = new UserRepository($dbService);
        $this->userGroupRepository = new UserGroupRepository($dbService);
        $this->logger = $this->createStub(Logger::class);
        $this->loginManager = $this->createStub(LoginManager::class);
        $this->request = $this->createStub(Request::class);
        $this->request->method("url")->willReturn(new Url("/", "irrelevant page"));
    }

    public function testLoginActionSuccessRedirects(): void
    {
        $_POST = ["username" => "jane", "password" => "test"];
        $password = new FakePassword;
        $sut = new LoginController(
            $this->conf,
            $this->text,
            $this->userRepository,
            $this->userGroupRepository,
            $this->logger,
            $this->loginManager,
            $password
        );
        $this->request->method("function")->willReturn("registerlogin");
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
            $this->loginManager,
            new FakePassword
        );
        $this->request->method("function")->willReturn("registerlogin");
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
            $this->loginManager,
            new FakePassword
        );
        $this->request->method("function")->willReturn("registerlogout");
        $this->request->method("username")->willReturn("jane");
        $response = $sut($this->request);
        $this->assertEquals("http://example.com/?Logged-out", $response->location());
    }

    private function jane(): User
    {
        return new User(
            "jane",
            "\$2y\$04\$vcjV1rBQmBIKJsVNhRvWZukMmECVkKIHKAdVI9FlcXmVbSb/km3c6",
            ["admin"],
            "Jane Doe",
            "jane@example.com",
            "activated",
            "secret"
        );
    }
}
