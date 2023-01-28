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
    const TTL = 3600;

    /**
     * @var array<string,string>
     */
    private $config;

    /**
     * @var array<string,string>
     */
    private $lang;

    /** @var int */
    private $now;

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
        int $now,
        View $view,
        UserRepository $userRepository,
        MailService $mailService
    ) {
        $this->config = $config;
        $this->lang = $lang;
        $this->now = $now;
        $this->view = $view;
        $this->userRepository = $userRepository;
        $this->mailService = $mailService;
    }

    public function defaultAction(): string
    {
        $email = $_POST['email'] ?? '';
        return $this->renderForgotForm($email);
    }

    public function passwordForgottenAction(): string
    {
        /**
         * @var string $su
         */
        global $su;

        $email = $_POST['email'] ?? '';

        if ($email == '') {
            return $this->view->message("fail", $this->lang['err_email'])
                . $this->renderForgotForm($email);
        }
        if (!preg_match("/^[^\s()<>@,;:\"\/\[\]?=]+@\w[\w-]*(\.\w[\w-]*)*\.[a-z]{2,}$/i", $email)) {
            return $this->view->message("fail", $this->lang['err_email_invalid'])
                . $this->renderForgotForm($email);
        }

        $user = $this->userRepository->findByEmail($email);
        if ($user) {
            $mac = $this->calculateMac($user->getUsername(), $this->now, $user->getPassword());
            // prepare email content for user data email
            $content = $this->lang['emailtext1'] . "\n\n"
                . ' ' . $this->lang['name'] . ": " . $user->getName() . "\n"
                . ' ' . $this->lang['username'] . ": " . $user->getUsername() . "\n";
            $content .= ' ' . $this->lang['email'] . ": " . $user->getEmail() . "\n";
            $content .= "\n" . $this->lang['emailtext3'] ."\n\n"
                . '<' . CMSIMPLE_URL . '?' . $su . '&'
                . 'action=registerResetPassword&username=' . urlencode($user->getUsername()) . '&time='
                . urlencode((string) $this->now) . '&mac=' . urlencode($mac) . '>';

            // send reminder email
            $this->mailService->sendMail(
                $email,
                $this->lang['reminderemailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
                $content,
                array('From: ' . $this->config['senderemail'])
            );
        }
        return $this->view->message('success', $this->lang['remindersent_reset']);
    }

    public function resetPasswordAction(): string
    {
        global $sn, $su;

        $username = $_GET["username"] ?? "";
        $time = $_GET["time"] ?? 0;
        $mac = $_GET["mac"] ?? "";

        $user = $this->userRepository->findByUsername($username);
        if (!$user || !hash_equals($this->calculateMac($username, $time, $user->getPassword()), $mac)) {
            return $this->view->message("fail", $this->lang['err_status_invalid']);
        }
        if ($this->now > $time + self::TTL) {
            return $this->view->message("fail", $this->lang["forgotten_expired"]);
        }

        $username = urlencode($username);
        $time = urlencode($time);
        $mac = urlencode($mac);
        return $this->view->render("change_password", [
            "url" => "$sn?$su&action=register_change_password&username=$username&time=$time&mac=$mac",
        ]);
    }

    public function changePasswordAction(): string
    {
        global $sn, $su;

        $username = $_GET["username"] ?? "";
        $time = $_GET["time"] ?? 0;
        $mac = $_GET["mac"] ?? "";

        $user = $this->userRepository->findByUsername($username);
        if (!$user || !hash_equals($this->calculateMac($username, $time, $user->getPassword()), $mac)) {
            return $this->view->message("fail", $this->lang['err_status_invalid']);
        }
        if ($this->now > $time + self::TTL) {
            return $this->view->message("fail", $this->lang["forgotten_expired"]);
        }

        if (!isset($_POST["password1"], $_POST["password2"]) || $_POST["password1"] !== $_POST["password2"]) {
            $o = $this->view->message("fail", $this->lang['err_password2']);
            $username = urlencode($username);
            $time = urlencode($time);
            $mac = urlencode($mac);
            return $o . $this->view->render("change_password", [
                "url" => "$sn?$su&action=register_change_password&username=$username&time=$time&nonce=$mac",
            ]);
        }

        $password = $_POST["password1"];
        $user->changePassword($password);
        if (!$this->userRepository->update($user)) {
            return $this->view->message("fail", $this->lang['err_cannot_write_csv']);
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
        return $this->view->message('success', $this->lang['remindersent']);
    }

    private function calculateMac(string $username, int $time, string $secret): string
    {
        $mac = hash_hmac("sha1", "{$username}{$time}", $secret, true);
        return rtrim(strtr(base64_encode($mac), "+/", "-_"), "=");
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
