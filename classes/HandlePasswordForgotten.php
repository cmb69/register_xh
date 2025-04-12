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
use Plib\Request;
use Plib\Response;
use Plib\Url;
use Plib\View;
use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\Mailer;
use Register\Infra\Password;
use Register\Logic\Util;
use Register\Model\User;
use Register\Model\Users;
use Register\Value\Passwords;

class HandlePasswordForgotten
{
    private const TTL = 3600;

    /** @var array<string,string> */
    private $conf;

    /** @var View */
    private $view;

    /** @var DocumentStore */
    private $store;

    /** @var Password */
    private $password;

    /** @var Mailer */
    private $mailer;

    /** @var LoginManager */
    private $loginManager;

    /** @var Logger */
    private $logger;

    /** @param array<string,string> $conf */
    public function __construct(
        array $conf,
        View $view,
        DocumentStore $store,
        Password $password,
        Mailer $mailer,
        LoginManager $loginManager,
        Logger $logger
    ) {
        $this->conf = $conf;
        $this->view = $view;
        $this->store = $store;
        $this->password = $password;
        $this->mailer = $mailer;
        $this->loginManager = $loginManager;
        $this->logger = $logger;
    }

    public function __invoke(Request $request): Response
    {
        if (!$this->conf["allowed_password_forgotten"] || $request->username()) {
            return Response::create($this->view->message("fail", "error_unauthorized"));
        }
        switch ($request->post("register_action") ?? $request->get("register_action")) {
            default:
                return $this->showForm($request);
            case "forgot_password":
                return $this->passwordForgotten($request);
            case "reset_password":
                return $this->resetPassword($request);
            case "change_password":
                return $this->changePassword($request);
        }
    }

    private function showForm(Request $request): Response
    {
        return Response::create($this->renderForm($request->url(), ""));
    }

    private function passwordForgotten(Request $request): Response
    {
        $post = [
            "email" => $request->post("email") ?? "",
        ];
        if (($errors = Util::validateEmail($post["email"]))) {
            return Response::create($this->renderForm($request->url(), $post["email"], $errors));
        }
        $users = Users::retrieve($this->store);
        if (!($user = $users->userByEmail($post["email"]))) {
            return Response::redirect($request->url()->without("function")->without("register_action")->absolute());
        }
        $this->sendNotification($user, $request);
        return Response::redirect($request->url()->without("function")->without("register_action")->absolute());
    }

    private function sendNotification(User $user, Request $request): bool
    {
        $mac = Util::hmac($user->getUsername() .  $request->time(), $user->secret());
        $url = $request->url()->with("register_action", "reset_password")
            ->with("register_username", $user->getUsername())
            ->with("register_time", (string) $request->time())
            ->with("register_mac", $mac);
        $html = $this->view->render("mail_reset", [
            "fullname" => $user->getName(),
            "username" => $user->getUsername(),
            "email" => $user->getEmail(),
            "url" => $url->absolute(),
        ]);
        return $this->mailer->sendMail(
            $user->getEmail(),
            $this->view->plain("email_subject", $request->header("HOST") ?? ""),
            html_entity_decode(strip_tags($html), ENT_COMPAT | ENT_SUBSTITUTE, "UTF-8"),
            $this->conf["mail_address"]
        );
    }

    private function resetPassword(Request $request): Response
    {
        $params = [
            "username" => $request->get("register_username") ?? "",
            "time" => $request->get("register_time") ?? "",
            "mac" => $request->get("register_mac") ?? "",
        ];
        $users = Users::retrieve($this->store);
        if (!($user = $users->user($params["username"]))) {
            return Response::create($this->view->message("fail", "error_user_does_not_exist", $params["username"]));
        }
        if (!$this->verifyMac($user, $params)) {
            return Response::create($this->view->message("fail", 'error_code_invalid'));
        }
        if ($this->isExpired((int) $params["time"], $request)) {
            return Response::create($this->view->message("fail", "error_expired"));
        }
        return Response::create(
            $this->renderResetPasswordForm($request, new Passwords("", ""))
        );
    }

    private function changePassword(Request $request): Response
    {
        $params = [
            "username" => $request->get("register_username") ?? "",
            "time" => $request->get("register_time") ?? "",
            "mac" => $request->get("register_mac") ?? "",
        ];
        $users = Users::update($this->store);
        if (!($user = $users->user($params["username"]))) {
            $this->store->rollback();
            return Response::create($this->view->message("fail", "error_user_does_not_exist", $params["username"]));
        }
        if (!$this->verifyMac($user, $params)) {
            $this->store->rollback();
            return Response::create($this->view->message("fail", 'error_code_invalid'));
        }
        if ($this->isExpired((int) $params["time"], $request)) {
            $this->store->rollback();
            return Response::create($this->view->message("fail", "error_expired"));
        }
        $passwords = new Passwords(
            $request->post("password1") ?? "",
            $request->post("password2") ?? ""
        );
        if (($errors = Util::validatePasswords($passwords))) {
            $this->store->rollback();
            return Response::create($this->renderResetPasswordForm($request, $passwords, $errors));
        }
        $user->setPassword($this->password->hash($passwords->password()));
        if (!$this->store->commit()) {
            return Response::create($this->view->message("fail", 'error_cannot_write_csv'));
        }
        $this->loginManager->login($user);
        $this->logger->logInfo("login", $this->view->plain("log_resetlogin", $user->getUsername()));
        return Response::redirect($request->url()->page($request->selected())->absolute());
    }

    /** @param array{username:string,time:string,mac:string} $params */
    private function verifyMac(User $user, array $params): bool
    {
        return hash_equals(Util::hmac($params["username"] . $params["time"], $user->secret()), $params["mac"]);
    }

    private function isExpired(int $time, Request $request): bool
    {
        return $request->time() > $time + self::TTL;
    }

    /** @param list<array{string}> $errors */
    private function renderForm(Url $url, string $email, array $errors = []): string
    {
        return $this->view->render("forgotten_form", [
            "errors" => $errors,
            'email' => $email,
            "cancel" => $url->without("function")->relative(),
        ]);
    }

    /** @param list<array{string}> $errors */
    private function renderResetPasswordForm(Request $request, Passwords $passwords, array $errors = []): string
    {
        return $this->view->render("reset_password", [
            "errors" => $errors,
            "password1" => $passwords->password(),
            "password2" => $passwords->confirmation(),
            "cancel" => $request->url()->page($request->selected())->relative(),
        ]);
    }
}
