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
use Register\Infra\Random;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Value\User;

class HandlePageAccessTest extends TestCase
{
    /** @var HandlePageAccess */
    private $sut;

    private $userRepository;

    /** @var Request */
    private $request;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $text = XH_includeVar("./languages/en.php", "plugin_tx")["register"];
        $dbService = new FakeDbService("vfs://root/register/", "guest", $this->createMock(Random::class));
        $dbService->writeUsers($this->users());
        $this->userRepository = new UserRepository($dbService);
        $this->sut = new HandlePageAccess($text, $this->userRepository);
        $this->request = $this->createStub(Request::class);
        $this->request->method("url")->willReturn(new Url("/", "Foo"));
    }

    public function testVisitorCannotAccessProtectedPage(): void
    {
        $this->request->method("username")->willReturn("");
        $response = ($this->sut)($this->request, "admin");
        $this->assertEquals("http://example.com/?Access-Restricted", $response->location());
    }

    public function testUserCanAccessProtectedPage(): void
    {
        $this->request->method("username")->willReturn("jane");
        $response = ($this->sut)($this->request, "admin");
        $this->assertNull($response->location());
    }

    public function testUserCannotAccessProtectedPage(): void
    {
        $this->request->method("username")->willReturn("john");
        $response = ($this->sut)($this->request, "admin");
        $this->assertEquals("http://example.com/?Access-Restricted", $response->location());
    }

    public function testDoesNotRedirectWhileSearching(): void
    {
        $this->request->method("function")->willReturn("search");
        $response = ($this->sut)($this->request, "admin");
        $this->assertNull($response->location());
    }

    private function users(): array
    {
        return [
            new User("jane", "test", ["admin"], "Jane Doe", "jane@example.com", "activated", "nDZ8c8abkHTjpfI77TPi"),
            new User("john", "test", ["guest"], "John Doe", "john@example.com", "locked", "n+VaBbbvk934dmPF/fRw"),
        ];
    }
}
