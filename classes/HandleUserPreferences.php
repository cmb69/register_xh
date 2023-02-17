<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\MailService;
use XH\CSRFProtection as CsrfProtector;

use Register\Value\HtmlString;
use Register\Logic\Validator;
use Register\Infra\CurrentUser;
use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\Request;
use Register\Infra\Response;
use Register\Infra\Session;
use Register\Infra\UserRepository;
use Register\Infra\View;

class HandleUserPreferences
{
    /** @var CurrentUser */
    private $currentUser;

    /** @var array<string,string> */
    private $conf;

    /** @var array<string,string> */
    private $text;

    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var UserRepository */
    private $userRepository;

    /** @var View */
    private $view;

    /** @var MailService */
    private $mailService;

    /** @var LoginManager */
    private $loginManager;

    /** @var Logger */
    private $logger;

    /**
     * @param array<string,string> $conf
     * @param array<string,string> $text
     */
    public function __construct(
        CurrentUser $currentUser,
        array $conf,
        array $text,
        Session $session,
        CsrfProtector $csrfProtector,
        UserRepository $userRepository,
        View $view,
        MailService $mailService,
        LoginManager $loginManager,
        Logger $logger
    ) {
        $this->currentUser = $currentUser;
        $this->conf = $conf;
        $this->text = $text;
        $session->start();
        $this->csrfProtector = $csrfProtector;
        $this->userRepository = $userRepository;
        $this->view = $view;
        $this->mailService = $mailService;
        $this->loginManager = $loginManager;
        $this->logger = $logger;
    }

    public function __invoke(Request $request): Response
    {
        if (!$this->currentUser->get()) {
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
        $username = $_SESSION['username'] ?? '';

        $user = $this->userRepository->findByUsername($username);
        if ($user === null) {
            return $this->view->message('fail', 'err_username_does_not_exist', $username);
        } elseif ($user->isLocked()) {
            return $this->view->message('fail', 'user_locked', $username);
        } else {
            $csrfTokenInput = $this->csrfProtector->tokenInput();
            $this->csrfProtector->store();
            return $this->view->render('userprefs-form', [
                'csrfTokenInput' => new HtmlString($csrfTokenInput),
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

        // set user name from session
        $username = $_SESSION['username'];

        $user = $this->userRepository->findByUsername($username);
        if ($user === null) {
            return $this->view->message('fail', 'err_username_does_not_exist', $username);
        }

        // Test if user is locked
        if ($user->isLocked()) {
            return $this->view->message('fail', 'user_locked', $username);
        }

        // check that old password got entered correctly
        if (!password_verify($oldpassword, $user->getPassword())) {
            $csrfTokenInput = $this->csrfProtector->tokenInput();
            $this->csrfProtector->store();
            return $this->view->message("fail", 'err_old_password_wrong')
                . $this->view->render('userprefs-form', [
                    'csrfTokenInput' => new HtmlString($csrfTokenInput),
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
        $errors = $validator->validateUser($name, $username, $password1, $password2, $email);
        if ($errors) {
            $csrfTokenInput = $this->csrfProtector->tokenInput();
            $this->csrfProtector->store();
            return $this->renderErrorMessages($errors)
                . $this->view->render('userprefs-form', [
                    'csrfTokenInput' => new HtmlString($csrfTokenInput),
                    'actionUrl' => $request->url()->relative(),
                    'name' => $name,
                    'email' => $email,
                ]);
        }

        $oldemail = $user->getEmail();

        // read user entry, update it and write it back to CSV file
        $user = $user->withPassword((string) password_hash($password1, PASSWORD_DEFAULT))
            ->withEmail($email)
            ->withName($name);

        if (!$this->userRepository->update($user)) {
            return $this->view->message("fail", 'err_cannot_write_csv');
        }

        // prepare email for user information about updates
        $content = $this->text['emailprefsupdated'] . "\n\n" .
            ' ' . $this->text['name'] . ': '.$name."\n" .
            ' ' . $this->text['username'] . ': '.$username."\n" .
            //' ' . $this->text['password'] . ': '.$password1."\n" .
            ' ' . $this->text['email'] . ': '.$email."\n" .
            ' ' . $this->text['fromip'] . ': '. $_SERVER['REMOTE_ADDR'] ."\n";

        // send update email
        $this->mailService->sendMail(
            $email,
            $this->text['prefsemailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
            $content,
            array(
                'From: ' . $this->conf['senderemail'],
                'Cc: '  . $oldemail . ', ' . $this->conf['senderemail']
            )
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

        // set user name from session
        $username = $_SESSION['username'] ?? "";

        $entry = $this->userRepository->findByUsername($username);
        if ($entry === null) {
            return $this->view->message('fail', 'err_username_does_not_exist', $username);
        }

        // Test if user is locked
        if ($entry->isLocked()) {
            return $this->view->message('fail', 'user_locked', $username);
        }

        // Form Handling - Delete User ================================================
        if (!password_verify($oldpassword, $entry->getPassword())) {
            $csrfTokenInput = $this->csrfProtector->tokenInput();
            $this->csrfProtector->store();
            return $this->view->message("fail", 'err_old_password_wrong')
                . $this->view->render('userprefs-form', [
                    'csrfTokenInput' => new HtmlString($csrfTokenInput),
                    'actionUrl' => $request->url()->relative(),
                    'name' => $name,
                    'email' => $email,
                ]);
        }

        if (!$this->userRepository->delete($entry)) {
            return $this->view->message("fail", 'err_cannot_write_csv');
        }

        $username = $_SESSION['username'] ?? '';
        $this->loginManager->logout();
        $this->logger->logInfo('logout', "$username deleted and logged out");
        return $this->view->message('success', 'user_deleted', $username);
    }
}
