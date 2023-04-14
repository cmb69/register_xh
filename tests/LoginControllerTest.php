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
use Register\Value\User;
use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\Random;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;

class LoginControllerTest extends TestCase
{
    private $conf;
    private $userRepository;
    private $logger;
    private $loginManager;

    private $request;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $this->conf = $plugin_cf['register'];
        $dbService = new FakeDbService("vfs://root/register/", "guest", $this->createMock(Random::class));
        $dbService->writeUsers([$this->jane(), $this->john()]);
        $this->userRepository = new UserRepository($dbService);
        $this->logger = $this->createStub(Logger::class);
        $this->loginManager = $this->createStub(LoginManager::class);
        $this->request = $this->createStub(Request::class);
        $this->request->method("url")->willReturn(new Url("/", "irrelevant page"));
    }

    private function sut()
    {
        return new LoginController(
            $this->conf,
            $this->userRepository,
            $this->logger,
            $this->loginManager
        );
    }

    public function testAutoLoginFailsOnBorkedCookie(): void
    {
        $this->request->method("cookie")->willReturn("jane");
        $response = $this->sut()($this->request);
        $this->assertEquals([["register_remember", "", 0]], $response->cookies());
    }

    public function testAutoLoginFailsForNonExistentUser(): void
    {
        $this->request->method("cookie")->willReturn("colt.6M5brgkTOP4AaQ9ZGLss7MZYyG4");
        $response = $this->sut()($this->request);
        $this->assertEquals([["register_remember", "", 0]], $response->cookies());
    }

    public function testAutoLoginFailsForDeactivatedUser(): void
    {
        $this->request->method("cookie")->willReturn("john.6M5brgkTOP4AaQ9ZGLss7MZYyG4");
        $response = $this->sut()($this->request);
        $this->assertEquals([["register_remember", "", 0]], $response->cookies());
    }

    public function testAutoLoginFailsForManipulatedCookie(): void
    {
        $this->request->method("cookie")->willReturn("jane.6M5brgkTOP4AaQ9ZGLss7MZYyG4");
        $response = $this->sut()($this->request);
        $this->assertEquals([["register_remember", "", 0]], $response->cookies());
    }

    public function testAutoLoginSucceeds(): void
    {
        $this->loginManager->expects($this->once())->method("login")->with($this->jane());
        $this->logger->expects($this->once())->method("logInfo")->with("login", "jane automatically logged in");
        $this->request->method("cookie")->willReturn("jane.i5ixPyjRJ6iPuDjTEwBwpxSg6H0");
        $this->sut()($this->request);
    }

    public function testLogoutSucceeds(): void
    {
        $this->request->method("username")->willReturn("jane");
        $this->request->method("function")->willReturn("registerlogout");
        $this->sut()($this->request);
    }

    public function testSuccessfulLogoutDeletesCookie(): void
    {
        $this->request->method("username")->willReturn("jane");
        $this->request->method("function")->willReturn("registerlogout");
        $this->request->method("cookie")->willReturn("jane.i5ixPyjRJ6iPuDjTEwBwpxSg6H0");
        $response = $this->sut()($this->request);
        $this->assertEquals([["register_remember", "", 0]], $response->cookies());
    }

    public function testForcesLogoutForUnknownUser(): void
    {
        $this->loginManager->expects($this->once())->method("logout");
        $this->request->method("username")->willReturn("colt");
        $this->sut()($this->request);
    }

    public function testDoesNothing(): void
    {
        $this->request->method("username")->willReturn("jane");
        $this->sut()($this->request);
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

    private function john(): User
    {
        return new User(
            "john",
            "\$2y\$04\$vcjV1rBQmBIKJsVNhRvWZukMmECVkKIHKAdVI9FlcXmVbSb/km3c6",
            ["guest"],
            "John Doe",
            "john@example.com",
            "deactivated",
            "secret"
        );
    }
}
