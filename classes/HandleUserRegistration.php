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
use Register\Infra\Random;
use Register\Infra\Request;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Logic\Util;
use Register\Logic\Validator;
use Register\Value\Response;
use Register\Value\User;

class HandleUserRegistration
{
    /** @var array<string,string> */
    private $conf;

    /** @var array<string,string> */
    private $text;

    /** @var Random */
    private $random;

    /** @var View */
    private $view;

    /** @var UserRepository */
    private $userRepository;

    /** @var Mailer */
    private $mailer;

    /** @var Password */
    private $password;

    /**
     * @param array<string,string> $conf
     * @param array<string,string> $text
     */
    public function __construct(
        array $conf,
        array $text,
        Random $random,
        View $view,
        UserRepository $userRepository,
        Mailer $mailer,
        Password $password
    ) {
        $this->conf = $conf;
        $this->text = $text;
        $this->random = $random;
        $this->view = $view;
        $this->userRepository = $userRepository;
        $this->mailer = $mailer;
        $this->password = $password;
    }

    public function __invoke(Request $request): Response
    {
        if ($request->username()) {
            return Response::redirect(CMSIMPLE_URL);
        }
        switch ($request->registerAction()) {
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
        return Response::create($this->view->render('registerform', [
            'actionUrl' => $request->url()->relative(),
            "errors" => [],
            'name' => "",
            'username' => "",
            'password1' => "",
            'password2' => "",
            'email' => "",
        ]));
    }

    private function registerUser(Request $request): Response
    {
        $post = $request->registerUserPost();
        $errors = (new Validator)->validateUser(
            $post["name"],
            $post["username"],
            $post["password1"],
            $post["password2"],
            $post["email"]
        );
        if ($errors) {
            return Response::create($this->view->render('registerform', [
                'actionUrl' => $request->url()->relative(),
                "errors" => $errors,
                'name' => $post["name"],
                'username' => $post["username"],
                'password1' => $post["password1"],
                'password2' => $post["password2"],
                'email' => $post["email"],
            ]));
        }

        if ($this->userRepository->findByUsername($post["username"])) {
            return Response::create($this->view->message("fail", 'err_username_exists'));
        }
        $user = $this->userRepository->findByEmail($post["email"]);

        // generate a nonce for the user activation
        $nonce = Util::base64url($this->random->bytes(18));
        if (!$user) {
            $newUser = new User(
                $post["username"],
                $this->password->hash($post["password1"]),
                array($this->conf['group_default']),
                $post["name"],
                $post["email"],
                $nonce,
                base64_encode($this->random->bytes(15))
            );

            if (!$this->userRepository->add($newUser)) {
                return Response::create($this->view->message("fail", 'err_cannot_write_csv'));
            }
        }

        // prepare email content for registration activation
        if (!$user) {
            $key = "emailtext2";
            $url = $request->url()->withParams([
                "register_action" => "activate",
                "username" => $post["username"],
                "nonce" => $nonce,
            ]);
        } else {
            $key = "emailtext4";
            $url = $request->url()->withPage($this->text['forgot_password']);
        }
        $this->mailer->notifyActivation(
            new User($post["username"], "", [], $post["name"], $post["email"], "", ""),
            $this->conf['senderemail'],
            $url->absolute(),
            $key,
            $request->serverName(),
            $request->remoteAddress()
        );
        return Response::create($this->view->message('success', 'registered'));
    }

    private function activateUser(Request $request): Response
    {
        $params = $request->activationParams();
        if ($params["username"] === "" || $params["nonce"] === "") {
            return Response::create();
        }
        $user = $this->userRepository->findByUsername($params["username"]);
        if ($user === null) {
            return Response::create($this->view->message("fail", 'err_username_notfound', $params["username"]));
        }
        if ($user->getStatus() === "") {
            return Response::create($this->view->message("fail", 'err_status_empty'));
        }
        if (!hash_equals($user->getStatus(), $params["nonce"])) {
            return Response::create($this->view->message("fail", 'err_status_invalid'));
        }
        $user = $user->activate()->withAccessgroups([$this->conf['group_activated']]);
        $this->userRepository->update($user);
        return Response::create($this->view->message('success', 'activated'));
    }
}
