<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\Pages;
use Register\Infra\Request;
use Register\Infra\UserRepository;
use Register\Logic\Util;
use Register\Value\Response;

class HandlePageProtection
{
    /** @var array<string,string> */
    private $conf;

    /** @var UserRepository */
    private $userRepository;

    /** @var Pages */
    private $pages;

    /** @param array<string,string> $conf */
    public function __construct(array $conf, UserRepository $userRepository, Pages $pages)
    {
        $this->conf = $conf;
        $this->userRepository = $userRepository;
        $this->pages = $pages;
    }

    public function __invoke(Request $request): Response
    {
        if ($request->editMode()) {
            return Response::create();
        }
        $user = $this->userRepository->findByUsername($request->username());
        $data = [];
        foreach ($this->pages->data() as $i => $pd) {
            $data[] = [$this->pages->level($i), $pd["register_access"] ?? ""];
        }
        foreach (Util::accessAuthorization($user, $data) as $i => $auth) {
            if (!$auth) {
                $content = "{{{register_access('!')}}}";
                if ($this->conf["hide_pages"]) {
                    $content .= "#CMSimple hide#";
                }
                $this->pages->setContentOf($i, $content);
            }
        }
        return Response::create();
    }
}
