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

class PasswordForgotten
{
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
        $email = $_POST['email'] ?? '';

        if ($email == '') {
            return $this->view->message("fail", 'err_email')
                . $this->view->render('forgotten-form', [
                    'actionUrl' => $request->url()->relative(),
                    'email' => $email,
                ]);
        }
        if (!preg_match("/^[^\s()<>@,;:\"\/\[\]?=]+@\w[\w-]*(\.\w[\w-]*)*\.[a-z]{2,}$/i", $email)) {
            return $this->view->message("fail", 'err_email_invalid')
                . $this->view->render('forgotten-form', [
                    'actionUrl' => $request->url()->relative(),
                    'email' => $email,
                ]);
        }

        $user = $this->userRepository->findByEmail($email);
        if ($user) {
            $mac = hash_hmac("sha1", $user->getUsername() . $this->now, $user->getPassword());
            $url = $request->url()->withParams([
                "action" => "registerResetPassword",
                "username" => $user->getUsername(),
                "time" => (string) $this->now,
                "mac" => $mac,
            ]);
            // prepare email content for user data email
            $content = $this->lang['emailtext1'] . "\n\n"
                . ' ' . $this->lang['name'] . ": " . $user->getName() . "\n"
                . ' ' . $this->lang['username'] . ": " . $user->getUsername() . "\n";
            $content .= ' ' . $this->lang['email'] . ": " . $user->getEmail() . "\n";
            $content .= "\n" . $this->lang['emailtext3'] ."\n\n"
                . '<' . $url->absolute() . '>';

            // send reminder email
            $this->mailService->sendMail(
                $email,
                $this->lang['reminderemailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
                $content,
                array('From: ' . $this->config['senderemail'])
            );
        }
        return $this->view->message('success', 'remindersent_reset');
    }
}
