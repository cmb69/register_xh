<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

class LoginManager
{
    const REMEMBER_PERIOD = 100 * 24 * 60 * 60;

    /** @var int */
    private $now;

    public function __construct(int $now)
    {
        $this->now = $now;
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
                $this->calculateMac($user->getUsername(), $user->getPassword()),
                $this->now + self::REMEMBER_PERIOD,
                CMSIMPLE_ROOT
            );
        }

        XH_startSession();
        session_regenerate_id(true);
        $_SESSION['username'] = $user->getUsername();
    }

    /**
     * @return void
     */
    public function logout()
    {
        XH_startSession();
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

    /**
     * @param User|null $user
     * @param string|null $token
     */
    public function isUserAuthenticated($user, string $password, $token): bool
    {
        return $user
            && ($user->isActivated() || $user->isLocked())
            && (!isset($token) || hash_equals($this->calculateMac($user->getUsername(), $user->getPassword()), $token))
            && (isset($token) || (password_verify($password, $user->getPassword())));
    }

    private function calculateMac(string $username, string $secret): string
    {
        $mac = hash_hmac("sha1", "{$username}", $secret, true);
        return rtrim(strtr(base64_encode($mac), "+/", "-_"), "=");
    }
}
