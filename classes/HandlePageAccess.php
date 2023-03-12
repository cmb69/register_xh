<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\Request;
use Register\Infra\UserRepository;
use Register\Value\Response;

class HandlePageAccess
{
    /** @var array<string,string> */
    private $text;

    /** @var UserRepository */
    private $userRepository;

    /** @param array<string,string> $text */
    public function __construct(array $text, UserRepository $userRepository)
    {
        $this->text = $text;
        $this->userRepository = $userRepository;
    }

    public function __invoke(Request $request, string $groupString): Response
    {
        // remove spaces etc.
        $groupString = (string) preg_replace("/[ \t\r\n]*/", '', $groupString);
        $groupNames = explode(",", $groupString);
    
        $user = $this->userRepository->findByUsername($request->username());
        if ($request->function() !== "search"
                && (!$user || !count(array_intersect($groupNames, $user->getAccessgroups())))) {
            // go to access error page
            return Response::redirect($request->url()->withPage($this->text["access_error"])->absolute());
        }
        return Response::create();
    }
}
