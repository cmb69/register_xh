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

    /**
     * @return void
     */
    public function registrationPageAction()
    {
        /**
         * @var string $title
         */
        global $title;

        if ($this->conf['allowed_register'] && !in_array($this->text['register'], $this->headings)) {
            $title = XH_hsc($this->text['register']);
            echo $this->renderPageView(
                $this->text['register'],
                $this->text['register_form1'],
                Plugin::handleUserRegistration()
            );
        }
    }

    /**
     * @return void
     */
    public function passwordForgottenPageAction()
    {
        /**
         * @var string $title
         */
        global $title;

        if (!in_array($this->text['forgot_password'], $this->headings)) {
            $title = XH_hsc($this->text['forgot_password']);
            echo $this->renderPageView(
                $this->text['forgot_password'],
                $this->text['reminderexplanation'],
                Plugin::handleForgotPassword()
            );
        }
    }

    /**
     * @return void
     */
    public function userPrefsPageAction()
    {
        /**
         * @var string $title
         */
        global $title;

        if (!in_array($this->text['user_prefs'], $this->headings)) {
            $title = XH_hsc($this->text['user_prefs']);
            echo $this->renderPageView(
                $this->text['user_prefs'],
                $this->text['changeexplanation'],
                Plugin::handleUserPrefs()
            );
        }
    }

    /**
     * @return void
     */
    public function loginErrorPageAction()
    {
        /**
         * @var string $title
         */
        global $title;

        header('HTTP/1.1 403 Forbidden');
        if (!in_array($this->text['login_error'], $this->headings)) {
            $title = $this->text['login_error'];
            echo $this->renderPageView(
                $this->text['login_error'],
                $this->text['login_error_text']
            );
        }
    }

    /**
     * @return void
     */
    public function logoutPageAction()
    {
        /**
         * @var string $title
         */
        global $title;

        if (!in_array($this->text['loggedout'], $this->headings)) {
            $title = $this->text['loggedout'];
            echo $this->renderPageView(
                $this->text['loggedout'],
                $this->text['loggedout_text']
            );
        }
    }

    /**
     * @return void
     */
    public function loginPageAction()
    {
        /**
         * @var string $title
         */
        global $title;

        if (!in_array($this->text['loggedin'], $this->headings)) {
            $title = $this->text['loggedin'];
            echo $this->renderPageView(
                $this->text['loggedin'],
                $this->text['loggedin_text']
            );
        }
    }

    /**
     * @return void
     */
    public function accessErrorPageAction()
    {
        /**
         * @var string $title
         */
        global $title;

        header('HTTP/1.1 403 Forbidden');
        if (!in_array($this->text['access_error'], $this->headings)) {
            $title = $this->text['access_error'];
            echo $this->renderPageView(
                $this->text['access_error'],
                $this->text['access_error_text']
            );
        }
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
