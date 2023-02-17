<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\CurrentUser;
use Register\Value\User;
use Register\Logic\Validator;
use Register\Infra\MailService;
use Register\Infra\Request;
use Register\Infra\Response;
use Register\Infra\UserRepository;
use Register\Infra\View;

class HandleUserRegistration
{
    /** @var CurrentUser */
    private $currentUser;

    /** @var array<string,string> */
    private $conf;

    /** @var array<string,string> */
    private $text;

    /** @var View */
    private $view;

    /** @var UserRepository */
    private $userRepository;

    /** @var MailService */
    private $mailService;

    /**
     * @param array<string,string> $conf
     * @param array<string,string> $text
     */
    public function __construct(
        CurrentUser $currentUser,
        array $conf,
        array $text,
        View $view,
        UserRepository $userRepository,
        MailService $mailService
    ) {
        $this->currentUser = $currentUser;
        $this->conf = $conf;
        $this->text = $text;
        $this->view = $view;
        $this->userRepository = $userRepository;
        $this->mailService = $mailService;
    }

    public function __invoke(Request $request): Response
    {
        if ($this->currentUser->get()) {
            return (new Response)->redirect(CMSIMPLE_URL);
        }
        if (isset($_POST['action']) && $_POST['action'] === 'register_user') {
            return $this->registerUser($request);
        }
        if (isset($_GET['action']) && $_GET['action'] === 'register_activate_user') {
            return $this->activateUser();
        }
        return $this->showForm($request);
    }

    private function showForm(Request $request): Response
    {
        $response = new Response;
        $response->body($this->view->render('registerform', [
            'actionUrl' => $request->url()->relative(),
            'name' => "",
            'username' => "",
            'password1' => "",
            'password2' => "",
            'email' => "",
        ]));
        return $response;
    }

    private function registerUser(Request $request): Response
    {
        $response = new Response;
        $name      = isset($_POST['name']) && is_string($_POST["name"]) ? trim($_POST['name']) : '';
        $username  = isset($_POST['username']) && is_string($_POST["username"]) ? trim($_POST['username']) : '';
        $password1 = isset($_POST['password1']) && is_string($_POST["password1"]) ? trim($_POST['password1']) : '';
        $password2 = isset($_POST['password2']) && is_string($_POST["password2"]) ? trim($_POST['password2']) : '';
        $email     = isset($_POST['email']) && is_string($_POST["email"]) ? trim($_POST['email']) : '';

        $validator = new Validator();
        $errors = $validator->validateUser($name, $username, $password1, $password2, $email);
        if ($errors) {
            $response->body($this->renderErrorMessages($errors)
                . $this->view->render('registerform', [
                    'actionUrl' => $request->url()->relative(),
                    'name' => $name,
                    'username' => $username,
                    'password1' => $password1,
                    'password2' => $password2,
                    'email' => $email,
                ]));
            return $response;
        }

        if ($this->userRepository->findByUsername($username)) {
            return $response->body($this->view->message("fail", 'err_username_exists'));
        }
        $user = $this->userRepository->findByEmail($email);

        // generate a nonce for the user activation
        $status = bin2hex(random_bytes(16));
        if (!$user) {
            $newUser = new User(
                $username,
                (string) password_hash($password1, PASSWORD_DEFAULT),
                array($this->conf['group_default']),
                $name,
                $email,
                $status
            );

            if (!$this->userRepository->add($newUser)) {
                return $response->body($this->view->message("fail", 'err_cannot_write_csv'));
            }
        }

        // prepare email content for registration activation
        $content = $this->text['emailtext1'] . "\n\n"
            . ' ' . $this->text['name'] . ": $name \n"
            . ' ' . $this->text['username'] . ": $username \n"
            . ' ' . $this->text['email'] . ": $email \n"
            . ' ' . $this->text['fromip'] . ": {$_SERVER['REMOTE_ADDR']} \n\n";
        if (!$user) {
            $url = $request->url()->withParams([
                "action" => "register_activate_user",
                "username" => $username,
                "nonce" => $status,
            ]);
                $content .= $this->text['emailtext2'] . "\n\n"
                . '<' . $url->absolute() . '>';
        } else {
            $url = $request->url()->withPage($this->text['forgot_password']);
            $content .= $this->text['emailtext4'] . "\n\n"
                . '<' . $url->absolute() . '>';
        }

        // send activation email
        $this->mailService->sendMail(
            $email,
            $this->text['emailsubject'] . ' ' . $_SERVER['SERVER_NAME'],
            $content,
            array(
                'From: ' . $this->conf['senderemail'],
                'Cc: '  . $this->conf['senderemail']
            )
        );
        return $response->body($this->view->message('success', 'registered'));
    }

    /** @param list<array{string}> $errors */
    private function renderErrorMessages(array $errors): string
    {
        return implode("", array_map(function ($args) {
            return $this->view->message("fail", ...$args);
        }, $errors));
    }

    private function activateUser(): Response
    {
        $response = new Response;
        if (isset($_GET['username']) && isset($_GET['nonce'])) {
            return $response->body($this->doActivateUser($_GET['username'], $_GET['nonce']));
        }
        return $response->body("");
    }

    private function doActivateUser(string $username, string $nonce): string
    {
        $user = $this->userRepository->findByUsername($username);
        if ($user === null) {
            return $this->view->message("fail", 'err_username_notfound', $username);
        }
        if ($user->getStatus() == "") {
            return $this->view->message("fail", 'err_status_empty');
        }
        if ($nonce != $user->getStatus()) {
            return $this->view->message("fail", 'err_status_invalid');
        }
        $user = $user->activate()->withAccessgroups([$this->conf['group_activated']]);
        $this->userRepository->update($user);
        return $this->view->message('success', 'activated');
    }
}
