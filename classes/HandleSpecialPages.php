<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\Pages;
use Register\Infra\Request;
use Register\Infra\Response;
use Register\Infra\View;

class HandleSpecialPages
{
    /** @var string[] */
    private $headings;

    /** @var array<string,string> */
    private $conf;

    /** @var array<string,string> */
    private $text;

    /** @var View */
    private $view;

    /** @var Pages */
    private $pages;

    /**
     * @param string[] $headings
     * @param array<string,string> $conf
     * @param array<string,string> $text
     */
    public function __construct(array $headings, array $conf, array $text, View $view, Pages $pages)
    {
        $this->headings = $headings;
        $this->conf = $conf;
        $this->text = $text;
        $this->view = $view;
        $this->pages = $pages;
    }

    public function __invoke(Request $request): Response
    {
        if ($request->url()->pageMatches($this->text["register"])) {
            return $this->registrationPageAction();
        } elseif ($request->url()->pageMatches($this->text["forgot_password"])) {
            return $this->passwordForgottenPageAction();
        } elseif ($request->url()->pageMatches($this->text["user_prefs"])) {
            return $this->userPrefsPageAction();
        } elseif ($request->url()->pageMatches($this->text["login_error"])) {
            return $this->loginErrorPageAction();
        } elseif ($request->url()->pageMatches($this->text["loggedout"])) {
            return $this->logoutPageAction();
        } elseif ($request->url()->pageMatches($this->text["loggedin"])) {
            return $this->loginPageAction();
        } elseif ($request->url()->pageMatches($this->text["access_error"])) {
            return $this->accessErrorPageAction();
        } else {
            return new Response;
        }
    }

    private function registrationPageAction(): Response
    {
        $response = new Response();
        if ($this->conf['allowed_register'] && !in_array($this->text['register'], $this->headings)) {
            $response->setTitle($this->text['register']);
            $response->body(
                $this->renderPageView(
                    $this->text['register'],
                    $this->text['register_form1'],
                    "registerUser()"
                )
            );
        }
        return $response;
    }

    private function passwordForgottenPageAction(): Response
    {
        $response = new Response();
        if (!in_array($this->text['forgot_password'], $this->headings)) {
            $response->setTitle($this->text['forgot_password']);
            $response->body(
                $this->renderPageView(
                    $this->text['forgot_password'],
                    $this->text['reminderexplanation'],
                    "registerForgotPassword()"
                )
            );
        }
        return $response;
    }

    private function userPrefsPageAction(): Response
    {
        $response = new Response();
        if (!in_array($this->text['user_prefs'], $this->headings)) {
            $response->setTitle($this->text['user_prefs']);
            $response->body(
                $this->renderPageView(
                    $this->text['user_prefs'],
                    $this->text['changeexplanation'],
                    "registerUserPrefs()"
                )
            );
        }
        return $response;
    }

    private function loginErrorPageAction(): Response
    {
        $response = new Response();
        $response->forbid();
        if (!in_array($this->text['login_error'], $this->headings)) {
            $response->setTitle($this->text['login_error']);
            $response->body(
                $this->renderPageView(
                    $this->text['login_error'],
                    $this->text['login_error_text']
                )
            );
        }
        return $response;
    }

    private function logoutPageAction(): Response
    {
        $response = new Response();
        if (!in_array($this->text['loggedout'], $this->headings)) {
            $response->setTitle($this->text['loggedout']);
            $response->body(
                $this->renderPageView(
                    $this->text['loggedout'],
                    $this->text['loggedout_text']
                )
            );
        }
        return $response;
    }

    private function loginPageAction(): Response
    {
        $response = new Response();
        if (!in_array($this->text['loggedin'], $this->headings)) {
            $response->setTitle($this->text['loggedin']);
            $response->body(
                $this->renderPageView(
                    $this->text['loggedin'],
                    $this->text['loggedin_text']
                )
            );
        }
        return $response;
    }

    private function accessErrorPageAction(): Response
    {
        $response = new Response();
        $response->forbid();
        if (!in_array($this->text['access_error'], $this->headings)) {
            $response->setTitle($this->text['access_error']);
            $response->body(
                $this->renderPageView(
                    $this->text['access_error'],
                    $this->text['access_error_text']
                )
            );
        }
        return $response;
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
