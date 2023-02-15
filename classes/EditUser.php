<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use XH\CSRFProtection as CsrfProtector;

use Register\Value\HtmlString;
use Register\Logic\ValidationService;
use Register\Infra\MailService;
use Register\Infra\Request;
use Register\Infra\Session;
use Register\Infra\UserRepository;
use Register\Infra\View;

class EditUser
{
    /** @var array<string,string> */
    private $config;

    /** @var array<string,string> */
    private $lang;

    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var ValidationService */
    private $validationService;

    /** @var UserRepository */
    private $userRepository;

    /** @var View */
    private $view;

    /** @var MailService */
    private $mailService;

    /**
     * @param array<string,string> $config
     * @param array<string,string> $lang
     */
    public function __construct(
        array $config,
        array $lang,
        Session $session,
        CsrfProtector $csrfProtector,
        ValidationService $validationService,
        UserRepository $userRepository,
        View $view,
        MailService $mailService
    ) {
        $this->config = $config;
        $this->lang = $lang;
        $session->start();
        $this->csrfProtector = $csrfProtector;
        $this->validationService = $validationService;
        $this->userRepository = $userRepository;
        $this->view = $view;
        $this->mailService = $mailService;
    }

    public function __invoke(Request $request): string
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

        $errors = $this->validationService->validateUser($name, $username, $password1, $password2, $email);
        if ($errors) {
            $csrfTokenInput = $this->csrfProtector->tokenInput();
            $this->csrfProtector->store();
            return $this->view->render('error', ['errors' => $errors])
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
        $content = $this->lang['emailprefsupdated'] . "\n\n" .
            ' ' . $this->lang['name'] . ': '.$name."\n" .
            ' ' . $this->lang['username'] . ': '.$username."\n" .
            //' ' . $this->lang['password'] . ': '.$password1."\n" .
            ' ' . $this->lang['email'] . ': '.$email."\n" .
            ' ' . $this->lang['fromip'] . ': '. $_SERVER['REMOTE_ADDR'] ."\n";

        // send update email
        $this->mailService->sendMail(
            $email,
            $this->lang['prefsemailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
            $content,
            array(
                'From: ' . $this->config['senderemail'],
                'Cc: '  . $oldemail . ', ' . $this->config['senderemail']
            )
        );
        return $this->view->message('success', 'prefsupdated');
    }

    private function trimmedPostString(string $param): string
    {
        return (isset($_POST[$param]) && is_string($_POST[$param])) ? trim($_POST[$param]) : "";
    }
}
