<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

class ForgotPasswordController
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
     * @var View
     */
    private $view;

    /**
     * @var DbService
     */
    private $dbService;

    /**
     * @var MailService
     */
    private $mailService;

    /**
     * @param array<string,string> $config
     * @param array<string,string> $lang
     */
    public function __construct(array $config, array $lang, View $view, DbService $dbService, MailService $mailService)
    {
        $this->config = $config;
        $this->lang = $lang;
        $this->view = $view;
        $this->dbService = $dbService;
        $this->mailService = $mailService;
    }

    /**
     * @return void
     */
    public function defaultAction()
    {
        $email = $_POST['email'] ?? '';
        echo $this->renderForgotForm($email);
    }

    /**
     * @return void
     */
    public function passwordForgottenAction()
    {
        /**
         * @var string $su
         */
        global $su;

        $email = $_POST['email'] ?? '';

        if ($email == '') {
            echo $this->view->message("fail", $this->lang['err_email']);
            echo $this->renderForgotForm($email);
            return;
        }
        if (!preg_match("/^[^\s()<>@,;:\"\/\[\]?=]+@\w[\w-]*(\.\w[\w-]*)*\.[a-z]{2,}$/i", $email)) {
            echo $this->view->message("fail", $this->lang['err_email_invalid']);
            echo $this->renderForgotForm($email);
            return;
        }

        // read user file in CSV format separated by colons
        $this->dbService->lock(LOCK_SH);
        $userArray = $this->dbService->readUsers();
        $this->dbService->lock(LOCK_UN);

        // search user for email
        $user = registerSearchUserArray($userArray, 'email', $email);
        if ($user) {
            // prepare email content for user data email
            $content = $this->lang['emailtext1'] . "\n\n"
                . ' ' . $this->lang['name'] . ": " . $user->name . "\n"
                . ' ' . $this->lang['username'] . ": " . $user->username . "\n";
            $content .= ' ' . $this->lang['email'] . ": " . $user->email . "\n";
            $content .= "\n" . $this->lang['emailtext3'] ."\n\n"
                . '<' . CMSIMPLE_URL . '?' . $su . '&'
                . 'action=registerResetPassword&username=' . urlencode($user->username) . '&nonce='
                . urlencode($user->password) . '>';

            // send reminder email
            $this->mailService->sendMail(
                $email,
                $this->lang['reminderemailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
                $content,
                array('From: ' . $this->config['senderemail'])
            );
        }
        echo $this->view->message('success', $this->lang['remindersent_reset']);
    }

    /**
     * @return void
     */
    public function resetPasswordAction()
    {
        // read user file in CSV format separated by colons
        $this->dbService->lock(LOCK_EX);
        $userArray = $this->dbService->readUsers();

        // search user for email
        $user = registerSearchUserArray($userArray, 'username', $_GET['username']);
        if (!$user) {
            echo $this->view->message("fail", $this->lang['err_username_does_not_exist']);
            $this->dbService->lock(LOCK_UN);
            return;
        }

        if ($user->password != $_GET['nonce']) {
            echo $this->view->message("fail", $this->lang['err_status_invalid']);
            $this->dbService->lock(LOCK_UN);
            return;
        }

        // in case of encrypted password a new random password will be generated
        // and its value be written back to the CSV file
        $password = base64_encode(random_bytes(8));
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $userArray = registerReplaceUserEntry($userArray, $user);
        if (!$this->dbService->writeUsers($userArray)) {
            $this->view->message("fail", $this->lang['err_cannot_write_csv']);
            $this->dbService->lock(LOCK_UN);
            return;
        }
        $this->dbService->lock(LOCK_UN);

        // prepare email content for user data email
        $content = $this->lang['emailtext1'] . "\n\n"
            . ' ' . $this->lang['name'] . ": " . $user->name . "\n"
            . ' ' . $this->lang['username'] . ": " . $user->username . "\n"
            . ' ' . $this->lang['password'] . ": " . $password . "\n"
            . ' ' . $this->lang['email'] . ": " . $user->email . "\n";

        // send reminder email
        $this->mailService->sendMail(
            $user->email,
            $this->lang['reminderemailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
            $content,
            array('From: ' . $this->config['senderemail'])
        );
        echo $this->view->message('success', $this->lang['remindersent']);
    }

    /**
     * @param string $email
     */
    private function renderForgotForm($email): string
    {
        /**
         * @var string $sn
         * @var string $su
         */
        global $sn, $su;

        return $this->view->render('forgotten-form', [
            'actionUrl' => "$sn?$su",
            'email' => $email,
        ]);
    }
}
