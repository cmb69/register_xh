<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Plib\CsrfProtector;
use Plib\DocumentStore;
use Plib\Request;
use Plib\Response;
use Plib\View;
use Register\Infra\Pages;
use Register\Logic\Util;
use Register\Model\Groups;
use Register\Model\UserGroup;

class GroupAdmin
{
    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var DocumentStore */
    private $store;

    /** @var Pages */
    private $pages;

    /** @var View */
    private $view;

    public function __construct(
        CsrfProtector $csrfProtector,
        DocumentStore $store,
        Pages $pages,
        View $view
    ) {
        $this->csrfProtector = $csrfProtector;
        $this->store = $store;
        $this->pages = $pages;
        $this->view = $view;
    }

    public function __invoke(Request $request): Response
    {
        switch (($request->post("action") ?? $request->get("action")) ?? "") {
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
        $groups = Groups::retrieve($this->store)->groups();
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
        return $this->respondWith($this->renderEditForm($group, "create"));
    }

    private function doCreate(Request $request): Response
    {
        if (!$this->csrfProtector->check($request->post("register_token"))) {
            return $this->respondWith($this->view->message("fail", "error_unauthorized"));
        }
        $groupname = $request->get("group") ?? "";
        $groups = Groups::update($this->store);
        if ($groups->group($groupname) !== null) {
            $this->store->rollback();
            return $this->respondWith($this->view->message("fail", "error_groupname_exists"));
        }
        $group = $groups->createGroup($request->post("groupname") ?? "", $request->post("loginpage") ?? "");
        if (($errors = Util::validateGroup($group))) {
            $this->store->rollback();
            return $this->respondWith($this->renderEditForm($group, "create", $errors));
        }
        if (!$this->store->commit()) {
            return $this->respondWith($this->renderEditForm($group, "create", [["error_cannot_write_csv"]]));
        }
        return Response::redirect($request->url()->page("register")->with("admin", "groups")->absolute());
    }

    private function update(Request $request): Response
    {
        $groupname = $request->get("group") ?? "";
        $groups = Groups::retrieve($this->store);
        if (!($group = $groups->group($groupname))) {
            return $this->overview([["error_group_does_not_exist", $groupname]]);
        }
        return $this->respondWith($this->renderEditForm($group, "update"));
    }

    private function doUpdate(Request $request): Response
    {
        if (!$this->csrfProtector->check($request->post("register_token"))) {
            return $this->respondWith($this->view->message("fail", "error_unauthorized"));
        }
        $groupname = $request->get("group") ?? "";
        $groups = Groups::update($this->store);
        if (!($group = $groups->group($groupname))) {
            $this->store->rollback();
            return $this->respondWith($this->view->message("fail", "error_group_does_not_exist", $groupname));
        }
        $post = ["loginpage" => $request->post("loginpage") ?? ""];
        $group->setLoginpage($post["loginpage"]);
        assert(!Util::validateGroup($group));
        if (!$this->store->commit()) {
            return $this->respondWith($this->renderEditForm($group, "update", [["error_cannot_write_csv"]]));
        }
        return Response::redirect($request->url()->page("register")->with("admin", "groups")->absolute());
    }

    private function delete(Request $request): Response
    {
        $groupname = $request->get("group") ?? "";
        $groups = Groups::retrieve($this->store);
        if (!($group = $groups->group($groupname))) {
            return $this->overview([["error_group_does_not_exist", $groupname]]);
        }
        return $this->respondWith($this->renderEditForm($group, "delete"));
    }

    private function doDelete(Request $request): Response
    {
        if (!$this->csrfProtector->check($request->post("register_token"))) {
            return $this->respondWith($this->view->message("fail", "error_unauthorized"));
        }
        $groupname = $request->get("group") ?? "";
        $groups = Groups::update($this->store);
        if (!($group = $groups->group($groupname))) {
            $this->store->rollback();
            return $this->respondWith($this->view->message("fail", "error_group_does_not_exist", $groupname));
        }
        $groups->deleteGroup($groupname);
        if (!$this->store->commit()) {
            return $this->respondWith($this->renderEditForm($group, "delete", [["error_cannot_write_csv"]]));
        }
        return Response::redirect($request->url()->page("register")->with("admin", "groups")->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderEditForm(UserGroup $group, string $action, array $errors = []): string
    {
        return $this->view->render("group_edit", [
            "errors" => $errors,
            "token" => $this->csrfProtector->token(),
            "group" => $group->getGroupname(),
            "options" => $this->options($group->getLoginpage()),
            "show_details" => $action !== "delete",
            "disabled" => $action === "create" ? "" : "disabled",
            "button" => "do_" . $action,
            "label" => "label_" . $action,
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
        $title = "Register – " . $this->view->text("menu_group_admin");
        return Response::create("<section class=\"register_admin\">\n<h1>$title</h1>\n$output</section>\n")
            ->withTitle($title);
    }
}
