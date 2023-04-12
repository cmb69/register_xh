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
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Logic\Util;
use Register\Logic\Validator;
use Register\Value\Response;

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
            return Response::redirect(CMSIMPLE_URL);
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
        return Response::create($this->renderForm($request->url(), [], ""));
    }

    private function passwordForgotten(Request $request): Response
    {
        $post = $request->forgotPasswordPost();
        if ($post["email"] === "") {
            return Response::create($this->renderForm($request->url(), [["err_email"]], ""));
        }
        $errors = (new Validator)->validateEmail($post["email"]);
        if ($errors) {
            return Response::create($this->renderForm($request->url(), $errors, $post["email"]));
        }

        $user = $this->userRepository->findByEmail($post["email"]);
        if ($user) {
            $mac = Util::hmac($user->getUsername() .  $request->time(), $user->secret());
            $url = $request->url()->withParams([
                "register_action" => "reset_password",
                "username" => $user->getUsername(),
                "time" => (string) $request->time(),
                "mac" => $mac,
            ]);
            $this->mailer->notifyPasswordForgotten(
                $user,
                $this->conf['senderemail'],
                $url->absolute(),
                $request->serverName()
            );
        }
        return Response::create($this->view->message('success', 'remindersent_reset'));
    }

    private function resetPassword(Request $request): Response
    {
        $params = $request->resetPasswordParams();

        $user = $this->userRepository->findByUsername($params["username"]);
        if (!$user || !hash_equals(Util::hmac($params["username"] . $params["time"], $user->secret()), $params["mac"])) {
            return Response::create($this->view->message("fail", 'err_status_invalid'));
        }
        if ($request->time() > (int) $params["time"] + self::TTL) {
            return Response::create($this->view->message("fail", "forgotten_expired"));
        }
        return Response::create($this->renderChangePasswordForm($request->url(), $params, []));
    }

    private function changePassword(Request $request): Response
    {
        $params = $request->resetPasswordParams();

        $user = $this->userRepository->findByUsername($params["username"]);
        if (!$user || !hash_equals(Util::hmac($params["username"] . $params["time"], $user->secret()), $params["mac"])) {
            return Response::create($this->view->message("fail", 'err_status_invalid'));
        }
        if ($request->time() > (int) $params["time"] + self::TTL) {
            return Response::create($this->view->message("fail", "forgotten_expired"));
        }
        $post = $request->changePasswordPost();
        if ($post["password1"] === "" || $post["password2"] === "" || $post["password1"] !== $post["password2"]) {
            return Response::create($this->renderChangePasswordForm($request->url(), $params, [["err_password2"]]));
        }

        $password = $post["password1"];
        $user = $user->withPassword($password);
        if (!$this->userRepository->save($user)) {
            return Response::create($this->view->message("fail", 'err_cannot_write_csv'));
        }

        $this->mailer->notifyPasswordReset($user, $this->conf['senderemail'], $request->serverName());
        return Response::create($this->view->message('success', 'remindersent'));
    }

    /** @param list<array{string}> $errors */
    private function renderForm(Url $url, array $errors, string $email): string
    {
        return $this->view->render("forgotten_form", [
            'actionUrl' => $url->relative(),
            "errors" => $errors,
            'email' => $email,
        ]);
    }

    /**
     * @param array{username:string,time:string,mac:string} $params
     * @param list<array{string}> $errors
     */
    private function renderChangePasswordForm(Url $url, array $params, array $errors): string
    {
        $url = $url->withParams([
            "register_action" => "change_password",
            "username" => $params["username"],
            "time" => $params["time"],
            "mac" => $params["mac"],
        ]);
        return $this->view->render("change_password", [
            "url" => $url->relative(),
            "errors" => $errors,
        ]);
    }
}
