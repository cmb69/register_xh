<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Value\HtmlString;
use Register\Infra\Response;
use Register\Infra\View;

class SpecialPageController
{
    /** @var string[] */
    private $headings;

    /** @var array<string,string> */
    private $conf;

    /** @var array<string,string> */
    private $text;

    /**
     * @var View
     */
    private $view;

    /**
     * @param string[] $headings
     * @param array<string,string> $conf
     * @param array<string,string> $text
     */
    public function __construct(array $headings, array $conf, array $text, View $view)
    {
        $this->headings = $headings;
        $this->conf = $conf;
        $this->text = $text;
        $this->view = $view;
    }

    public function registrationPageAction(): Response
    {
        /**
         * @var string $title
         */
        global $title;

        $response = new Response();
        if ($this->conf['allowed_register'] && !in_array($this->text['register'], $this->headings)) {
            $title = XH_hsc($this->text['register']);
            $response->body(
                $this->renderPageView(
                    $this->text['register'],
                    $this->text['register_form1'],
                    Plugin::handleUserRegistration()
                )
            );
        }
        return $response;
    }

    public function passwordForgottenPageAction(): Response
    {
        /**
         * @var string $title
         */
        global $title;

        $response = new Response();
        if (!in_array($this->text['forgot_password'], $this->headings)) {
            $title = XH_hsc($this->text['forgot_password']);
            $response->body(
                $this->renderPageView(
                    $this->text['forgot_password'],
                    $this->text['reminderexplanation'],
                    Plugin::handleForgotPassword()
                )
            );
        }
        return $response;
    }

    public function userPrefsPageAction(): Response
    {
        /**
         * @var string $title
         */
        global $title;

        $response = new Response();
        if (!in_array($this->text['user_prefs'], $this->headings)) {
            $title = XH_hsc($this->text['user_prefs']);
            $response->body(
                $this->renderPageView(
                    $this->text['user_prefs'],
                    $this->text['changeexplanation'],
                    Plugin::handleUserPrefs()
                )
            );
        }
        return $response;
    }

    public function loginErrorPageAction(): Response
    {
        /**
         * @var string $title
         */
        global $title;

        $response = new Response();
        header('HTTP/1.1 403 Forbidden');
        if (!in_array($this->text['login_error'], $this->headings)) {
            $title = $this->text['login_error'];
            $response->body(
                $this->renderPageView(
                    $this->text['login_error'],
                    $this->text['login_error_text']
                )
            );
        }
        return $response;
    }

    public function logoutPageAction(): Response
    {
        /**
         * @var string $title
         */
        global $title;

        $response = new Response();
        if (!in_array($this->text['loggedout'], $this->headings)) {
            $title = $this->text['loggedout'];
            $response->body(
                $this->renderPageView(
                    $this->text['loggedout'],
                    $this->text['loggedout_text']
                )
            );
        }
        return $response;
    }

    public function loginPageAction(): Response
    {
        /**
         * @var string $title
         */
        global $title;

        $response = new Response();
        if (!in_array($this->text['loggedin'], $this->headings)) {
            $title = $this->text['loggedin'];
            $response->body(
                $this->renderPageView(
                    $this->text['loggedin'],
                    $this->text['loggedin_text']
                )
            );
        }
        return $response;
    }

    public function accessErrorPageAction(): Response
    {
        /**
         * @var string $title
         */
        global $title;

        $response = new Response();
        header('HTTP/1.1 403 Forbidden');
        if (!in_array($this->text['access_error'], $this->headings)) {
            $title = $this->text['access_error'];
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
     * @param string $more
     */
    private function renderPageView($title, $intro, $more = ''): string
    {
        return $this->view->render('page', [
            'title' => $title,
            'intro' => $intro,
            'more' => new HtmlString($more),
        ]);
    }
}
