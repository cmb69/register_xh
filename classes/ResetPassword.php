<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\Request;
use Register\Infra\UserRepository;
use Register\Infra\View;

class ResetPassword
{
    private const TTL = 3600;

    /** @var int */
    private $now;

    /** @var View */
    private $view;

    /** @var UserRepository */
    private $userRepository;

    public function __construct(
        int $now,
        View $view,
        UserRepository $userRepository
    ) {
        $this->now = $now;
        $this->view = $view;
        $this->userRepository = $userRepository;
    }

    public function __invoke(Request $request): string
    {
        $username = $_GET["username"] ?? "";
        $time = $_GET["time"] ?? 0;
        $mac = $_GET["mac"] ?? "";

        $user = $this->userRepository->findByUsername($username);
        if (!$user || !hash_equals(hash_hmac("sha1", $username . $time, $user->getPassword()), $mac)) {
            return $this->view->message("fail", 'err_status_invalid');
        }
        if ($this->now > $time + self::TTL) {
            return $this->view->message("fail", "forgotten_expired");
        }
        $url = $request->url()->withParams([
            "action" => "register_change_password",
            "username" => $username,
            "time" => $time,
            "mac" => $mac,
        ]);
        $username = urlencode($username);
        $time = urlencode($time);
        $mac = urlencode($mac);
        return $this->view->render("change_password", [
            "url" => $url->relative(),
        ]);
    }
}
