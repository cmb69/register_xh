<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Register\Infra\DbService;
use Register\Infra\FakeRequest;
use Register\Infra\Random;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Value\User;

class UserInfoTest extends TestCase
{
    private $conf;
    private $userRepository;
    private $view;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $this->conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $dbService = new DbService("vfs://root/register/", "guest", $this->createMock(Random::class));
        $this->userRepository = new UserRepository($dbService);
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["register"]);
    }

    private function sut(): UserInfo
    {
        return new UserInfo(
            $this->conf,
            $this->userRepository,
            $this->view
        );
    }

    public function testShowsNothingToVisitors(): void
    {
        $request = new FakeRequest();
        $response = $this->sut()($request, "Register");
        $this->assertEquals("", $response->output());
    }

    public function testReportsNonExistentUser(): void
    {
        $request = new FakeRequest(["username" => "colt"]);
        $response = $this->sut()($request, "Register");
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testRendersUserInfo(): void
    {
        $this->userRepository->save(new User("cmb", "12345", ["guest"], "Christoph Becker", "cmb@example.com", "1", "1"));
        $request = new FakeRequest(["username" => "cmb"]);
        $response = $this->sut()($request, "Register");
        Approvals::verifyHtml($response->output());
    }
}
