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
use Register\Infra\Request;
use Register\Infra\Response;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Logic\Util;

class HandlePasswordForgotten
{
    private const TTL = 3600;

    /** @var array<string,string> */
    private $conf;

    /** @var View */
    private $view;

    /** @var UserRepository */
    private $userRepository;

    /** @var Mailer */
    private $mailer;

    /** @param array<string,string> $conf */
    public function __construct(
        array $conf,
        View $view,
        UserRepository $userRepository,
        Mailer $mailer
    ) {
        $this->conf = $conf;
        $this->view = $view;
        $this->userRepository = $userRepository;
        $this->mailer = $mailer;
    }

    public function __invoke(Request $request): Response
    {
        if ($request->username()) {
            return (new Response)->redirect(CMSIMPLE_URL);
        }
        if (isset($_POST['action']) && $_POST['action'] === 'forgotten_password') {
            return $this->passwordForgotten($request);
        } elseif (isset($_GET['action']) && $_GET['action'] === 'registerResetPassword') {
            return $this->resetPassword($request);
        } elseif (isset($_GET['action']) && $_GET['action'] === 'register_change_password') {
            return $this->changePassword($request);
        } else {
            return $this->showForm($request);
        }
    }

    private function showForm(Request $request): Response
    {
        $response = new Response;
        $email = $_POST['email'] ?? '';
        $response->body($this->view->render('forgotten_form', [
            'actionUrl' => $request->url()->relative(),
            'email' => $email,
        ]));
        return $response;
    }

    private function passwordForgotten(Request $request): Response
    {
        $response = new Response;
        $email = $_POST['email'] ?? '';

        if ($email == '') {
            $response->body($this->view->message("fail", 'err_email')
                . $this->view->render('forgotten_form', [
                    'actionUrl' => $request->url()->relative(),
                    'email' => $email,
                ]));
            return $response;
        }
        if (!preg_match("/^[^\s()<>@,;:\"\/\[\]?=]+@\w[\w-]*(\.\w[\w-]*)*\.[a-z]{2,}$/i", $email)) {
            $response->body($this->view->message("fail", 'err_email_invalid')
                . $this->view->render('forgotten_form', [
                    'actionUrl' => $request->url()->relative(),
                    'email' => $email,
                ]));
            return $response;
        }

        $user = $this->userRepository->findByEmail($email);
        if ($user) {
            $mac = Util::hmac($user->getUsername() .  $request->time(), $user->secret());
            $url = $request->url()->withParams([
                "action" => "registerResetPassword",
                "username" => $user->getUsername(),
                "time" => (string) $request->time(),
                "mac" => $mac,
            ]);
            $this->mailer->notifyPasswordForgotten(
                $user,
                $this->conf['senderemail'],
                $url->absolute(),
                $_SERVER["SERVER_NAME"]
            );
        }
        $response->body($this->view->message('success', 'remindersent_reset'));
        return $response;
    }

    private function resetPassword(Request $request): Response
    {
        $response = new Response;
        $username = $_GET["username"] ?? "";
        $time = $_GET["time"] ?? 0;
        $mac = $_GET["mac"] ?? "";

        $user = $this->userRepository->findByUsername($username);
        if (!$user || !hash_equals(Util::hmac($username . $time, $user->secret()), $mac)) {
            $response->body($this->view->message("fail", 'err_status_invalid'));
            return $response;
        }
        if ($request->time() > $time + self::TTL) {
            $response->body($this->view->message("fail", "forgotten_expired"));
            return $response;
        }
        $url = $request->url()->withParams([
            "action" => "register_change_password",
            "username" => $username,
            "time" => $time,
            "mac" => $mac,
        ]);
        $username = urlencode($username);
        $time = urlencode($time);
        $mac = urlencode($mac);
        $response->body($this->view->render("change_password", [
            "url" => $url->relative(),
        ]));
        return $response;
    }

    private function changePassword(Request $request): Response
    {
        $response = new Response;
        $username = $_GET["username"] ?? "";
        $time = $_GET["time"] ?? 0;
        $mac = $_GET["mac"] ?? "";

        $user = $this->userRepository->findByUsername($username);
        if (!$user || !hash_equals(Util::hmac($username . $time, $user->secret()), $mac)) {
            $response->body($this->view->message("fail", 'err_status_invalid'));
            return $response;
        }
        if ($request->time() > $time + self::TTL) {
            $response->body($this->view->message("fail", "forgotten_expired"));
            return $response;
        }

        if (!isset($_POST["password1"], $_POST["password2"]) || $_POST["password1"] !== $_POST["password2"]) {
            $url = $request->url()->withParams([
                "action" => "register_change_password",
                "username" => $username,
                "time" => $time,
                "nonce" => $mac,
            ]);
            $response->body($this->view->message("fail", 'err_password2')
                . $this->view->render("change_password", [
                    "url" => $url->relative(),
                ]));
            return $response;
        }

        $password = $_POST["password1"];
        $user = $user->withPassword($password);
        if (!$this->userRepository->update($user)) {
            $response->body($this->view->message("fail", 'err_cannot_write_csv'));
            return $response;
        }

        $this->mailer->notifyPasswordReset($user, $this->conf['senderemail'], $_SERVER["SERVER_NAME"]);
        $response->body($this->view->message('success', 'remindersent'));
        return $response;
    }
}
