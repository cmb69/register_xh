<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Plib\Request;
use Plib\Response;
use Plib\Url;
use Plib\View;
use Register\Infra\Pages;

class PagesAdmin
{
    /** @var Pages */
    private $pages;

    /** @var View */
    private $view;

    public function __construct(Pages $pages, View $view)
    {
        $this->pages = $pages;
        $this->view = $view;
    }

    public function __invoke(Request $request): Response
    {
        return Response::create($this->view->render("pages", [
            "pages" => $this->pageRecords($request->url()),
        ]))->withTitle("Register – " . $this->view->text("menu_main"));
    }

    /** @return list<array{heading:string,url:string,indent:string,groups:string}> */
    private function pageRecords(Url $url): array
    {
        return array_map(function (int $i, array $pd) use ($url) {
            return [
                "heading" => $this->pages->heading($i),
                "url" => $url->page($this->pages->url($i))->with("edit")->relative(),
                "indent" => str_repeat("\xC2\xA0", 3 * ($this->pages->level($i) - 1)),
                "groups" => $pd["register_access"],
            ];
        }, array_keys($this->pages->data()), array_values($this->pages->data()));
    }
}
