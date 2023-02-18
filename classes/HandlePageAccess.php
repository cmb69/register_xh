<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\CurrentUser;
use Register\Infra\Request;
use Register\Infra\Response;

class HandlePageAccess
{
    /** @var array<string,string> */
    private $text;

    /** @var CurrentUser */
    private $currentUser;

    /** @param array<string,string> $text */
    public function __construct(array $text, CurrentUser $currentUser)
    {
        $this->text = $text;
        $this->currentUser = $currentUser;
    }

    public function __invoke(Request $request, string $groupString): Response
    {
        $response = new Response;
        // remove spaces etc.
        $groupString = (string) preg_replace("/[ \t\r\n]*/", '', $groupString);
        $groupNames = explode(",", $groupString);
    
        $user = $this->currentUser->get();
        if ($request->function() !== "search"
                && (!$user || !count(array_intersect($groupNames, $user->getAccessgroups())))) {
            // go to access error page
            return $response->redirect($request->url()->withPage($this->text["access_error"])->absolute());
        }
        return $response;
    }
}
