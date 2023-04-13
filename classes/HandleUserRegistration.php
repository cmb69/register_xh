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
use Register\Infra\Url;
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
                return $this->showForm();
            case "register":
                return $this->registerUser($request);
            case "activate":
                return $this->activateUser($request);
        }
    }

    private function showForm(): Response
    {
        $user = new User("", "", [], "", "", "", "");
        return Response::create($this->renderForm($user, ""));
    }

    private function registerUser(Request $request): Response
    {
        $post = $request->registerUserPost();
        $user = $this->userFromPost($post);
        if (($errors = Util::validateUser($user, $post["password2"]))) {
            return Response::create($this->renderForm($user, $post["password2"], $errors));
        }
        if ($this->userRepository->findByUsername($post["username"])) {
            return Response::create($this->renderForm($user, $post["password2"], [["err_username_exists"]]));
        }
        if ($this->userRepository->hasDuplicateEmail($user)) {
            $this->sendDuplicateEmailNotification($user, $request);
            return Response::redirect($request->url()->withPage("")->absolute());
        }
        $newUser = $this->registeredUser($post);
        if (!$this->userRepository->save($newUser)) {
            return Response::create($this->renderForm($user, $post["password2"], [["err_cannot_write_csv"]]));
        }
        $this->sendSuccessNotification($newUser, $request);
        return Response::redirect($request->url()->withPage("")->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderForm(User $user, string $password2, array $errors = []): string
    {
        return $this->view->render("registerform", [
            "errors" => $errors,
            "name" => $user->getName(),
            "username" => $user->getUsername(),
            "password1" => $user->getPassword(),
            "password2" => $password2,
            "email" => $user->getEmail(),
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
    private function registeredUser(array $post): User
    {
        return new User(
            $post["username"],
            $this->password->hash($post["password1"]),
            array($this->conf["group_default"]),
            $post["name"],
            $post["email"],
            Util::base64url($this->random->bytes(15)),
            base64_encode($this->random->bytes(15))
        );
    }

    private function sendDuplicateEmailNotification(User $user, Request $request): bool
    {
        $url = $request->url()->withPage($this->text["forgot_password"]);
        return $this->sendNotification($user, "emailtext4", $url, $request);
    }

    private function sendSuccessNotification(User $user, Request $request): bool
    {
        $url = $request->url()->withParams([
            "register_action" => "activate",
            "username" => $user->getUsername(),
            "nonce" => $user->getStatus(),
        ]);
        return $this->sendNotification($user, "emailtext2", $url, $request);
    }

    private function sendNotification(User $user, string $key, Url $url, Request $request): bool
    {
        return $this->mailer->notifyActivation(
            $user,
            $this->conf["senderemail"],
            $url->absolute(),
            $key,
            $request->serverName(),
            $request->remoteAddress()
        );
    }

    private function activateUser(Request $request): Response
    {
        $params = $request->activationParams();
        if (!$params["nonce"]) {
            return Response::create($this->view->error("err_status_empty"));
        }
        if (!($user = $this->userRepository->findByUsername($params["username"]))) {
            return Response::create($this->view->error("err_username_notfound", $params["username"]));
        }
        if (!hash_equals($user->getStatus(), $params["nonce"])) {
            return Response::create($this->view->error("err_status_invalid"));
        }
        $user = $user->activate()->withAccessgroups([$this->conf["group_activated"]]);
        if (!$this->userRepository->save($user)) {
            return Response::create($this->view->error("err_cannot_write_csv"));
        }
        return Response::create($this->view->message("success", "activated"));
    }
}
