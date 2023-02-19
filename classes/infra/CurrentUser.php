<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

use Register\Value\User;

class CurrentUser
{
    /** @var bool */
    private $initialized = false;

    /** @var bool */
    private $invalid = false;

    /** @var User|null */
    private $user = null;

    /** @var string */
    private $sessionFilename;

    /** @var UserRepository */
    private $userRepository;

    public function __construct(string $sessionFilename, UserRepository $userRepository)
    {
        $this->sessionFilename = $sessionFilename;
        $this->userRepository = $userRepository;
    }

    public function invalid(): bool
    {
        $this->initialize();
        return $this->invalid;
    }

    public function get(): ?User
    {
        $this->initialize();
        return $this->user;
    }

    /** @return void */
    public function login(User $user)
    {
        $this->initialized = true;
        $this->invalid = false;
        $this->user = $user;
        $this->startSession();
        $this->regenerateSessionId();
        $_SESSION["username"] = $user->getUsername();
    }

    /** @return void */
    public function logout()
    {
        $this->initialize();
        $this->startSession();
        $this->regenerateSessionId();
        unset($_SESSION["username"]);
        $this->invalid = false;
        $this->user = null;
    }

    /** @return void */
    private function initialize()
    {
        if ($this->initialized) {
            return;
        }
        $this->startSessionOnCookie();
        if (!($_SESSION["username"] ?? null)) {
            return;
        }
        $user = $this->userRepository->findByUsername($_SESSION["username"]);
        if (!$user) {
            $this->invalid = true;
            return;
        }
        $this->user = $user;
    }

    /** @return void */
    private function startSessionOnCookie()
    {
        // it would be nice if XH had an API to get the session name without starting a session
        if (is_file($this->sessionFilename) && isset($_COOKIE[file_get_contents($this->sessionFilename)])) {
            $this->startSession();
        }
    }

    /** @return void */
    protected function startSession()
    {
        XH_startSession();
    }

    /** @return void */
    protected function regenerateSessionId()
    {
        session_regenerate_id(true);
    }
}
