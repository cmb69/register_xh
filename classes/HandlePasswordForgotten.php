<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\Mailer;
use Register\Infra\Password;
use Register\Infra\Request;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Logic\Util;
use Register\Logic\Validator;
use Register\Value\Response;
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

    /** @param array<string,string> $conf */
    public function __construct(
        array $conf,
        View $view,
        UserRepository $userRepository,
        Password $password,
        Mailer $mailer
    ) {
        $this->conf = $conf;
        $this->view = $view;
        $this->userRepository = $userRepository;
        $this->password = $password;
        $this->mailer = $mailer;
    }

    public function __invoke(Request $request): Response
    {
        if (!$this->conf["password_forgotten"] || $request->username() || $request->editMode()) {
            return Response::create();
        }
        switch ($request->registerAction()) {
            default:
                return $this->showForm();
            case "forgot_password":
                return $this->passwordForgotten($request);
            case "reset_password":
                return $this->resetPassword($request);
            case "change_password":
                return $this->changePassword($request);
        }
    }

    private function showForm(): Response
    {
        return $this->respondWith($this->renderForm(""));
    }

    private function passwordForgotten(Request $request): Response
    {
        $post = $request->forgotPasswordPost();
        if (($errors = (new Validator)->validateEmail($post["email"]))) {
            return $this->respondWith($this->renderForm($post["email"], $errors));
        }
        if (!($user = $this->userRepository->findByEmail($post["email"]))) {
            return $this->respondWith($this->view->message('success', 'remindersent_reset'));
        }
        $this->sendNotification($user, $request);
        return $this->respondWith($this->view->message('success', 'remindersent_reset'));
    }

    private function sendNotification(User $user, Request $request): bool
    {
        $mac = Util::hmac($user->getUsername() .  $request->time(), $user->secret());
        $url = $request->url()->withParams([
            "register_action" => "reset_password",
            "username" => $user->getUsername(),
            "time" => (string) $request->time(),
            "mac" => $mac,
        ]);
        return $this->mailer->notifyPasswordForgotten(
            $user,
            $this->conf['senderemail'],
            $url->absolute(),
            $request->serverName()
        );
    }

    private function resetPassword(Request $request): Response
    {
        $params = $request->resetPasswordParams();
        if (!($user = $this->userRepository->findByUsername($params["username"]))) {
            return $this->respondWith($this->view->error("err_user_does_not_exist", $params["username"]));
        }
        if (!$this->verifyMac($user, $params)) {
            return $this->respondWith($this->view->message("fail", 'err_status_invalid'));
        }
        if ($this->isExpired((int) $params["time"], $request)) {
            return $this->respondWith($this->view->message("fail", "forgotten_expired"));
        }
        return $this->respondWith($this->renderChangePasswordForm(["password1" => "", "password2" => ""]));
    }

    private function changePassword(Request $request): Response
    {
        $params = $request->resetPasswordParams();
        if (!($user = $this->userRepository->findByUsername($params["username"]))) {
            return $this->respondWith($this->view->error("err_user_does_not_exist", $params["username"]));
        }
        if (!$this->verifyMac($user, $params)) {
            return $this->respondWith($this->view->message("fail", 'err_status_invalid'));
        }
        if ($this->isExpired((int) $params["time"], $request)) {
            return $this->respondWith($this->view->message("fail", "forgotten_expired"));
        }
        $post = $request->changePasswordPost();
        if (($errors = (new Validator)->validatePassword($post["password1"], $post["password2"]))) {
            return $this->respondWith($this->renderChangePasswordForm($post, $errors));
        }
        $user = $user->withPassword($this->password->hash($post["password1"]));
        if (!$this->userRepository->save($user)) {
            return $this->respondWith($this->view->message("fail", 'err_cannot_write_csv'));
        }
        $this->mailer->notifyPasswordReset($user, $this->conf['senderemail'], $request->serverName());
        return $this->respondWith($this->view->message('success', 'remindersent'));
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
    private function renderForm(string $email, array $errors = []): string
    {
        return $this->view->render("forgotten_form", [
            "errors" => $errors,
            'email' => $email,
        ]);
    }

    /**
     * @param array{password1:string,password2:string} $passwords
     * @param list<array{string}> $errors
     */
    private function renderChangePasswordForm(array $passwords, array $errors = []): string
    {
        return $this->view->render("change_password", [
            "errors" => $errors,
            "password1" => $passwords["password1"],
            "password2" => $passwords["password2"],
        ]);
    }

    private function respondWith(string $output): Response
    {
        return Response::create("<h1>" . $this->view->text("forgot_password") . "</h1>\n"
            . "<p>" . $this->view->text("reminderexplanation") . "</p>\n"
            . $output)->withTitle($this->view->text("forgot_password"));
    }
}
