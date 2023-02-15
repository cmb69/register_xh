<?php

/**
 * Copyright (c) 2012-2021 Christoph M. Becker
 */

namespace Register;

use Register\Infra\Request;
use Register\Infra\View;

class ShowPageDataTab
{
    /** @var string */
    private $coreStyleFolder;

    /** @var string */
    private $helpText;

    /** @var View */
    private $view;

    public function __construct(string $coreStyleFolder, string $helpText, View $view)
    {
        $this->coreStyleFolder = $coreStyleFolder;
        $this->helpText = $helpText;
        $this->view = $view;
    }

    /** @param array<string,string> $pageData */
    public function __invoke(array $pageData, Request $request): string
    {
        return $this->view->render("page_data", [
            "action" => $request->url()->relative(),
            "iconFilename" => $this->coreStyleFolder . "help_icon.png",
            "iconAlt" => $this->helpText,
            "accessGroups" => $pageData["register_access"],
        ]);
    }
}
