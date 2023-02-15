<?php

/**
 * Copyright (c) 2012-2021 Christoph M. Becker
 */

namespace Register;

use PHPUnit\Framework\TestCase;
use ApprovalTests\Approvals;

use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\View;

class ShowPageDataTabTest extends TestCase
{
    public function testRendersPageDataTab(): void
    {
        $sut = new ShowPageDataTab(
            "../../assets/css/",
            "Help",
            new View("./", XH_includeVar("./languages/en.php", "plugin_tx")["register"])
        );
        $request = $this->createStub(Request::class);
        $request->method("url")->willReturn(new Url("./", "SomePage"));
        $response = $sut(["register_access" => "cmb"], $request);
        Approvals::verifyHtml($response);
    }
}
