<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\Mailer;
use Register\Infra\Password;
use Register\Infra\Request;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Logic\Util;
use Register\Value\Passwords;
use Register\Value\Response;
use Register\Value\Url;
use Register\Value\User;

class HandlePasswordForgotten
{
    private const TTL = 3600;

    /** @var array<string,string> */
    private $conf;

    /** @var View */
    private $view;

    /** @var UserRepository */
    private $userRepository;

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
        UserRepository $userRepository,
        Password $password,
        Mailer $mailer,
        LoginManager $loginManager,
        Logger $logger
    ) {
        $this->conf = $conf;
        $this->view = $view;
        $this->userRepository = $userRepository;
        $this->password = $password;
        $this->mailer = $mailer;
        $this->loginManager = $loginManager;
        $this->logger = $logger;
    }

    public function __invoke(Request $request): Response
    {
        if (!$this->conf["allowed_password_forgotten"] || $request->username()) {
            return Response::create($this->view->error("error_unauthorized"));
        }
        switch ($request->registerAction()) {
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
        $post = $request->forgotPasswordPost();
        if (($errors = Util::validateEmail($post["email"]))) {
            return Response::create($this->renderForm($request->url(), $post["email"], $errors));
        }
        if (!($user = $this->userRepository->findByEmail($post["email"]))) {
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
        return $this->mailer->sendMail(
            $user->getEmail(),
            $this->view->plain("email_subject", $request->serverName()),
            $this->view->renderPlain("mail_reset", [
                "fullname" => $user->getName(),
                "username" => $user->getUsername(),
                "email" => $user->getEmail(),
                "url" => $url->absolute(),
            ]),
            $this->conf["mail_address"]
        );
    }

    private function resetPassword(Request $request): Response
    {
        $params = $request->resetPasswordParams();
        if (!($user = $this->userRepository->findByUsername($params["username"]))) {
            return Response::create($this->view->error("error_user_does_not_exist", $params["username"]));
        }
        if (!$this->verifyMac($user, $params)) {
            return Response::create($this->view->message("fail", 'error_code_invalid'));
        }
        if ($this->isExpired((int) $params["time"], $request)) {
            return Response::create($this->view->message("fail", "error_expired"));
        }
        return Response::create(
            $this->renderResetPasswordForm($request->url(), new Passwords("", ""))
        );
    }

    private function changePassword(Request $request): Response
    {
        $params = $request->resetPasswordParams();
        if (!($user = $this->userRepository->findByUsername($params["username"]))) {
            return Response::create($this->view->error("error_user_does_not_exist", $params["username"]));
        }
        if (!$this->verifyMac($user, $params)) {
            return Response::create($this->view->message("fail", 'error_code_invalid'));
        }
        if ($this->isExpired((int) $params["time"], $request)) {
            return Response::create($this->view->message("fail", "error_expired"));
        }
        $passwords = $request->postedPasswords();
        if (($errors = Util::validatePasswords($passwords))) {
            return Response::create($this->renderResetPasswordForm($request->url(), $passwords, $errors));
        }
        $user = $user->withPassword($this->password->hash($passwords->password()));
        if (!$this->userRepository->save($user)) {
            return Response::create($this->view->message("fail", 'error_cannot_write_csv'));
        }
        $this->loginManager->login($user);
        $this->logger->logInfo("login", $this->view->plain("log_resetlogin", $user->getUsername()));
        return Response::redirect($request->url()->withoutParams()->absolute());
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
    private function renderResetPasswordForm(Url $url, Passwords $passwords, array $errors = []): string
    {
        return $this->view->render("reset_password", [
            "errors" => $errors,
            "password1" => $passwords->password(),
            "password2" => $passwords->confirmation(),
            "cancel" => $url->withPage($url->page())->relative(),
        ]);
    }
}
