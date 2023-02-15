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

    public function editGroupsAction(Request $request): string
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

    public function saveGroupsAction(Request $request): string
    {
        $this->csrfProtector->check();
        $errors = [];

        $delete = $_POST['delete'] ?? [];
        $add = $_POST['add'] ?? '';
        $groupname = $_POST['groupname'] ?? [];

        $deleted = false;
        $added   = false;

        $newgroups = array();
        foreach (array_keys($groupname) as $j) {
            if (!preg_match("/^[A-Za-z0-9_-]+$/", $groupname[$j])) {
                $errors[] = $this->lang['err_group_illegal'];
            }

            if (!isset($delete[$j]) || $delete[$j] == '') {
                $entry = new UserGroup($groupname[$j], $_POST['grouploginpage'][$j]);
                $newgroups[] = $entry;
            } else {
                $deleted = true;
            }
        }
        if ($add != '') {
            $entry = new UserGroup("NewGroup", '');
            $newgroups[] = $entry;
            $added = true;
        }

        if (!empty($errors)) {
            return $this->view->render('error', ['errors' => $errors])
                . $this->renderGroupsForm($newgroups, $request->url());
        }
        if ($deleted || $added) {
            return $this->renderGroupsForm($newgroups, $request->url());
        }
        // In case that nothing got deleted or added, store back (save got pressed)
        if (!$this->dbService->writeGroups($newgroups)) {
            $errors[] = $this->lang['err_cannot_write_csv']
                . ' (' . $this->dbService->dataFolder() . 'groups.csv' . ')';
        }
        if (!empty($errors)) {
            return $this->view->render('error', ['errors' => $errors])
                . $this->renderGroupsForm($newgroups, $request->url());
        }
        $filename = $this->dbService->dataFolder() . 'groups.csv';
        return $this->view->message('success', 'csv_written', $filename)
            . $this->renderGroupsForm($newgroups, $request->url());
    }

    /** @param list<UserGroup> $groups */
    private function renderGroupsForm(array $groups, Url $url): string
    {
        return $this->view->render('admin-groups', [
            'csrfTokenInput' => new HtmlString($this->csrfProtector->tokenInput()),
            'actionUrl' => $url->withPage("register")->relative(),
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
