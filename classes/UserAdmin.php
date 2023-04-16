<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\CsrfProtector;
use Register\Infra\Mailer;
use Register\Infra\Password;
use Register\Infra\Random;
use Register\Infra\Request;
use Register\Infra\UserGroupRepository;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Logic\Util;
use Register\Value\Mail;
use Register\Value\Response;
use Register\Value\User;
use Register\Value\UserGroup;

class UserAdmin
{
    /** @var array<string,string> */
    private $conf;

    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var UserRepository */
    private $userRepository;

    /** @var UserGroupRepository */
    private $userGroupRepository;

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
        CsrfProtector $csrfProtector,
        UserRepository $userRepository,
        UserGroupRepository $userGroupRepository,
        Password $password,
        Random $random,
        Mailer $mailer,
        View $view
    ) {
        $this->conf = $conf;
        $this->csrfProtector = $csrfProtector;
        $this->userRepository = $userRepository;
        $this->userGroupRepository = $userGroupRepository;
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
        $users = $this->userRepository->select($filters);
        $groups = $this->userGroupRepository->all();
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
        return $this->respondWith($this->renderCreateForm($user, ""));
    }

    private function doCreate(Request $request): Response
    {
        if (!$this->csrfProtector->check()) {
            return $this->respondWith($this->view->error("err_unauthorized"));
        }
        $username = $request->selectedUser();
        if ($this->userRepository->findByUsername($username)) {
            return $this->respondWith($this->view->error("err_username_exists"));
        }
        $user = $request->postedUser();
        if (($errors = Util::validateUser($user, $request->postedPassword()))) {
            return $this->respondWith($this->renderCreateForm($user, $request->postedPassword(), $errors));
        }
        if ($this->userRepository->hasDuplicateEmail($user)) {
            return $this->respondWith($this->renderCreateForm($user, $request->postedPassword(), [["err_email_exists"]]));
        }
        $newUser = $user->withPassword($this->password->hash($user->getPassword()))
            ->withSecret(base64_encode($this->random->bytes(15)));
        if (!$this->userRepository->save($newUser)) {
            return $this->respondWith($this->renderCreateForm($user, $request->postedPassword(), [["err_cannot_write_csv"]]));
        }
        return Response::redirect($request->url()->withPage("register")->with("admin", "users")->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderCreateForm(User $user, string $password2, array $errors = []): string
    {
        $groups = $this->userGroupRepository->all();
        return $this->view->render("user_create", [
            "errors" => $errors,
            "token" => $this->csrfProtector->token(),
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
        if (!($user = $this->userRepository->findByUsername($username))) {
            return $this->overview($request, [["err_user_does_not_exist", $username]]);
        }
        return $this->respondWith($this->renderUpdateForm($user));
    }

    private function doUpdate(Request $request): Response
    {
        if (!$this->csrfProtector->check()) {
            return $this->respondWith($this->view->error("err_unauthorized"));
        }
        $username = $request->selectedUser();
        if (!($user = $this->userRepository->findByUsername($username))) {
            return $this->respondWith($this->view->error("err_user_does_not_exist", $username));
        }
        $post = $request->userPost();
        $user = $user->with($post["name"], $post["email"], $post["groups"], $post["status"]);
        if (($errors = Util::validateUser($user, $user->getPassword()))) {
            return $this->respondWith($this->renderUpdateForm($user, $errors));
        }
        if ($this->userRepository->hasDuplicateEmail($user)) {
            return $this->respondWith($this->renderUpdateForm($user, [["err_email_exists"]]));
        }
        if (!$this->userRepository->save($user)) {
            return $this->respondWith($this->renderUpdateForm($user, [["err_cannot_write_csv"]]));
        }
        return Response::redirect($request->url()->withPage("register")->with("admin", "users")->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderUpdateForm(User $user, array $errors = []): string
    {
        $groups = $this->userGroupRepository->all();
        return $this->view->render("user_update", [
            "errors" => $errors,
            "token" => $this->csrfProtector->token(),
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
                in_array($group->getGroupname(), $user->getAccessgroups(), true) ? "checked" : "",
            ];
        }, $groups);
    }

    private function changePassword(Request $request): Response
    {
        $username = $request->selectedUser();
        if (!($user = $this->userRepository->findByUsername($username))) {
            return $this->overview($request, [["err_user_does_not_exist", $username]]);
        }
        $user = $user->withPassword("");
        return $this->respondWith($this->renderPasswordForm($user, ""));
    }

    private function doChangePassword(Request $request): Response
    {
        if (!$this->csrfProtector->check()) {
            return $this->respondWith($this->view->error("err_unauthorized"));
        }
        $username = $request->selectedUser();
        if (!($user = $this->userRepository->findByUsername($username))) {
            return $this->respondWith($this->view->error("err_user_does_not_exist", $username));
        }
        $post = $request->changePasswordPost();
        $user = $user->withPassword($post["password1"]);
        if (($errors = Util::validateUser($user, $post["password2"]))) {
            return $this->respondWith($this->renderPasswordForm($user, $post["password2"], $errors));
        }
        $newUser = $user->withPassword($this->password->hash($post["password1"]));
        if (!$this->userRepository->save($newUser)) {
            return $this->respondWith($this->renderPasswordForm($user, $post["password2"], [["err_cannot_write_csv"]]));
        }
        return Response::redirect($request->url()->withPage("register")->with("admin", "users")->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderPasswordForm(User $user, string $password2, array $errors = []): string
    {
        return $this->view->render("user_password", [
            "errors" => $errors,
            "token" => $this->csrfProtector->token(),
            "username" => $user->getUsername(),
            "password1" => $user->getPassword(),
            "password2" => $password2,
        ]);
    }

    private function mail(Request $request): Response
    {
        $username = $request->selectedUser();
        if (!($user = $this->userRepository->findByUsername($username))) {
            return $this->overview($request, [["err_user_does_not_exist", $username]]);
        }
        $mail = new Mail("", "");
        return $this->respondWith($this->renderMailForm($user, $mail));
    }

    private function doMail(Request $request): Response
    {
        if (!$this->csrfProtector->check()) {
            return $this->respondWith($this->view->error("err_unauthorized"));
        }
        $username = $request->selectedUser();
        if (!($user = $this->userRepository->findByUsername($username))) {
            return $this->respondWith($this->view->error("err_user_does_not_exist", $username));
        }
        $mail = $request->postedMail();
        if (($errors = Util::validateMail($mail))) {
            return $this->respondWith($this->renderMailForm($user, $mail, $errors));
        }
        if (!$this->mailer->adHocMail($user, $mail, $this->conf["senderemail"])) {
            return $this->respondWith($this->renderMailForm($user, $mail, [["err_send_mail"]]));
        }
        return Response::redirect($request->url()->withPage("register")->with("admin", "users")->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderMailForm(User $user, Mail $mail, array $errors = []): string
    {
        return $this->view->render("user_mail", [
            "errors" => $errors,
            "token" => $this->csrfProtector->token(),
            "email" => $user->getEmail(),
            "subject" => $mail->subject(),
            "message" => $mail->message(),
        ]);
    }

    private function delete(Request $request): Response
    {
        $username = $request->selectedUser();
        if (!($user = $this->userRepository->findByUsername($username))) {
            return $this->overview($request, [["err_user_does_not_exist", $username]]);
        }
        return $this->respondWith($this->renderDeleteForm($user));
    }

    private function doDelete(Request $request): Response
    {
        if (!$this->csrfProtector->check()) {
            return $this->respondWith($this->view->error("err_unauthorized"));
        }
        $username = $request->selectedUser();
        if (!($user = $this->userRepository->findByUsername($username))) {
            return $this->respondWith($this->view->error("err_user_does_not_exist", $username));
        }
        if (!$this->userRepository->delete($user)) {
            return $this->respondWith($this->renderDeleteForm($user, [["err_cannot_write_csv"]]));
        }
        return Response::redirect($request->url()->withPage("register")->with("admin", "users")->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderDeleteForm(User $user, array $errors = []): string
    {
        return $this->view->render("user_delete", [
            "errors" => $errors,
            "token" => $this->csrfProtector->token(),
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
