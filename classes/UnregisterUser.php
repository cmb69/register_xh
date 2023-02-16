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
use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\Request;
use Register\Infra\Session;
use Register\Infra\UserRepository;
use Register\Infra\View;

class UnregisterUser
{
    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var UserRepository */
    private $userRepository;

    /** @var View */
    private $view;

    /** @var LoginManager */
    private $loginManager;

    /** @var Logger */
    private $logger;

    public function __construct(
        Session $session,
        CsrfProtector $csrfProtector,
        UserRepository $userRepository,
        View $view,
        LoginManager $loginManager,
        Logger $logger
    ) {
        $session->start();
        $this->csrfProtector = $csrfProtector;
        $this->userRepository = $userRepository;
        $this->view = $view;
        $this->loginManager = $loginManager;
        $this->logger = $logger;
    }

    public function __invoke(Request $request): string
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