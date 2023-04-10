<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\DbService;
use Register\Infra\Pages;
use Register\Infra\Request;
use Register\Infra\View;
use Register\Logic\Groups;
use Register\Value\Html;
use Register\Value\Response;
use Register\Value\UserGroup;
use XH\CSRFProtection;

class GroupAdmin
{
    /** @var CSRFProtection */
    private $csrfProtection;

    /** @var DbService */
    private $dbService;

    /** @var Pages */
    private $pages;

    /** @var View */
    private $view;

    public function __construct(
        CSRFProtection $csrfProtection,
        DbService $dbService,
        Pages $pages,
        View $view
    ) {
        $this->csrfProtection = $csrfProtection;
        $this->dbService = $dbService;
        $this->pages = $pages;
        $this->view = $view;
    }

    public function __invoke(Request $request): Response
    {
        switch ($request->action()) {
            default:
                return $this->overview();
            case "create":
                return $this->create();
            case "do_create":
                return $this->doCreate($request);
            case "update":
                return $this->update($request);
            case "do_update":
                return $this->doUpdate($request);
            case "delete":
                return $this->delete($request);
            case "do_delete":
                return $this->doDelete($request);
        }
    }

    /** @param list<array{string}> $errors */
    private function overview(array $errors = []): Response
    {
        [$groups, $lock] = $this->dbService->readGroupsWithLock(false);
        $groups = Groups::sortByName($groups);
        return $this->respondWith($this->view->render("groups", [
            "errors" => $errors,
            "groups" => array_map(function (UserGroup $group) {
                return [
                    "name" => $group->getGroupname(),
                    "loginpage" => $group->getLoginpage(),
                ];
            }, $groups),
        ]));
    }

    private function create(): Response
    {
        $group = new UserGroup("", "");
        return $this->respondWith($this->renderCreateForm($group));
    }

    private function doCreate(Request $request): Response
    {
        $this->csrfProtection->check();
        $group = $request->postedGroup();
        [$groups, $lock] = $this->dbService->readGroupsWithLock(true);
        if (($errors = Groups::validate($group, $groups, true))) {
            return $this->respondWith($this->renderCreateForm($group, $errors));
        }
        $groups = Groups::add($group, $groups);
        if (!$this->dbService->writeGroups($groups)) {
            return $this->respondWith($this->renderCreateForm($group, [["err_cannot_write_csv"]]));
        }
        return Response::redirect($request->url()->withPage("register")->withParams(["admin" => "groups"])->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderCreateForm(UserGroup $group, array $errors = []): string
    {
        return $this->view->render("group_create", [
            "errors" => $errors,
            "token" => Html::from($this->csrfProtection->tokenInput()),
            "group" => $group->getGroupname(),
            "options" => $this->options($group->getLoginpage()),
        ]);
    }

    private function update(Request $request): Response
    {
        $groupname = $request->selectedGroup();
        [$groups, $lock] = $this->dbService->readGroupsWithLock(false);
        if (!($group = Groups::findByGroupname($groupname, $groups))) {
            return $this->overview([["err_group_does_not_exist", $groupname]]);
        }
        return $this->respondWith($this->renderUpdateForm($group));
    }

    private function doUpdate(Request $request): Response
    {
        $this->csrfProtection->check();
        $groupname = $request->selectedGroup();
        [$groups, $lock] = $this->dbService->readGroupsWithLock(true);
        if (!($group = Groups::findByGroupname($groupname, $groups))) {
            return $this->respondWith($this->view->error("err_group_does_not_exist", $groupname));
        }
        $group = $request->postedGroup();
        assert(!Groups::validate($group, $groups, false));
        $groups = Groups::update($group, $groups);
        if (!$this->dbService->writeGroups($groups)) {
            return $this->respondWith($this->renderUpdateForm($group, [["err_cannot_write_csv"]]));
        }
        return Response::redirect($request->url()->withPage("register")->withParams(["admin" => "groups"])->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderUpdateForm(UserGroup $group, array $errors = []): string
    {
        return $this->view->render("group_update", [
            "errors" => $errors,
            "token" => Html::from($this->csrfProtection->tokenInput()),
            "group" => $group->getGroupname(),
            "options" => $this->options($group->getLoginpage()),
        ]);
    }

    private function delete(Request $request): Response
    {
        $groupname = $request->selectedGroup();
        [$groups, $lock] = $this->dbService->readGroupsWithLock(false);
        if (!($group = Groups::findByGroupname($groupname, $groups))) {
            return $this->overview([["err_group_does_not_exist", $groupname]]);
        }
        return $this->respondWith($this->renderDeleteForm($group));
    }

    private function doDelete(Request $request): Response
    {
        $this->csrfProtection->check();
        $groupname = $request->selectedGroup();
        [$groups, $lock] = $this->dbService->readGroupsWithLock(true);
        if (!($group = Groups::findByGroupname($groupname, $groups))) {
            return $this->respondWith($this->view->error("err_group_does_not_exist", $groupname));
        }
        $groups = Groups::delete($group, $groups);
        if (!$this->dbService->writeGroups($groups)) {
            return $this->respondWith($this->renderDeleteForm($group, [["err_cannot_write_csv"]]));
        }
        return Response::redirect($request->url()->withPage("register")->withParams(["admin" => "groups"])->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderDeleteForm(UserGroup $group, array $errors = []): string
    {
        return $this->view->render("group_delete", [
            "errors" => $errors,
            "token" => Html::from($this->csrfProtection->tokenInput()),
            "groupname" => $group->getGroupname(),
        ]);
    }

    /** @return list<array{selected:string,url:string,heading:string}> */
    private function options(string $loginpage): array
    {
        $res = [];
        for ($i = 0; $i < $this->pages->count(); $i++) {
            $res[] = [
                "selected" => $this->pages->url($i) === $loginpage ? "selected" : "",
                "url" => $this->pages->url($i),
                "heading" => str_repeat("\xC2\xA0", 3 * ($this->pages->level($i) - 1)) . $this->pages->heading($i),
            ];
        }
        return $res;
    }

    private function respondWith(string $output): Response
    {
        $title = "Register – " . $this->view->text("mnu_group_admin");
        return Response::create("<section class=\"register_admin\">\n<h1>$title</h1>\n$output</section>\n")
            ->withTitle($title);
    }
}
