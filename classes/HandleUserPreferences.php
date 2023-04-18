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
use Register\Value\Passwords;
use Register\Value\Response;
use Register\Value\Url;
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
        if (!$this->conf["allowed_settings"] || !$request->username()) {
            return Response::create($this->view->error("error_unauthorized"));
        }
        switch ($request->registerAction()) {
            default:
                return $this->showSettingsForm($request);
            case "change_prefs":
                return $this->saveUser($request);
            case "password":
                return $this->showPasswordForm($request);
            case "change_password":
                return $this->changePassword($request);
            case "delete":
                return $this->showDeleteForm($request);
            case "unregister":
                return $this->unregisterUser($request);
        }
    }

    private function showSettingsForm(Request $request): Response
    {
        if (!($user = $this->userRepository->findByUsername($request->username()))) {
            return Response::create($this->view->error("error_user_does_not_exist", $request->username()));
        }
        if ($user->isLocked()) {
            return Response::create($this->view->error("error_user_locked", $user->getUsername()));
        }
        return Response::create($this->renderSettingsForm($request->url(), $user));
    }

    private function saveUser(Request $request): Response
    {
        if (!$this->csrfProtector->check()) {
            return Response::create($this->view->error("error_unauthorized"));
        }
        if (!($user = $this->userRepository->findByUsername($request->username()))) {
            return Response::create($this->view->error("error_user_does_not_exist", $request->username()));
        }
        if ($user->isLocked()) {
            return Response::create($this->view->error("error_user_locked", $user->getUsername()));
        }
        $post = $request->changePrefsPost();
        $changedUser = $user->withName($post["name"])->withEmail($post["email"]);
        if (!$this->password->verify($post["oldpassword"], $user->getPassword())) {
            return Response::create(
                $this->renderSettingsForm($request->url(), $changedUser, [["error_old_password_wrong"]])
            );
        }
        if (($errors = Util::validateUser($changedUser, $changedUser->getPassword()))) {
            return Response::create($this->renderSettingsForm($request->url(), $changedUser, $errors));
        }
        if (!$this->userRepository->save($changedUser)) {
            return Response::create(
                $this->renderSettingsForm($request->url(), $changedUser, [["error_cannot_write_csv"]])
            );
        }
        $this->sendNotification($changedUser, $user->getEmail(), $request);
        return Response::redirect($request->url()->without("function")->without("register_action")->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderSettingsForm(Url $url, User $user, array $errors = []): string
    {
        return $this->view->render("userprefs_form", [
            "token" => $this->csrfProtector->token(),
            "errors" => $errors,
            "name" => $user->getName(),
            "email" => $user->getEmail(),
            "cancel" => $url->without("function")->without("register_action")->relative(),
        ]);
    }

    private function showPasswordForm(Request $request): Response
    {
        if (!($user = $this->userRepository->findByUsername($request->username()))) {
            return Response::create($this->view->error("error_user_does_not_exist", $request->username()));
        }
        if ($user->isLocked()) {
            return Response::create($this->view->error("error_user_locked", $user->getUsername()));
        }
        return Response::create($this->renderPasswordForm($request->url(), new Passwords("", "")));
    }

    private function changePassword(Request $request): Response
    {
        if (!$this->csrfProtector->check()) {
            return Response::create($this->view->error("error_unauthorized"));
        }
        if (!($user = $this->userRepository->findByUsername($request->username()))) {
            return Response::create($this->view->error("error_user_does_not_exist", $request->username()));
        }
        if ($user->isLocked()) {
            return Response::create($this->view->error("error_user_locked", $user->getUsername()));
        }
        $password = $request->postedPassword();
        $passwords = $request->postedPasswords();
        if (!$this->password->verify($password, $user->getPassword())) {
            return Response::create(
                $this->renderPasswordForm($request->url(), $passwords, [["error_old_password_wrong"]])
            );
        }
        $changedUser = $user->withPassword($passwords->password());
        if (($errors = Util::validateUser($changedUser, $passwords->confirmation()))) {
            return Response::create($this->renderPasswordForm($request->url(), $passwords, $errors));
        }
        $changedUser = $changedUser->withPassword($this->password->hash($changedUser->getPassword()));
        if (!$this->userRepository->save($changedUser)) {
            return Response::create(
                $this->renderPasswordForm($request->url(), $passwords, [["error_cannot_write_csv"]])
            );
        }
        $this->sendNotification($changedUser, $user->getEmail(), $request);
        return Response::redirect($request->url()->without("function")->without("register_action")->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderPasswordForm(Url $url, Passwords $passwords, array $errors = []): string
    {
        return $this->view->render("change_password", [
            "action" => $url->with("register_action", "change_password")->relative(),
            "token" => $this->csrfProtector->token(),
            "errors" => $errors,
            "password1" => $passwords->password(),
            "password2" => $passwords->confirmation(),
            "cancel" => $url->without("function")->without("register_action")->relative(),
        ]);
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

    private function showDeleteForm(Request $request): Response
    {
        if (!($user = $this->userRepository->findByUsername($request->username()))) {
            return Response::create($this->view->error("error_user_does_not_exist", $request->username()));
        }
        if ($user->isLocked()) {
            return Response::create($this->view->error("error_user_locked", $user->getUsername()));
        }
        return Response::create($this->renderDeleteForm($request->url()));
    }

    private function unregisterUser(Request $request): Response
    {
        if (!$this->csrfProtector->check()) {
            return Response::create($this->view->error("error_unauthorized"));
        }
        if (!($user = $this->userRepository->findByUsername($request->username()))) {
            return Response::create($this->view->error("error_user_does_not_exist", $request->username()));
        }
        if ($user->isLocked()) {
            return Response::create($this->view->error("error_user_locked", $user->getUsername()));
        }
        $password = $request->postedPassword();
        if (!$this->password->verify($password, $user->getPassword())) {
            return Response::create($this->renderDeleteForm($request->url(), [["error_old_password_wrong"]]));
        }
        if (!$this->userRepository->delete($user)) {
            return Response::create($this->renderDeleteForm($request->url(), [["error_cannot_write_csv"]]));
        }
        $this->logger->logInfo("logout", $this->view->plain("log_unregister", $user->getUsername()));
        return Response::redirect($request->url()->without("function")->without("register_action")->absolute());
    }

    /** @param list<array{string}> $errors */
    private function renderDeleteForm(Url $url, array $errors = []): string
    {
        return $this->view->render("delete_account", [
            "action" => $url->with("register_action", "unregister")->relative(),
            "token" => $this->csrfProtector->token(),
            "errors" => $errors,
            "cancel" => $url->without("function")->without("register_action")->relative(),
        ]);
    }
}
