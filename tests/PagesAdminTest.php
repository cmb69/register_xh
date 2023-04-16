<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use Register\Infra\FakeRequest;
use Register\Infra\Pages;
use Register\Infra\View;

class PagesAdminTest extends TestCase
{
    private $pages;
    private $view;

    public function setUp(): void
    {
        $this->pages = $this->createMock(Pages::class);
        $this->view = new View("./views/", XH_includeVar("./languages/en.php", "plugin_tx")["register"]);
    }

    public function sut()
    {
        return new PagesAdmin($this->pages, $this->view);
    }

    public function testRendersPageOverview(): void
    {
        $this->pages->method("level")->willReturnMap([
            [0, 1],
            [1, 2],
            [2, 1],
        ]);
        $this->pages->method("heading")->willReturnMap([
            [0, "Start"],
            [1, "Sub"],
            [2, "Second"],
        ]);
        $this->pages->method("url")->willReturnMap([
            [0, "Start"],
            [1, "Start/Sub"],
            [2, "Second"],
        ]);
        $this->pages->method("data")->willReturn([
            ["register_access" => ""],
            ["register_access" => "guest"],
            ["register_access" => "admin"],
        ]);
        $response = $this->sut()(new FakeRequest);
        Approvals::verifyHtml($response->output());
    }
}
