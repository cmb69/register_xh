<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\DbService;
use Register\Infra\Mailer;
use Register\Infra\Password;
use Register\Infra\Random;
use Register\Infra\Request;
use Register\Infra\View;
use Register\Logic\Groups;
use Register\Logic\Users;
use Register\Logic\Util;
use Register\Value\Html;
use Register\Value\Mail;
use Register\Value\Response;
use Register\Value\User;
use Register\Value\UserGroup;
use XH\CSRFProtection;

class UserAdmin
{
    /** @var array<string,string> */
    private $conf;

    /** @var CSRFProtection */
    private $csrfProtection;

    /** @var DbService */
    private $dbService;

    /** @var Password */
    private $password;

    /** @var Random */
    private $random;

    /** @var Mailer */
    private $mailer;

    /** @var View */
    private $view;

    /** @param array<string,string> $conf */
    public function __construct(
        array $conf,
        CSRFProtection $csrfProtection,
        DbService $dbService,
        Password $password,
        Random $random,
        Mailer $mailer,
        View $view
    ) {
        $this->conf = $conf;
        $this->csrfProtection = $csrfProtection;
        $this->dbService = $dbService;
        $this->password = $password;
        $this->random = $random;
        $this->mailer = $mailer;
        $this->view = $view;
    }

    public function __invoke(Request $request): Response
    {
        switch ($request->action()) {
            default:
                return $this->overview($request);
            case "create":
                return $this->create();
            case "do_create":
                return $this->doCreate($request);
            case "update":
                return $this->update($request);
            case "do_update":
                return $this->doUpdate($request);
            case "change_password":
                return $this->changePassword($request);
            case "do_change_password":
                return $this->doChangePassword($request);
            case "mail":
                return $this->mail($request);
            case "do_mail":
                return $this->doMail($request);
            case "delete":
                return $this->delete($request);
            case "do_delete":
                return $this->doDelete($request);
        }
    }

    /** @param list<array{string}> $errors */
    private function overview(Request $request, array $errors = []): Response
    {
        $filters = $request->userFilters();
        [$users, $groups, $lock] = $this->dbService->readUsersAndGroupsWithLock(false);
        $users = Users::select($filters, $users);
        $groups = Groups::sortByName($groups);
        return $this->respondWith($this->view->render("users", [
            "errors" => $errors,
            "users" => $this->userRecords($users, $request->selectedUser()),
            "username" => $filters["username"],
            "name" => $filters["name"],
            "email" => $filters["email"],
            "groups" => $this->groupFilters($groups, $filters["group"]),
            "status" => $filters["status"],
        ]));
    }

    /**
     * @param list<User> $users
     * @return list<array{checked:string,username:string,fullname:string,email:string,groups:string,status_label:string}>
     */
    private function userRecords(array $users, string $username): array
    {
        return array_map(function (User $user) use ($username) {
            return [
                "checked" => $user->getUsername() === $username ? "checked" : "",
                "username" => $user->getUsername(),
                "fullname" => $user->getName(),
                "email" => $user->getEmail(),
                "groups" => implode(", ", $user->getAccessgroups()),
                "status_label" => $this->statusLabel($user->getStatus()),
            ];
        }, $users);
    }

    /**
     * @param list<UserGroup> $groups
     * @return list<array{string,string}>
     */
    private function groupFilters(array $groups, string $filter): array
    {
        array_unshift($groups, new UserGroup("", ""));
        return array_map(function (UserGroup $group) use ($filter) {
            return [
                $group->getGroupname(),
                $group->getGroupname() === $filter ? "selected" : "",
            ];
        }, $groups);
    }

    private function statusLabel(string $status): string
    {
        switch ($status) {
            case "activated":
                return "status_activated";
            case "locked":
                return "status_locked";
            case "":
                return "status_deactivated";
            default:
                return "status_not_yet_activated";
        }
    }

    private function create(): Response
    {
        $user = new User("", "", [$this->conf["group_default"]], "", "", "activated", "");
        [$groups, $lock] = $this->dbService->readGroupsWithLock(true);
        return $this->respondWith($this->renderCreateForm($user, $groups, ""));
    }

    private function doCreate(Request $request): Response
    {
        $this->csrfProtection->check();
        $user = $request->postedUser();
        [$users, $groups, $lock] = $this->dbService->readUsersAndGroupsWithLock(true);
        if (($errors = Users::validate($user, $request->postedPassword(), $users, true))) {
            return $this->respondWith($this->renderCreateForm($user, $groups, $request->postedPassword(), $errors));
        }
        $newUser = $user->withPassword($this->password->hash($user->getPassword()))
            ->withSecret(base64_encode($this->random->bytes(15)));
        $users = Users::add($newUser, $users);
        if (!$this->dbService->writeUsers($users)) {
            return $this->respondWith($this->renderCreateForm($user, $groups, $request->postedPassword(), [["err_cannot_write_csv"]]));
        }
        return Response::redirect($request->url()->withPage("register")->withParams(["admin" => "users"])->absolute());
    }

    /**
     * @param list<UserGroup> $groups
     * @param list<array{string}> $errors
     */
    private function renderCreateForm(User $user, array $groups, string $password2, array $errors = []): string
    {
        return $this->view->render("user_create", [
            "errors" => $errors,
            "token" => Html::from($this->csrfProtection->tokenInput()),
            "username" => $user->getUsername(),
            "name" => $user->getName(),
            "email" => $user->getEmail(),
            "groups" => $this->groupRecords($user, $groups),
            "states" => $this->states($user),
            "password1" => $user->getPassword(),
            "password2" => $password2,
        ]);
    }

    private function update(Request $request): Response
    {
        $username = $request->selectedUser();
        [$users, $groups, $lock] = $this->dbService->readUsersAndGroupsWithLock(false);
        if (!($user = Users::findByUsername($username, $users))) {
            return $this->overview($request, [["err_user_does_not_exist", $username]]);
        }
        return $this->respondWith($this->renderUpdateForm($user, $groups));
    }

    private function doUpdate(Request $request): Response
    {
        $this->csrfProtection->check();
        $username = $request->selectedUser();
        [$users, $groups, $lock] = $this->dbService->readUsersAndGroupsWithLock(true);
        if (!($user = Users::findByUsername($username, $users))) {
            return $this->respondWith($this->view->error("err_user_does_not_exist", $username));
        }
        $post = $request->userPost();
        $user = $user->with($post["name"], $post["email"], $post["groups"], $post["status"]);
        if (($errors = Users::validate($user, $user->getPassword(), $users, false))) {
            return $this->respondWith($this->renderUpdateForm($user, $groups, $errors));
        }
        $users = Users::update($user, $users);
        if (!$this->dbService->writeUsers($users)) {
            return $this->respondWith($this->renderUpdateForm($user, $groups, [["err_cannot_write_csv"]]));
        }
        return Response::redirect($request->url()->withPage("register")->withParams(["admin" => "users"])->absolute());
    }

    /**
     * @param list<UserGroup> $groups
     * @param list<array{string}> $errors
     */
    private function renderUpdateForm(User $user, array $groups, array $errors = []): string
    {
        return $this->view->render("user_update", [
            "errors" => $errors,
            "token" => Html::from($this->csrfProtection->tokenInput()),
            "username" => $user->getUsername(),
            "name" => $user->getName(),
            "email" => $user->getEmail(),
            "groups" => $this->groupRecords($user, $groups),
            "states" => $this->states($user),
        ]);
    }

    /** @return list<array{string,string,string}> */
    private function states(User $user): array
    {
        return array_map(function (string $status) use ($user) {
            return [$status, "status_$status", $user->getStatus() === $status ? "selected" : ""];
        }, User::STATUSES);
    }

    /**
     * @param list<UserGroup> $groups
     * @return list<array{string,string}>
     */
    private function groupRecords(User $user, array $groups): array
    {
        return array_map(function (UserGroup $group) use ($user) {
            return [
                $group->getGroupname(),
                in_array($group->getGroupname(), $user->getAccessgroups(), true) ? "selected" : "",
            ];
        }, $groups);
    }

    private function changePassword(Request $request): Response
    {
        $username = $request->selectedUser();
        [$users, $lock] = $this->dbService->readUsersWithLock(false);
        if (!($user = Users::findByUsername($username, $users))) {
            return $this->overview($request, [["err_user_does_not_exist", $username]]);
        }
        $user = $user->withPassword("");
        return $this->respondWith($this->renderPasswordForm($user, ""));
    }

    private function doChangePassword(Request $request): Response
    {
        $this->csrfProtection->check();
        $username = $request->selectedUser();
        [$users, $lock] = $this->dbService->readUsersWithLock(true);
        if (!($user = Users::findByUsername($username, $users))) {
            return $this->respondWith($this->view->error("err_user_does_not_exist", $username));
        }
        $post = $request->changePasswordPost();
        $user = $user->withPassword($post["password1"]);
        if (($errors = Users::validate($user, $post["password2"], $users, false))) {
            return $this->respondWith($this->renderPasswordForm($user, $post["password2"], $errors));
        }
        $user = $user->withPassword($this->password->hash($post["password1"]));
        $users = Users::update($user, $users);
        if (!$this->dbService->writeUsers($users)) {
            $user = $user->withPassword($post["password1"]);
            return $this->respondWith($this->renderPasswordForm($user, $post["password2"], [["err_cannot_write_csv"]]));
        }
        return Response::redirect($request->url()->withPage("register")->withParams(["admin" => "users"])->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderPasswordForm(User $user, string $password2, array $errors = []): string
    {
        return $this->view->render("user_password", [
            "errors" => $errors,
            "token" => Html::from($this->csrfProtection->tokenInput()),
            "username" => $user->getUsername(),
            "password1" => $user->getPassword(),
            "password2" => $password2,
        ]);
    }

    private function mail(Request $request): Response
    {
        $username = $request->selectedUser();
        [$users, $lock] = $this->dbService->readUsersWithLock(false);
        if (!($user = Users::findByUsername($username, $users))) {
            return $this->overview($request, [["err_user_does_not_exist", $username]]);
        }
        $mail = new Mail("", "");
        return $this->respondWith($this->renderMailForm($user, $mail));
    }

    private function doMail(Request $request): Response
    {
        $this->csrfProtection->check();
        $username = $request->selectedUser();
        [$users, $lock] = $this->dbService->readUsersWithLock(false);
        if (!($user = Users::findByUsername($username, $users))) {
            return $this->respondWith($this->view->error("err_user_does_not_exist", $username));
        }
        $mail = $request->postedMail();
        if (($errors = Util::validateMail($mail))) {
            return $this->respondWith($this->renderMailForm($user, $mail, $errors));
        }
        if (!$this->mailer->adHocMail($user, $mail, $this->conf["senderemail"])) {
            return $this->respondWith($this->renderMailForm($user, $mail, [["err_send_mail"]]));
        }
        return Response::redirect($request->url()->withPage("register")->withParams(["admin" => "users"])->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderMailForm(User $user, Mail $mail, array $errors = []): string
    {
        return $this->view->render("user_mail", [
            "errors" => $errors,
            "token" => Html::from($this->csrfProtection->tokenInput()),
            "email" => $user->getEmail(),
            "subject" => $mail->subject(),
            "message" => $mail->message(),
        ]);
    }

    private function delete(Request $request): Response
    {
        $username = $request->selectedUser();
        [$users, $lock] = $this->dbService->readUsersWithLock(false);
        if (!($user = Users::findByUsername($username, $users))) {
            return $this->overview($request, [["err_user_does_not_exist", $username]]);
        }
        return $this->respondWith($this->renderDeleteForm($user));
    }

    private function doDelete(Request $request): Response
    {
        $this->csrfProtection->check();
        $username = $request->selectedUser();
        [$users, $lock] = $this->dbService->readUsersWithLock(true);
        if (!($user = Users::findByUsername($username, $users))) {
            return $this->respondWith($this->view->error("err_user_does_not_exist", $username));
        }
        $users = Users::delete($user, $users);
        if (!$this->dbService->writeUsers($users)) {
            return $this->respondWith($this->renderDeleteForm($user, [["err_cannot_write_csv"]]));
        }
        return Response::redirect($request->url()->withPage("register")->withParams(["admin" => "users"])->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderDeleteForm(User $user, array $errors = []): string
    {
        return $this->view->render("user_delete", [
            "errors" => $errors,
            "token" => Html::from($this->csrfProtection->tokenInput()),
            "username" => $user->getUsername(),
        ]);
    }

    private function respondWith(string $output): Response
    {
        $title = "Register – " . $this->view->text("mnu_user_admin");
        return Response::create("<section class=\"register_admin\">\n<h1>$title</h1>\n$output</section>\n")
            ->withTitle($title);
    }
}
