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

class LoginManager
{
    const REMEMBER_PERIOD = 100 * 24 * 60 * 60;

    /** @var int */
    private $now;

    /** @var Session $session */
    private $session;

    /** @var UserRepository */
    private $userRepository;

    /** @var Password */
    private $password;

    public function __construct(int $now, Session $session, UserRepository $userRepository, Password $password)
    {
        $this->now = $now;
        $this->session = $session;
        $this->userRepository = $userRepository;
        $this->password = $password;
    }

    /**
     * @return void
     */
    public function login(User $user, bool $remember)
    {
        if ($remember) {
            setcookie(
                'register_username',
                $user->getUsername(),
                $this->now + self::REMEMBER_PERIOD,
                CMSIMPLE_ROOT
            );
            setcookie(
                'register_token',
                $this->calculateMac($user->getUsername(), $user->secret()),
                $this->now + self::REMEMBER_PERIOD,
                CMSIMPLE_ROOT
            );
        }

        $this->session->start();
        session_regenerate_id(true);
        $_SESSION['username'] = $user->getUsername();
    }

    /**
     * @return void
     */
    public function logout()
    {
        $this->session->start();
        session_regenerate_id(true);
        unset($_SESSION['username']);
        $this->forget();
    }

    /**
     * @return void
     */
    public function forget()
    {
        if (isset($_COOKIE['register_username'], $_COOKIE['register_token'])) {
            setcookie('register_username', '', 0, CMSIMPLE_ROOT);
            setcookie('register_token', '', 0, CMSIMPLE_ROOT);
        }
    }

    public function isUserAuthenticated(?User $user, string $password, ?string $token): bool
    {
        if (!$user) {
            return false;
        }
        if (!$user->isActivated() && !$user->isLocked()) {
            return false;
        }
        if ($token !== null) {
            return hash_equals($this->calculateMac($user->getUsername(), $user->secret()), $token);
        }
        if (!$this->password->verify($password, $user->getPassword())) {
            return false;
        }
        if ($this->password->needsRehash($user->getPassword())) {
            $this->userRepository->update($user->withPassword($this->password->hash($password)));
        }
        return true;
    }

    private function calculateMac(string $username, string $secret): string
    {
        $mac = hash_hmac("sha1", $username, $secret, true);
        return rtrim(strtr(base64_encode($mac), "+/", "-_"), "=");
    }
}
