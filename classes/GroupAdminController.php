<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\DbService;
use Register\Infra\Pages;
use Register\Infra\Request;
use Register\Infra\Response;
use Register\Infra\Url;
use Register\Infra\View;
use Register\Logic\AdminProcessor;
use Register\Value\Html;
use Register\Value\UserGroup;
use XH\CSRFProtection as CsrfProtector;

class GroupAdminController
{
    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var View */
    private $view;

    /** @var DbService */
    private $dbService;

    /** @var Pages */
    private $pages;

    public function __construct(
        CsrfProtector $csrfProtector,
        View $view,
        DbService $dbService,
        Pages $pages
    ) {
        $this->csrfProtector = $csrfProtector;
        $this->view = $view;
        $this->dbService = $dbService;
        $this->pages = $pages;
    }

    public function __invoke(Request $request): Response
    {
        switch ($request->groupAdminAction()) {
            default:
                return (new Response)->body($this->editGroups($request));
            case "do_update":
                return (new Response)->body($this->saveGroups($request));
        }
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

        $processor = new AdminProcessor();
        [$newgroups, $save, $errors] = $processor->processGroups(...$request->groupAdminSubmission());

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
        return $this->view->render('admin_groups', [
            'csrfTokenInput' => Html::from($this->csrfProtector->tokenInput()),
            'actionUrl' => $url->withPage("register")->withParams(["admin" => "groups"])->relative(),
            'groups' => $this->groupNames($groups),
            'selects' => $this->selects($groups),
        ]);
    }

    /**
     * @param list<UserGroup> $groups
     * @return list<string>
     */
    private function groupNames(array $groups): array
    {
        return array_map(function (UserGroup $group) {
            return $group->getGroupname();
        }, $groups);
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
        for ($i = 0; $i < $this->pages->count(); $i++) {
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
