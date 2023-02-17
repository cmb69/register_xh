<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use XH\CSRFProtection as CsrfProtector;
use XH\Pages;

use Register\Value\HtmlString;
use Register\Value\UserGroup;
use Register\Logic\AdminProcessor;
use Register\Infra\DbService;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\View;

class GroupAdminController
{
    /** @var string */
    private $pluginFolder;

    /** @var array<string,string> */
    private $lang;

    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var View */
    private $view;

    /** @var DbService */
    private $dbService;

    /** @var Pages */
    private $pages;

    /** @param array<string,string> $lang */
    public function __construct(
        string $pluginFolder,
        array $lang,
        CsrfProtector $csrfProtector,
        DbService $dbService,
        Pages $pages
    ) {
        $this->pluginFolder = $pluginFolder;
        $this->lang = $lang;
        $this->csrfProtector = $csrfProtector;
        $this->view = new View($this->pluginFolder, $this->lang);
        $this->dbService = $dbService;
        $this->pages = $pages;
    }

    public function __invoke(Request $request): string
    {
        if ($request->method() === "post") {
            return $this->saveGroups($request);
        }
        return $this->editGroups($request);
    }

    private function editGroups(Request $request): string
    {
        $filename = $this->dbService->dataFolder() . 'groups.csv';
        if ($this->dbService->hasGroupsFile()) {
            $groups = $this->dbService->readGroups();
            return $this->renderGroupsForm($groups, $request->url())
                . $this->view->messagep('info', count($groups), 'entries_in_csv', $filename);
        } else {
            return $this->view->message('fail', 'err_csv_missing', $filename);
        }
    }

    private function saveGroups(Request $request): string
    {
        $this->csrfProtector->check();

        $delete = $_POST['delete'] ?? [];
        $add = $_POST['add'] ?? '';
        $groupname = $_POST['groupname'] ?? [];
        $groupLoginPages = $_POST['grouploginpage'] ?? [];

        $processor = new AdminProcessor();
        [$newgroups, $save, $errors] = $processor->processGroups($add, $delete, $groupname, $groupLoginPages);

        if (!empty($errors)) {
            return $this->renderErrorMessages($errors)
                . $this->renderGroupsForm($newgroups, $request->url());
        }
        if (!$save) {
            return $this->renderGroupsForm($newgroups, $request->url());
        }
        $filename = $this->dbService->dataFolder() . 'groups.csv';
        $saved = $this->dbService->writeGroups($newgroups);
        if (!$saved) {
            return $this->view->message("fail", 'err_cannot_write_csv_adm', $filename)
                . $this->renderGroupsForm($newgroups, $request->url());
        }
        return $this->view->message('success', 'csv_written', $filename)
            . $this->renderGroupsForm($newgroups, $request->url());
    }

    /** @param list<array{string}> $errors */
    private function renderErrorMessages(array $errors): string
    {
        return implode("", array_map(function ($args) {
            return $this->view->message("fail", ...$args);
        }, $errors));
    }

    /** @param list<UserGroup> $groups */
    private function renderGroupsForm(array $groups, Url $url): string
    {
        return $this->view->render('admin-groups', [
            'csrfTokenInput' => new HtmlString($this->csrfProtector->tokenInput()),
            'actionUrl' => $url->withPage("register")->withParams(["admin" => "groups"])->relative(),
            'groups' => $groups,
            'selects' => $this->selects($groups),
        ]);
    }

    /**
     * @param list<UserGroup> $groups
     * @return list<list<array{selected:string,indent:string,url:string,heading:string}>>
     */
    private function selects(array $groups): array
    {
        $selects = [];
        foreach ($groups as $group) {
            $selects[] = $this->options($group->getLoginpage());
        }
        return $selects;
    }

    /** @return list<array{selected:string,indent:string,url:string,heading:string}> */
    private function options(string $loginpage): array
    {
        $res = [];
        for ($i = 0; $i < $this->pages->getCount(); $i++) {
            $res[] = [
                "selected" => $this->pages->url($i) === $loginpage ? "selected" : "",
                "indent" => str_repeat("\xC2\xA0", 3 * ($this->pages->level($i) - 1)),
                "url" => $this->pages->url($i),
                "heading" => $this->pages->heading($i),
            ];
        }
        return $res;
    }
}
