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
use Register\Infra\Pages;
use Register\Infra\Random;
use Register\Infra\Request;
use Register\Infra\UserRepository;
use Register\Value\User;

class HandlePageProtectionTest extends TestCase
{
    /** @var HandlePageProtection */
    private $sut;

    private $userRepository;

    /** @var Pages&MockObject */
    private $pages;

    /** @var Request */
    private $request;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $dbService = new FakeDbService("vfs://root/register/", "guest", $this->createMock(Random::class));
        $dbService->writeUsers($this->users());
        $this->userRepository = new UserRepository($dbService);
        $this->pages = $this->createStub(Pages::class);
        $this->pages->method("data")->willReturn([
            ["register_access" => ""],
            ["register_access" => "guest"],
            ["register_access" => "admin"],
        ]);
        $this->sut = new HandlePageProtection($conf, $this->userRepository, $this->pages);
        $this->request = $this->createStub(Request::class);
        $this->request->method("username")->willReturn("john");
    }

    public function testProtectsPages(): void
    {
        $this->pages->expects($this->once())->method("setContentOf")->with(
            2, "#CMSimple hide# {{{register_access('admin')}}}"
        );
        ($this->sut)($this->request);
    }

    public function testDoesNotProtectPagesInEditMode(): void
    {
        $this->pages->expects($this->never())->method("setContentOf");
        $this->request->method("editMode")->willReturn(true);
        ($this->sut)($this->request);

    }

    private function users(): array
    {
        return [
            new User("jane", "test", ["admin"], "Jane Doe", "jane@example.com", "activated", "nDZ8c8abkHTjpfI77TPi"),
            new User("john", "test", ["guest"], "John Doe", "john@example.com", "locked", "n+VaBbbvk934dmPF/fRw"),
        ];
    }
}
