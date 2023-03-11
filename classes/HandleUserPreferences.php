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
use Register\Infra\Mailer;
use Register\Infra\Password;
use Register\Infra\Request;
use Register\Infra\Response;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Logic\Validator;
use Register\Value\Html;
use XH\CSRFProtection as CsrfProtector;

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
            return (new Response)->body($this->view->message("fail", "access_error_text"));
        }
        if (isset($_POST['action']) && $_POST['action'] === 'edit_user_prefs' && isset($_POST['submit'])) {
            return (new Response)->body($this->saveUser($request));
        }
        if (isset($_POST['action']) && $_POST['action'] === 'edit_user_prefs' && isset($_POST['delete'])) {
            return (new Response)->body($this->unregisterUser($request));
        }
        return (new Response)->body($this->showForm($request));
    }

    private function showForm(Request $request): string
    {
        $user = $this->userRepository->findByUsername($request->username());
        assert($user !== null);
        if ($user->isLocked()) {
            return $this->view->message('fail', 'user_locked', $user->getUsername());
        } else {
            $csrfTokenInput = $this->csrfProtector->tokenInput();
            $this->csrfProtector->store();
            return $this->view->render('userprefs_form', [
                'csrfTokenInput' => Html::from($csrfTokenInput),
                'actionUrl' => $request->url()->relative(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
            ]);
        }
    }

    private function saveUser(Request $request): string
    {
        $this->csrfProtector->check();

        // Get form data if available
        $oldpassword = $this->trimmedPostString("oldpassword");
        $name = $this->trimmedPostString("name");
        $password1 = $this->trimmedPostString("password1");
        $password2 = $this->trimmedPostString("password2");
        $email = $this->trimmedPostString("email");

        $user = $this->userRepository->findByUsername($request->username());
        assert($user !== null);

        // Test if user is locked
        if ($user->isLocked()) {
            return $this->view->message('fail', 'user_locked', $user->getUsername());
        }

        // check that old password got entered correctly
        if (!$this->password->verify($oldpassword, $user->getPassword())) {
            $csrfTokenInput = $this->csrfProtector->tokenInput();
            $this->csrfProtector->store();
            return $this->view->message("fail", 'err_old_password_wrong')
                . $this->view->render('userprefs_form', [
                    'csrfTokenInput' => Html::from($csrfTokenInput),
                    'actionUrl' => $request->url()->relative(),
                    'name' => $name,
                    'email' => $email,
                ]);
        }

        if ($password1 == "" && $password2 == "") {
            $password1 = $oldpassword;
            $password2 = $oldpassword;
        }
        if ($email == "") {
            $email = $user->getEmail();
        }
        if ($name == "") {
            $name = $user->getName();
        }

        $validator = new Validator();
        $errors = $validator->validateUser($name, $user->getUsername(), $password1, $password2, $email);
        if ($errors) {
            $csrfTokenInput = $this->csrfProtector->tokenInput();
            $this->csrfProtector->store();
            return $this->renderErrorMessages($errors)
                . $this->view->render('userprefs_form', [
                    'csrfTokenInput' => Html::from($csrfTokenInput),
                    'actionUrl' => $request->url()->relative(),
                    'name' => $name,
                    'email' => $email,
                ]);
        }

        $oldemail = $user->getEmail();

        // read user entry, update it and write it back to CSV file
        $user = $user->withPassword($this->password->hash($password1))
            ->withEmail($email)
            ->withName($name);

        if (!$this->userRepository->update($user)) {
            return $this->view->message("fail", 'err_cannot_write_csv');
        }

        $this->mailer->notifyUpdate(
            $user,
            $oldemail,
            $this->conf['senderemail'],
            $_SERVER["SERVER_NAME"],
            $_SERVER["REMOTE_ADDR"]
        );
        return $this->view->message('success', 'prefsupdated');
    }

    private function trimmedPostString(string $param): string
    {
        return (isset($_POST[$param]) && is_string($_POST[$param])) ? trim($_POST[$param]) : "";
    }

    /** @param list<array{string}> $errors */
    private function renderErrorMessages(array $errors): string
    {
        return implode("", array_map(function ($args) {
            return $this->view->message("fail", ...$args);
        }, $errors));
    }

    private function unregisterUser(Request $request): string
    {
        $this->csrfProtector->check();
    
        // Get form data if available
        $oldpassword = $_POST['oldpassword'] ?? '';
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';

        $user = $this->userRepository->findByUsername($request->username());
        assert($user !== null);

        // Test if user is locked
        if ($user->isLocked()) {
            return $this->view->message('fail', 'user_locked', $user->getUsername());
        }

        // Form Handling - Delete User ================================================
        if (!$this->password->verify($oldpassword, $user->getPassword())) {
            $csrfTokenInput = $this->csrfProtector->tokenInput();
            $this->csrfProtector->store();
            return $this->view->message("fail", 'err_old_password_wrong')
                . $this->view->render('userprefs_form', [
                    'csrfTokenInput' => Html::from($csrfTokenInput),
                    'actionUrl' => $request->url()->relative(),
                    'name' => $name,
                    'email' => $email,
                ]);
        }

        if (!$this->userRepository->delete($user)) {
            return $this->view->message("fail", 'err_cannot_write_csv');
        }

        $this->logger->logInfo('logout', "{$user->getUsername()} deleted and logged out");
        return $this->view->message('success', 'user_deleted', $user->getUsername());
    }
}
