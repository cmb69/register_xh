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
use Register\Infra\Pages;
use Register\Infra\Request;
use Register\Infra\Response;

class HandlePageProtection
{
    /** @var array<string,string> */
    private $conf;

    /** @var CurrentUser */
    private $currentUser;

    /** @var Pages */
    private $pages;

    /** @param array<string,string> $conf */
    public function __construct(array $conf, CurrentUser $currentUser, Pages $pages)
    {
        $this->conf = $conf;
        $this->currentUser = $currentUser;
        $this->pages = $pages;
    }

    public function __invoke(Request $request): Response
    {
        if (($request->admin() && $request->edit()) || !$this->conf["hide_pages"]) {
            return new Response();
        }
        $user = $this->currentUser->get();
        $userGroups = $user ? $user->getAccessgroups() : [];
        foreach ($this->pages->data() as $i => $pd) {
            if (($arg = trim($pd["register_access"] ?? ""))) {
                $groups = array_map('trim', explode(',', $arg));
                if (count(array_intersect($groups, $userGroups)) == 0) {
                    $this->pages->setContentOf($i, "#CMSimple hide# {{{register_access('$arg')}}}");
                }
            }
        }
        return new Response();
    }
}
