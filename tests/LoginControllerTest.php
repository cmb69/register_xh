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
use Register\Infra\FakeRequest;
use Register\Value\User;
use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\Random;
use Register\Infra\UserRepository;

class LoginControllerTest extends TestCase
{
    private $conf;
    private $userRepository;
    private $logger;
    private $loginManager;

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
        $request = new FakeRequest(["cookies" => ["register_remember" => "jane"]]);
        $response = $this->sut()($request);
        $this->assertEquals([["register_remember", "", 0]], $response->cookies());
    }

    public function testAutoLoginFailsForNonExistentUser(): void
    {
        $request = new FakeRequest(["cookies" => ["register_remember" => "colt.6M5brgkTOP4AaQ9ZGLss7MZYyG4"]]);
        $response = $this->sut()($request);
        $this->assertEquals([["register_remember", "", 0]], $response->cookies());
    }

    public function testAutoLoginFailsForDeactivatedUser(): void
    {
        $request = new FakeRequest(["cookies" => ["register_remember" => "john.6M5brgkTOP4AaQ9ZGLss7MZYyG4"]]);
        $response = $this->sut()($request);
        $this->assertEquals([["register_remember", "", 0]], $response->cookies());
    }

    public function testAutoLoginFailsForManipulatedCookie(): void
    {
        $request = new FakeRequest(["cookies" => ["register_remember" => "jane.6M5brgkTOP4AaQ9ZGLss7MZYyG4"]]);
        $response = $this->sut()($request);
        $this->assertEquals([["register_remember", "", 0]], $response->cookies());
    }

    public function testAutoLoginSucceeds(): void
    {
        $this->loginManager->expects($this->once())->method("login")->with($this->jane());
        $this->logger->expects($this->once())->method("logInfo")->with("login", "jane automatically logged in");
        $request = new FakeRequest(["cookies" => ["register_remember" => "jane.i5ixPyjRJ6iPuDjTEwBwpxSg6H0"]]);
        $this->sut()($request);
    }

    public function testLogoutSucceeds(): void
    {
        $request = new FakeRequest(["query" => "&function=registerlogout", "username" => "jane"]);
        $this->sut()($request);
    }

    public function testSuccessfulLogoutDeletesCookie(): void
    {
        $request = new FakeRequest([
            "query" => "&function=registerlogout",
            "username" => "jane",
            "cookies" => ["register_remember" => "jane.i5ixPyjRJ6iPuDjTEwBwpxSg6H0"],
        ]);
        $response = $this->sut()($request);
        $this->assertEquals([["register_remember", "", 0]], $response->cookies());
    }

    public function testForcesLogoutForUnknownUser(): void
    {
        $this->loginManager->expects($this->once())->method("logout");
        $request = new FakeRequest(["username" => "colt"]);
        $this->sut()($request);
    }

    public function testDoesNothing(): void
    {
        $request = new FakeRequest(["username" => "jane"]);
        $this->sut()($request);
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
