<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Register\Infra\ActivityRepository;
use Register\Infra\FakeDbService;
use Register\Infra\FakeLogger;
use Register\Infra\FakeRequest;
use Register\Value\User;
use Register\Infra\LoginManager;
use Register\Infra\Pages;
use Register\Infra\Random;
use Register\Infra\UserRepository;
use Register\Infra\View;

class MainTest extends TestCase
{
    private $conf;
    private $userRepository;
    private $activityRepository;
    private $pages;
    private $logger;
    private $loginManager;
    private $view;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $this->conf = $plugin_cf['register'];
        $dbService = new FakeDbService("vfs://root/register/", "guest", $this->createMock(Random::class));
        $dbService->writeUsers([$this->jane(), $this->john()]);
        $this->userRepository = new UserRepository($dbService);
        $this->activityRepository = new ActivityRepository($dbService);
        $this->pages = $this->createMock(Pages::class);
        $this->pages->method("data")->willReturn([
            ["register_access" => ""],
            ["register_access" => "guest"],
            ["register_access" => "admin"],
        ]);
        $this->logger = new FakeLogger;
        $this->loginManager = $this->createStub(LoginManager::class);
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["register"]);
    }

    private function sut()
    {
        return new Main(
            $this->conf,
            $this->userRepository,
            $this->activityRepository,
            $this->pages,
            $this->logger,
            $this->loginManager,
            $this->view
        );
    }

    public function testProtectsPages(): void
    {
        $this->pages->expects($this->once())->method("setContentOf")->with(
            2, "{{{register_forbidden()}}}#CMSimple hide#"
        );
        $request = new FakeRequest(["username" => "john"]);
        $this->sut()($request);
    }

    public function testDoesNotProtectPagesInEditMode(): void
    {
        $this->pages->expects($this->never())->method("setContentOf");
        $request = new FakeRequest(["username" => "john", "editMode" => true]);
        $this->sut()($request);
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
        $request = new FakeRequest(["cookies" => ["register_remember" => "jane.i5ixPyjRJ6iPuDjTEwBwpxSg6H0"]]);
        $this->sut()($request);
        $this->assertEquals(
            ["info", "register", "login", "User “jane” automatically logged in"],
            $this->logger->lastEntry()
        );
    }

    public function testLogoutSucceeds(): void
    {
        $request = new FakeRequest(["query" => "&function=registerlogout", "username" => "jane"]);
        $response = $this->sut()($request);
        $this->assertEquals("http://example.com/?&function=registerlogout", $response->location());
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
        $this->assertEquals("http://example.com/?&function=registerlogout", $response->location());
        $this->assertEquals(["info", "register", "logout", "User “jane” logged out"], $this->logger->lastEntry());
    }

    public function testForcesLogoutForUnknownUser(): void
    {
        $this->loginManager->expects($this->once())->method("logout");
        $request = new FakeRequest(["username" => "colt"]);
        $response = $this->sut()($request);
        $this->assertEquals("http://example.com/", $response->location());
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
