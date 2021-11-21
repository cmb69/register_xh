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
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var MailService
     */
    private $mailService;

    /**
     * @param array<string,string> $config
     * @param array<string,string> $lang
     */
    public function __construct(
        array $config,
        array $lang,
        View $view,
        UserRepository $userRepository,
        MailService $mailService
    ) {
        $this->config = $config;
        $this->lang = $lang;
        $this->view = $view;
        $this->userRepository = $userRepository;
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

        $user = $this->userRepository->findByEmail($email);
        if ($user) {
            // prepare email content for user data email
            $content = $this->lang['emailtext1'] . "\n\n"
                . ' ' . $this->lang['name'] . ": " . $user->getName() . "\n"
                . ' ' . $this->lang['username'] . ": " . $user->getUsername() . "\n";
            $content .= ' ' . $this->lang['email'] . ": " . $user->getEmail() . "\n";
            $content .= "\n" . $this->lang['emailtext3'] ."\n\n"
                . '<' . CMSIMPLE_URL . '?' . $su . '&'
                . 'action=registerResetPassword&username=' . urlencode($user->getUsername()) . '&nonce='
                . urlencode($user->getPassword()) . '>';

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
        global $sn, $su;

        $user = $this->userRepository->findByUsername($_GET['username']);
        if (!$user) {
            echo $this->view->message("fail", $this->lang['err_username_does_not_exist']);
            return;
        }

        if ($user->getPassword() != $_GET['nonce']) {
            echo $this->view->message("fail", $this->lang['err_status_invalid']);
            return;
        }

        echo $this->view->render("change_password", [
            "url" => "$sn?$su&action=register_change_password&username={$_GET['username']}&nonce={$_GET['nonce']}",
        ]);
    }

    /**
     * @return void
     */
    public function changePasswordAction()
    {
        global $sn, $su;

        $user = $this->userRepository->findByUsername($_GET['username']);
        if (!$user) {
            echo $this->view->message("fail", $this->lang['err_username_does_not_exist']);
            return;
        }

        if ($user->getPassword() != $_GET['nonce']) {
            echo $this->view->message("fail", $this->lang['err_status_invalid']);
            return;
        }

        if (!isset($_POST["password1"], $_POST["password2"]) || $_POST["password1"] !== $_POST["password2"]) {
            echo $this->view->message("fail", $this->lang['err_password2']);
            echo $this->view->render("change_password", [
                "url" => "$sn?$su&action=register_change_password&username={$_GET['username']}&nonce={$_GET['nonce']}",
            ]);
            return;
        }

        $password = $_POST["password1"];
        $user->changePassword($password);
        if (!$this->userRepository->update($user)) {
            $this->view->message("fail", $this->lang['err_cannot_write_csv']);
            return;
        }

        // prepare email content for user data email
        $content = $this->lang['emailtext1'] . "\n\n"
            . ' ' . $this->lang['name'] . ": " . $user->getName() . "\n"
            . ' ' . $this->lang['username'] . ": " . $user->getUsername() . "\n"
            . ' ' . $this->lang['email'] . ": " . $user->getEmail() . "\n";

        // send reminder email
        $this->mailService->sendMail(
            $user->getEmail(),
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
