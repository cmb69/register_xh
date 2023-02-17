<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use PHPUnit\Framework\TestCase;
use Register\Infra\CurrentUser;
use Register\Infra\Pages;
use Register\Value\User;
use XH\PageDataRouter as PageData;

class HandlePageProtectionTest extends TestCase
{
    /** @var HandlePageProtection */
    private $sut;

    /** @var CurrentUser&MockObject */
    private $currentUser;

    /** @var PageData&MockObject */
    private $pageData;

    /** @var Pages&MockObject */
    private $pages;

    public function setUp(): void
    {
        $this->currentUser = $this->createStub(CurrentUser::class);
        $this->pageData = $this->createStub(PageData::class);
        $this->pageData->method("find_all")->willReturn([
            ["register_access" => ""],
            ["register_access" => "guest"],
            ["register_access" => "admin"],
        ]);
        $this->pages = $this->createStub(Pages::class);
        $this->sut = new HandlePageProtection($this->currentUser, $this->pageData, $this->pages);
    }

    public function testIt(): void
    {
        $this->currentUser->method("get")->willReturn(new User("cmb", "", ["guest"], "", "", ""));
        $this->pages->expects($this->any())->method("setContentOf")->withConsecutive(
            [2, "#CMSimple hide# {{{register_access('admin')}}}"],
        );
        ($this->sut)();
    }
}
