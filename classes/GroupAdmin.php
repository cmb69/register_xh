<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\CsrfProtector;
use Register\Infra\Pages;
use Register\Infra\Request;
use Register\Infra\UserGroupRepository;
use Register\Infra\View;
use Register\Logic\Util;
use Register\Value\Response;
use Register\Value\UserGroup;

class GroupAdmin
{
    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var UserGroupRepository */
    private $userGroupRepository;

    /** @var Pages */
    private $pages;

    /** @var View */
    private $view;

    public function __construct(
        CsrfProtector $csrfProtector,
        UserGroupRepository $userGroupRepository,
        Pages $pages,
        View $view
    ) {
        $this->csrfProtector = $csrfProtector;
        $this->userGroupRepository = $userGroupRepository;
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
        $groups = $this->userGroupRepository->all();
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
        if (!$this->csrfProtector->check()) {
            return $this->respondWith($this->view->error("err_unauthorized"));
        }
        $groupname = $request->selectedGroup();
        if ($this->userGroupRepository->findByGroupname($groupname)) {
            return $this->respondWith($this->view->error("err_groupname_exists"));
        }
        $group = $request->postedGroup();
        if (($errors = Util::validateGroup($group))) {
            return $this->respondWith($this->renderCreateForm($group, $errors));
        }
        if (!$this->userGroupRepository->save($group)) {
            return $this->respondWith($this->renderCreateForm($group, [["err_cannot_write_csv"]]));
        }
        return Response::redirect($request->url()->withPage("register")->with("admin", "groups")->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderCreateForm(UserGroup $group, array $errors = []): string
    {
        return $this->view->render("group_create", [
            "errors" => $errors,
            "token" => $this->csrfProtector->token(),
            "group" => $group->getGroupname(),
            "options" => $this->options($group->getLoginpage()),
        ]);
    }

    private function update(Request $request): Response
    {
        $groupname = $request->selectedGroup();
        if (!($group = $this->userGroupRepository->findByGroupname($groupname))) {
            return $this->overview([["err_group_does_not_exist", $groupname]]);
        }
        return $this->respondWith($this->renderUpdateForm($group));
    }

    private function doUpdate(Request $request): Response
    {
        if (!$this->csrfProtector->check()) {
            return $this->respondWith($this->view->error("err_unauthorized"));
        }
        $groupname = $request->selectedGroup();
        if (!($group = $this->userGroupRepository->findByGroupname($groupname))) {
            return $this->respondWith($this->view->error("err_group_does_not_exist", $groupname));
        }
        $group = $request->postedGroup();
        assert(!Util::validateGroup($group));
        if (!$this->userGroupRepository->save($group)) {
            return $this->respondWith($this->renderUpdateForm($group, [["err_cannot_write_csv"]]));
        }
        return Response::redirect($request->url()->withPage("register")->with("admin", "groups")->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderUpdateForm(UserGroup $group, array $errors = []): string
    {
        return $this->view->render("group_update", [
            "errors" => $errors,
            "token" => $this->csrfProtector->token(),
            "group" => $group->getGroupname(),
            "options" => $this->options($group->getLoginpage()),
        ]);
    }

    private function delete(Request $request): Response
    {
        $groupname = $request->selectedGroup();
        if (!($group = $this->userGroupRepository->findByGroupname($groupname))) {
            return $this->overview([["err_group_does_not_exist", $groupname]]);
        }
        return $this->respondWith($this->renderDeleteForm($group));
    }

    private function doDelete(Request $request): Response
    {
        if (!$this->csrfProtector->check()) {
            return $this->respondWith($this->view->error("err_unauthorized"));
        }
        $groupname = $request->selectedGroup();
        if (!($group = $this->userGroupRepository->findByGroupname($groupname))) {
            return $this->respondWith($this->view->error("err_group_does_not_exist", $groupname));
        }
        if (!$this->userGroupRepository->delete($group)) {
            return $this->respondWith($this->renderDeleteForm($group, [["err_cannot_write_csv"]]));
        }
        return Response::redirect($request->url()->withPage("register")->with("admin", "groups")->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderDeleteForm(UserGroup $group, array $errors = []): string
    {
        return $this->view->render("group_delete", [
            "errors" => $errors,
            "token" => $this->csrfProtector->token(),
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
