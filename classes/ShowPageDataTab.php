<?php

/**
 * Copyright (c) 2012-2021 Christoph M. Becker
 */

namespace Register;

use Register\Infra\Request;
use Register\Infra\Response;
use Register\Infra\View;

class ShowPageDataTab
{
    /** @var array<string,string> */
    private $text;

    /** @var View */
    private $view;

    /** @param array<string,string> $text */
    public function __construct(array $text, View $view)
    {
        $this->text = $text;
        $this->view = $view;
    }

    /** @param array<string,string> $pageData */
    public function __invoke(Request $request, array $pageData): Response
    {
        return (new Response)->body($this->view->render("page_data", [
            "action" => $request->url()->relative(),
            "iconFilename" => $request->coreStyleFolder() . "help_icon.png",
            "iconAlt" => $this->text["alt_help"],
            "accessGroups" => $pageData["register_access"],
        ]));
    }
}
