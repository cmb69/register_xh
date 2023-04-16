<?php

/**
 * Copyright (c) 2012-2023 Christoph M. Becker
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use Register\Infra\FakeRequest;
use Register\Infra\View;

class ShowPageDataTabTest extends TestCase
{
    public function testRendersPageDataTab(): void
    {
        $text = XH_includeVar("./languages/en.php", "plugin_tx")["register"];
        $sut = new ShowPageDataTab("../../assets/css/", new View("./views/", $text));
        $request = new FakeRequest(["query" => "SomePage"]);
        $response = $sut($request, ["register_access" => "cmb"]);
        Approvals::verifyHtml($response->output());
    }
}
