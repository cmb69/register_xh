<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Value\User;

class ShowLoginFormTest extends TestCase
{
    public function testLoginForm(): void
    {
        $sut = new ShowLoginForm($this->conf(), $this->text(), $this->userRepo(), $this->view());
        $response = $sut($this->request());
        Approvals::verifyHtml($response->output());
    }

    public function testLoggedInOnlyForm(): void
    {
        $sut = new ShowLoginForm([], $this->text(), $this->userRepo(), $this->view());

        $response = $sut($this->request(), true);

        $this->assertEquals("", $response->output());

    }

    public function testLoggedInForm(): void
    {
        $sut = new ShowLoginForm([], $this->text(), $this->userRepo(), $this->view());

        $request = $this->request();
        $request->method("username")->willReturn("jane");
        $response = $sut($request);

        Approvals::verifyHtml($response->output());
    }

    private function request(): Request
    {
        $request = $this->createMock(Request::class);
        $request->method("url")->willReturn(new Url("/", "Foo"));
        return $request;
    }

    private function userRepo(): UserRepository
    {
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method("findByUsername")->willReturn(
            new User("jane", "", [], "Jane Doe", "jane@example.com", "activated", "secret")
        );
        return $userRepo;
    }

    private function view(): View
    {
        return new View("./views/", $this->text());
    }

    private function conf(): array
    {
        return XH_includeVar("./config/config.php", "plugin_cf")["register"];
    }

    private function text(): array
    {
        return XH_includeVar("./languages/en.php", "plugin_tx")["register"];
    }
}
