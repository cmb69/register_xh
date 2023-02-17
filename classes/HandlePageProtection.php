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
use Register\Infra\Response;
use XH\PageDataRouter as PageData;

class HandlePageProtection
{
    /** @var CurrentUser */
    private $currentUser;

    /** @var PageData */
    private $pageData;

    /** @var Pages */
    private $pages;

    public function __construct(CurrentUser $currentUser, PageData $pageData, Pages $pages)
    {
        $this->currentUser = $currentUser;
        $this->pageData = $pageData;
        $this->pages = $pages;
    }

    public function __invoke(): Response
    {
        $user = $this->currentUser->get();
        $userGroups = $user ? $user->getAccessgroups() : [];
        foreach ($this->pageData->find_all() as $i => $pd) {
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
