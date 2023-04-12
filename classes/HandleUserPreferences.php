<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\CsrfProtector;
use Register\Infra\Logger;
use Register\Infra\Mailer;
use Register\Infra\Password;
use Register\Infra\Request;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Logic\Util;
use Register\Value\Response;
use Register\Value\User;

class HandleUserPreferences
{
    /** @var array<string,string> */
    private $conf;

    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var UserRepository */
    private $userRepository;

    /** @var View */
    private $view;

    /** @var Mailer */
    private $mailer;

    /** @var Logger */
    private $logger;

    /** @var Password */
    private $password;

    /**
     * @param array<string,string> $conf
     */
    public function __construct(
        array $conf,
        CsrfProtector $csrfProtector,
        UserRepository $userRepository,
        View $view,
        Mailer $mailer,
        Logger $logger,
        Password $password
    ) {
        $this->conf = $conf;
        $this->csrfProtector = $csrfProtector;
        $this->userRepository = $userRepository;
        $this->view = $view;
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->password = $password;
    }

    public function __invoke(Request $request): Response
    {
        switch ($request->registerAction()) {
            default:
                return $this->showForm($request);
            case "change_prefs":
                return $this->saveUser($request);
            case "unregister":
                return $this->unregisterUser($request);
        }
    }

    private function showForm(Request $request): Response
    {
        if (!($user = $this->userRepository->findByUsername($request->username()))) {
            return Response::create($this->view->error("err_user_does_not_exist", $request->username()));
        }
        if ($user->isLocked()) {
            return Response::create($this->view->error("user_locked", $user->getUsername()));
        }
        return Response::create($this->renderForm($user));
    }

    private function saveUser(Request $request): Response
    {
        if (!$this->csrfProtector->check()) {
            return Response::create($this->view->error("err_unauthorized"));
        }
        if (!($user = $this->userRepository->findByUsername($request->username()))) {
            return Response::create($this->view->error("err_user_does_not_exist", $request->username()));
        }
        if ($user->isLocked()) {
            return Response::create($this->view->error("user_locked", $user->getUsername()));
        }
        $post = $request->changePrefsPost();
        $changedUser = $user->withName($post["name"])->withEmail($post["email"]);
        if (!$this->password->verify($post["oldpassword"], $user->getPassword())) {
            return Response::create($this->renderForm($changedUser, [["err_old_password_wrong"]]));
        }
        $changedUser = $changedUser->withPassword($post["password1"]);
        if (($errors = Util::validateUser($changedUser, $post["password2"]))) {
            return Response::create($this->renderForm($changedUser, $errors));
        }
        $changedUser = $changedUser->withPassword($this->password->hash($changedUser->getPassword()));
        if (!$this->userRepository->save($changedUser)) {
            return Response::create($this->renderForm($changedUser, [["err_cannot_write_csv"]]));
        }
        $this->sendNotification($changedUser, $user->getEmail(), $request);
        return Response::redirect($request->url()->absolute());
    }

    private function sendNotification(User $user, string $email, Request $request): bool
    {
        return $this->mailer->notifyUpdate(
            $user,
            $email,
            $this->conf["senderemail"],
            $request->serverName(),
            $request->remoteAddress()
        );
    }

    private function unregisterUser(Request $request): Response
    {
        if (!$this->csrfProtector->check()) {
            return Response::create($this->view->error("err_unauthorized"));
        }
        if (!($user = $this->userRepository->findByUsername($request->username()))) {
            return Response::create($this->view->error("err_user_does_not_exist", $request->username()));
        }
        if ($user->isLocked()) {
            return Response::create($this->view->error("user_locked", $user->getUsername()));
        }
        $post = $request->unregisterPost();
        if (!$this->password->verify($post["oldpassword"], $user->getPassword())) {
            return Response::create($this->renderForm($user, [["err_old_password_wrong"]]));
        }
        if (!$this->userRepository->delete($user)) {
            return Response::create($this->renderForm($user, [["err_cannot_write_csv"]]));
        }
        $this->logger->logInfo("logout", "{$user->getUsername()} deleted and logged out");
        return Response::redirect($request->url()->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderForm(User $user, array $errors = []): string
    {
        return $this->view->render("userprefs_form", [
            "token" => $this->csrfProtector->token(),
            "errors" => $errors,
            "name" => $user->getName(),
            "email" => $user->getEmail(),
        ]);
    }
}
