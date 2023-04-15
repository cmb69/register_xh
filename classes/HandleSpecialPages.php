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
use Register\Infra\View;
use Register\Value\Response;

class HandleSpecialPages
{
    /** @var array<string,string> */
    private $conf;

    /** @var array<string,string> */
    private $text;

    /** @var View */
    private $view;

    /** @var Pages */
    private $pages;

    /**
     * @param array<string,string> $conf
     * @param array<string,string> $text
     */
    public function __construct(array $conf, array $text, View $view, Pages $pages)
    {
        $this->conf = $conf;
        $this->text = $text;
        $this->view = $view;
        $this->pages = $pages;
    }

    public function __invoke(Request $request): Response
    {
        if ($request->editMode()) {
            return new Response();
        }
        if ($request->url()->pageMatches($this->text["register"])) {
            return $this->registrationPageAction();
        } elseif ($request->url()->pageMatches($this->text["forgot_password"])) {
            return $this->passwordForgottenPageAction();
        } elseif ($request->url()->pageMatches($this->text["user_prefs"])) {
            return $this->userPrefsPageAction();
        } else {
            return new Response;
        }
    }

    private function registrationPageAction(): Response
    {
        if ($this->conf['allowed_register'] && !$this->pages->has($this->text['register'])) {
            return Response::create($this->renderPageView(
                $this->text['register'],
                $this->text['register_form1'],
                "registerUser()"
            ))->withTitle($this->text['register']);
        }
        return Response::create();
    }

    private function passwordForgottenPageAction(): Response
    {
        if (!$this->pages->has($this->text['forgot_password'])) {
            return Response::create($this->renderPageView(
                $this->text['forgot_password'],
                $this->text['reminderexplanation'],
                "registerForgotPassword()"
            ))->withTitle($this->text['forgot_password']);
        }
        return Response::create();
    }

    private function userPrefsPageAction(): Response
    {
        if (!$this->pages->has($this->text['user_prefs'])) {
            return Response::create($this->renderPageView(
                $this->text['user_prefs'],
                $this->text['changeexplanation'],
                "registerUserPrefs()"
            ))->withTitle($this->text['user_prefs']);
        }
        return Response::create();
    }

    /**
     * @param string $title
     * @param string $intro
     */
    private function renderPageView($title, $intro, string $pluginCall = ""): string
    {
        return $this->pages->evaluate($this->view->render('page', [
            'title' => $title,
            'intro' => $intro,
            "plugin_call" => $pluginCall
        ]));
    }
}
