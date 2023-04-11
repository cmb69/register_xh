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
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Logic\Util;
use Register\Logic\Validator;
use Register\Value\Html;
use Register\Value\Response;

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
        if (!$request->username()) {
            return Response::create($this->view->message("fail", "access_error_text"));
        }
        switch ($request->registerAction()) {
            default:
                return Response::create($this->showForm($request));
            case "change_prefs":
                return Response::create($this->saveUser($request));
            case "unregister":
                return Response::create($this->unregisterUser($request));
        }
    }

    private function showForm(Request $request): string
    {
        $user = $this->userRepository->findByUsername($request->username());
        assert($user !== null);
        if ($user->isLocked()) {
            return $this->view->message("fail", "user_locked", $user->getUsername());
        }
        return $this->renderForm($request->url(), [], $user->getName(), $user->getEmail());
    }

    private function saveUser(Request $request): string
    {
        if (!$this->csrfProtector->check()) {
            return $this->view->error("err_unauthorized");
        }
        $user = $this->userRepository->findByUsername($request->username());
        assert($user !== null); // TODO 403 to be safe
        if ($user->isLocked()) {
            return $this->view->message("fail", "user_locked", $user->getUsername());
        }
        $post = $request->changePrefsPost();
        if (!$this->password->verify($post["oldpassword"], $user->getPassword())) {
            return $this->renderForm($request->url(), [["err_old_password_wrong"]], $post["name"], $post["email"]);
        }
        $post = Util::changePrefsRecordWithDefaults($post, $user);
        $errors = (new Validator)->validateUser(
            $post["name"],
            $user->getUsername(),
            $post["password1"],
            $post["password2"],
            $post["email"]
        );
        if ($errors) {
            return $this->renderForm($request->url(), $errors, $post["name"], $post["email"]);
        }
        $oldemail = $user->getEmail();
        $user = $user->withPassword($this->password->hash($post["password1"]))
            ->withEmail($post["email"])
            ->withName($post["name"]);
        if (!$this->userRepository->update($user)) {
            return $this->view->message("fail", "err_cannot_write_csv");
        }
        $this->mailer->notifyUpdate(
            $user,
            $oldemail,
            $this->conf["senderemail"],
            $request->serverName(),
            $request->remoteAddress()
        );
        return $this->view->message("success", "prefsupdated");
    }

    private function unregisterUser(Request $request): string
    {
        if (!$this->csrfProtector->check()) {
            return $this->view->error("err_unauthorized");
        }
        $user = $this->userRepository->findByUsername($request->username());
        assert($user !== null); // TODO 403 to be safe
        if ($user->isLocked()) {
            return $this->view->message("fail", "user_locked", $user->getUsername());
        }
        $post = $request->unregisterPost();
        if (!$this->password->verify($post["oldpassword"], $user->getPassword())) {
            return $this->renderForm($request->url(), [["err_old_password_wrong"]], $post["name"], $post["email"]);
        }
        if (!$this->userRepository->delete($user)) {
            return $this->view->message("fail", "err_cannot_write_csv");
        }
        $this->logger->logInfo("logout", "{$user->getUsername()} deleted and logged out");
        return $this->view->message("success", "user_deleted", $user->getUsername());
    }

    /** @param list<array{string}> $errors */
    private function renderForm(Url $url, array $errors, string $name, string $email): string
    {
        return $this->view->render("userprefs_form", [
            "token" => $this->csrfProtector->token(),
            "actionUrl" => $url->relative(),
            "errors" => $errors,
            "name" => $name,
            "email" => $email,
        ]);
    }
}
