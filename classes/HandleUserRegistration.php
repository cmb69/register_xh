<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Plib\DocumentStore;
use Plib\Random;
use Plib\Request;
use Plib\Response;
use Plib\Url;
use Plib\View;
use Register\Infra\Mailer;
use Register\Infra\Password;
use Register\Logic\Util;
use Register\Model\User;
use Register\Model\Users;

class HandleUserRegistration
{
    /** @var array<string,string> */
    private $conf;

    /** @var Random */
    private $random;

    /** @var View */
    private $view;

    /** @var DocumentStore */
    private $store;

    /** @var Mailer */
    private $mailer;

    /** @var Password */
    private $password;

    /** @param array<string,string> $conf */
    public function __construct(
        array $conf,
        Random $random,
        View $view,
        DocumentStore $store,
        Mailer $mailer,
        Password $password
    ) {
        $this->conf = $conf;
        $this->random = $random;
        $this->view = $view;
        $this->store = $store;
        $this->mailer = $mailer;
        $this->password = $password;
    }

    public function __invoke(Request $request): Response
    {
        if (!$this->conf["allowed_register"] || $request->username()) {
            return Response::create($this->view->message("fail", "error_unauthorized"));
        }
        switch ($request->post("register_action") ?? $request->get("register_action")) {
            default:
                return $this->showForm($request);
            case "register":
                return $this->registerUser($request);
            case "activate":
                return $this->activateUser($request);
        }
    }

    private function showForm(Request $request): Response
    {
        $user = new User("", "", [], "", "", "", "");
        return Response::create($this->renderForm($request->url(), $user, ""));
    }

    private function registerUser(Request $request): Response
    {
        $post = [
            "name" => $request->post("name") ?? "",
            "username" => $request->post("username") ?? "",
            "password1" => $request->post("password1") ?? "",
            "password2" => $request->post("password2") ?? "",
            "email" => $request->post("email") ?? "",
        ];
        $user = $this->userFromPost($post);
        if (($errors = Util::validateUser($user, $post["password2"]))) {
            return Response::create($this->renderForm($request->url(), $user, $post["password2"], $errors));
        }
        $users = Users::update($this->store);
        if ($users->user($post["username"])) {
            $this->store->rollback();
            return Response::create(
                $this->renderForm($request->url(), $user, $post["password2"], [["error_username_exists"]])
            );
        }
        if (($olduser = $users->userByEmail($user->getEmail()))) {
            $this->store->rollback();
            $this->sendDuplicateEmailNotification($user, $olduser, $request);
            return Response::redirect($request->url()->without("function")->without("register_action")->absolute());
        }
        $newUser = $this->registeredUser($users, $post);
        if (!$this->store->commit()) {
            return Response::create(
                $this->renderForm($request->url(), $user, $post["password2"], [["error_cannot_write_csv"]])
            );
        }
        $this->sendSuccessNotification($newUser, $request);
        return Response::redirect($request->url()->without("function")->without("register_action")->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderForm(Url $url, User $user, string $password2, array $errors = []): string
    {
        return $this->view->render("registerform", [
            "errors" => $errors,
            "name" => $user->getName(),
            "username" => $user->getUsername(),
            "password1" => $user->getPassword(),
            "password2" => $password2,
            "email" => $user->getEmail(),
            "cancel" => $url->without("function")->relative(),
        ]);
    }

    /** @param array{name:string,username:string,password1:string,password2:string,email:string} $post */
    private function userFromPost(array $post): User
    {
        return new User(
            $post["username"],
            $post["password1"],
            ["guest"],
            $post["name"],
            $post["email"],
            "activated",
            ""
        );
    }

    /** @param array{name:string,username:string,password1:string,password2:string,email:string} $post */
    private function registeredUser(Users $users, array $post): User
    {
        return $users->createUser(
            $post["username"],
            $this->password->hash($post["password1"]),
            array($this->conf["group_default"]),
            $post["name"],
            $post["email"],
            Util::base64url($this->random->bytes(15)),
            base64_encode($this->random->bytes(15))
        );
    }

    private function sendDuplicateEmailNotification(User $user, User $olduser, Request $request): bool
    {
        $url = $request->url()->with("function", "register_password");
        $html = $this->view->render("mail_duplicate", [
            "fullname" => $user->getName(),
            "username" => $user->getUsername(),
            "email" => $user->getEmail(),
            "remoteAddress" => $request->remoteAddr(),
            "other_fullname" => $olduser->getName(),
            "other_username" => $olduser->getUsername(),
            "other_email" => $olduser->getEmail(),
            "url" => $url->absolute(),
        ]);
        return  $this->mailer->sendMail(
            $user->getEmail(),
            $this->view->plain("email_subject", $request->header("HOST") ?? ""),
            html_entity_decode(strip_tags($html), ENT_COMPAT | ENT_SUBSTITUTE, "UTF-8"),
            $this->conf["mail_address"],
            $this->conf["mail_address"]
        );
    }

    private function sendSuccessNotification(User $user, Request $request): bool
    {
        $url = $request->url()->with("register_action", "activate")
            ->with("register_username", $user->getUsername())
            ->with("register_nonce", $user->getStatus());
        $html = $this->view->render("mail_activation", [
            "fullname" => $user->getName(),
            "username" => $user->getUsername(),
            "email" => $user->getEmail(),
            "remoteAddress" => $request->remoteAddr(),
            "url" => $url->absolute(),
        ]);
        return $this->mailer->sendMail(
            $user->getEmail(),
            $this->view->plain("email_subject", $request->header("HOST") ?? ""),
            html_entity_decode(strip_tags($html), ENT_COMPAT | ENT_SUBSTITUTE, "UTF-8"),
            $this->conf["mail_address"],
            $this->conf["mail_address"]
        );
    }

    private function activateUser(Request $request): Response
    {
        $params = [
            "username" => $request->get("register_username") ?? "",
            "nonce" => $request->get("register_nonce") ?? "",
        ];
        if (!$params["nonce"]) {
            return Response::create($this->view->message("fail", "error_code_missing"));
        }
        $users = Users::update($this->store);
        if (!($user = $users->user($params["username"]))) {
            $this->store->rollback();
            return Response::create($this->view->message("fail", "error_username_notfound", $params["username"]));
        }
        if (!hash_equals($user->getStatus(), $params["nonce"])) {
            $this->store->rollback();
            return Response::create($this->view->message("fail", "error_code_invalid"));
        }
        $user->activate($this->conf["group_activated"]);
        if (!$this->store->commit()) {
            return Response::create($this->view->message("fail", "error_cannot_write_csv"));
        }
        return Response::create($this->view->render("activation", [
            "url" => $request->url()->page($request->selected())->relative(),
        ]));
    }
}
