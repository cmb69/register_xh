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
use Register\Infra\Request;
use Register\Infra\UserRepository;
use Register\Infra\View;

class ChangePassword
{
    private const TTL = 3600;

    /** @var array<string,string> */
    private $config;

    /** @var array<string,string> */
    private $lang;

    /** @var int */
    private $now;

    /** @var View */
    private $view;

    /** @var UserRepository */
    private $userRepository;

    /** @var MailService */
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

    public function __invoke(Request $request): string
    {
        $username = $_GET["username"] ?? "";
        $time = $_GET["time"] ?? 0;
        $mac = $_GET["mac"] ?? "";

        $user = $this->userRepository->findByUsername($username);
        if (!$user || !hash_equals(hash_hmac("sha1", $username . $time, $user->getPassword()), $mac)) {
            return $this->view->message("fail", $this->lang['err_status_invalid']);
        }
        if ($this->now > $time + self::TTL) {
            return $this->view->message("fail", $this->lang["forgotten_expired"]);
        }

        if (!isset($_POST["password1"], $_POST["password2"]) || $_POST["password1"] !== $_POST["password2"]) {
            $url = $request->url()->withParams([
                "action" => "register_change_password",
                "username" => $username,
                "time" => $time,
                "nonce" => $mac,
            ]);
            return $this->view->message("fail", $this->lang['err_password2'])
                . $this->view->render("change_password", [
                    "url" => $url->relative(),
                ]);
        }

        $password = $_POST["password1"];
        $user = $user->withPassword($password);
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
}
