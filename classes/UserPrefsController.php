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

class UserPrefsController
{
    /**
     * @var array<string,string>
     */
    private $config;

    /**
     * @var array<string,string>
     */
    private $lang;

    /**
     * @var CsrfProtector
     */
    private $csrfProtector;

    /**
     * @var ValidationService
     */
    private $validationService;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var View
     */
    private $view;

    /**
     * @var MailService
     */
    private $mailService;

    /**
     * @var LoginManager
     */
    private $loginManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param array<string,string> $config
     * @param array<string,string> $lang
     */
    public function __construct(
        array $config,
        array $lang,
        CsrfProtector $csrfProtector,
        ValidationService $validationService,
        UserRepository $userRepository,
        View $view,
        MailService $mailService,
        LoginManager $loginManager,
        Logger $logger
    ) {
        $this->config = $config;
        $this->lang = $lang;
        XH_startSession();
        $this->csrfProtector = $csrfProtector;
        $this->validationService = $validationService;
        $this->userRepository = $userRepository;
        $this->view = $view;
        $this->mailService = $mailService;
        $this->loginManager = $loginManager;
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function defaultAction()
    {
        $username = $_SESSION['username'] ?? '';

        $user = $this->userRepository->findByUsername($username);
        if ($user === null) {
            echo $this->view->message('fail', $this->lang['err_username_does_not_exist'] . " ('" . $username . "')");
        } elseif ($user->isLocked()) {
            echo $this->view->message('fail', $this->lang['user_locked'] . ':' .$username);
        } else {
            echo $this->renderForm($user->getName(), $user->getEmail());
        }
    }

    /**
     * @return void
     */
    public function editAction()
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

        $entry = $this->userRepository->findByUsername($username);
        if ($entry === null) {
            echo $this->view->message('fail', $this->lang['err_username_does_not_exist'] . " ('" . $username . "')");
            return;
        }

        // Test if user is locked
        if ($entry->isLocked()) {
            echo $this->view->message('fail', $this->lang['user_locked'] . ':' .$username);
            return;
        }

        // check that old password got entered correctly
        if (!password_verify($oldpassword, $entry->getPassword())) {
            echo $this->view->message("fail", $this->lang['err_old_password_wrong']);
            echo $this->renderForm($name, $email);
            return;
        }

        if ($password1 == "" && $password2 == "") {
            $password1 = $oldpassword;
            $password2 = $oldpassword;
        }
        if ($email == "") {
            $email = $entry->getEmail();
        }
        if ($name == "") {
            $name = $entry->getName();
        }

        $errors = $this->validationService->validateUser($name, $username, $password1, $password2, $email);
        if ($errors) {
            echo $this->view->render('error', ['errors' => $errors]);
            echo $this->renderForm($name, $email);
            return;
        }

        $oldemail = $entry->getEmail();

        // read user entry, update it and write it back to CSV file
        $entry->setPassword((string) password_hash($password1, PASSWORD_DEFAULT));
        $entry->setEmail($email);
        $entry->setName($name);

        if (!$this->userRepository->update($entry)) {
            echo $this->view->message("fail", $this->lang['err_cannot_write_csv']);
            return;
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
        echo $this->view->message('success', $this->lang['prefsupdated']);
    }

    private function trimmedPostString(string $param): string
    {
        return (isset($_POST[$param]) && is_string($_POST[$param])) ? trim($_POST[$param]) : "";
    }

    /**
     * @return void
     */
    public function deleteAction()
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
            echo $this->view->message('fail', $this->lang['err_username_does_not_exist'] . " ('" . $username . "')");
            return;
        }

        // Test if user is locked
        if ($entry->isLocked()) {
            echo $this->view->message('fail', $this->lang['user_locked'] . ':' .$username);
            return;
        }

        // Form Handling - Delete User ================================================
        if (!password_verify($oldpassword, $entry->getPassword())) {
            echo $this->view->message("fail", $this->lang['err_old_password_wrong']);
            echo $this->renderForm($name, $email);
            return;
        }

        if (!$this->userRepository->delete($entry)) {
            echo $this->view->message("fail", $this->lang['err_cannot_write_csv']);
            return;
        }

        $username = $_SESSION['username'] ?? '';
        $this->loginManager->logout();
        $this->logger->logInfo('logout', "$username deleted and logged out");
        echo $this->view->message('success', $this->lang['user_deleted'] . ': '.$username);
    }

    /**
     * @param string $name
     * @param string $email
     */
    private function renderForm($name, $email): string
    {
        /**
         * @var string $sn
         * @var string $su
         */
        global $sn, $su;

        $csrfTokenInput = $this->csrfProtector->tokenInput();
        $this->csrfProtector->store();
        return $this->view->render('userprefs-form', [
            'csrfTokenInput' => new HtmlString($csrfTokenInput),
            'actionUrl' => "$sn?$su",
            'name' => $name,
            'email' => $email,
        ]);
    }
}
