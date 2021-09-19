<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

class ForgotPasswordController extends Controller
{
    /**
     * @var View
     */
    private $view;

    /**
     * @var DbService
     */
    private $dbService;

    public function __construct(View $view, DbService $dbService)
    {
        parent::__construct();
        $this->view = $view;
        $this->dbService = $dbService;
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

        $errors = [];

        $email = $_POST['email'] ?? '';

        if ($email == '') {
            $errors[] = $this->lang['err_email'];
        } elseif (!preg_match("/^[^\s()<>@,;:\"\/\[\]?=]+@\w[\w-]*(\.\w[\w-]*)*\.[a-z]{2,}$/i", $email)) {
            $errors[] = $this->lang['err_email_invalid'];
        }

        // read user file in CSV format separated by colons
        $this->dbService->lock(LOCK_SH);
        $userArray = $this->dbService->readUsers();
        $this->dbService->lock(LOCK_UN);

        // search user for email
        $user = registerSearchUserArray($userArray, 'email', $email);

        if (!empty($errors)) {
            echo $this->view->render('error', ['errors' => $errors]);
            echo $this->renderForgotForm($email);
        } else {
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
                (new MailService)->sendMail(
                    $email,
                    $this->lang['reminderemailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
                    $content,
                    array('From: ' . $this->config['senderemail'])
                );
            }
            echo XH_message('success', $this->lang['remindersent_reset']);
        }
    }

    /**
     * @return void
     */
    public function resetPasswordAction()
    {
        $errors = [];

        $email = $_POST['email'] ?? '';

        // read user file in CSV format separated by colons
        $this->dbService->lock(LOCK_EX);
        $userArray = $this->dbService->readUsers();

        // search user for email
        $user = registerSearchUserArray($userArray, 'username', $_GET['username']);
        if (!$user) {
            $errors[] = $this->lang['err_username_does_not_exist'];
        }

        if ($user->password != $_GET['nonce']) {
            $errors[] = $this->lang['err_status_invalid'];
        }

        $password = null;
        // in case of encrypted password a new random password will be generated
        // and its value be written back to the CSV file
        if (empty($errors)) {
            $password = base64_encode(random_bytes(8));
            $user->password = password_hash($password, PASSWORD_DEFAULT);
            $userArray = registerReplaceUserEntry($userArray, $user);
            if (!$this->dbService->writeUsers($userArray)) {
                $errors[] = $this->lang['err_cannot_write_csv'];
            }
        }
        $this->dbService->lock(LOCK_UN);

        if (!empty($errors)) {
            echo $this->view->render('error', ['errors' => $errors]);
            echo $this->renderForgotForm($email);
        } else {
            // prepare email content for user data email
            $content = $this->lang['emailtext1'] . "\n\n"
                . ' ' . $this->lang['name'] . ": " . $user->name . "\n"
                . ' ' . $this->lang['username'] . ": " . $user->username . "\n"
                . ' ' . $this->lang['password'] . ": " . $password . "\n"
                . ' ' . $this->lang['email'] . ": " . $user->email . "\n";

            // send reminder email
            (new MailService)->sendMail(
                $user->email,
                $this->lang['reminderemailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
                $content,
                array('From: ' . $this->config['senderemail'])
            );
            echo XH_message('success', $this->lang['remindersent']);
        }
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
