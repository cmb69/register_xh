<?php

/**
 * Copyright (c) 2012-2023 Christoph M. Becker
 */

namespace Register;

use Register\Infra\Request;
use Register\Infra\View;
use Register\Value\Response;

class ShowPageDataTab
{
    /** @var string */
    private $coreStyleFolder;

    /** @var View */
    private $view;

    public function __construct(string $coreStyleFolder, View $view)
    {
        $this->coreStyleFolder = $coreStyleFolder;
        $this->view = $view;
    }

    /** @param array<string,string> $pageData */
    public function __invoke(Request $request, array $pageData): Response
    {
        return Response::create($this->view->render("page_data", [
            "action" => $request->url()->relative(),
            "iconFilename" => $this->coreStyleFolder . "help_icon.png",
            "accessGroups" => $pageData["register_access"],
        ]));
    }
}
