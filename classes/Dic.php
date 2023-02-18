<?php

/**
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use XH\CSRFProtection as CsrfProtector;

use Register\Infra\CurrentUser;
use Register\Infra\DbService;
use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\Mailer;
use Register\Infra\Pages;
use Register\Infra\Random;
use Register\Infra\Request;
use Register\Infra\Session;
use Register\Infra\SystemChecker;
use Register\Infra\UserGroupRepository;
use Register\Infra\UserRepository;
use Register\Infra\View;

/**
 * @method static string|never loginController()
 * @method static string|never handleSpecialPages()
 * @method static string|never userAdminController()
 * @method static string|never groupAdminController()
 * @method static string|never handleUserRegistration()
 * @method static string|never handlePageAccess(string $groupString)
 * @method static string|never handlePageProtection()
 * @method static string|never handlePasswordForgotten()
 * @method static string|never handleUserPreferences()
 * @method static string|never showLoginForm(bool $loggedInOnly = false)
 * @method static string|never showPageDataTab(array $pageData)
 * @method static string|never showPluginInfo()
 */
class Dic
{
    /**
     * @param list<mixed> $args
     * @return string|never
     */
    public static function __callStatic(string $name, array $args)
    {
        $handler = self::{"make" . ucfirst($name)}();
        $response = $handler(new Request, ...$args);
        return $response->fire();
    }

    public static function makeLoginController(): LoginController
    {
        global $plugin_cf, $plugin_tx;

        return new LoginController(
            $plugin_cf["register"],
            $plugin_tx["register"],
            self::makeUserRepository(),
            new UserGroupRepository(self::makeDbService()),
            new LoginManager(time(), new Session()),
            new Logger(),
            new Session(),
            self::makeCurrentUser()
        );
    }

    public static function makeHandleSpecialPages(): HandleSpecialPages
    {
        global $plugin_cf, $plugin_tx;

        return new HandleSpecialPages(
            $plugin_cf['register'],
            $plugin_tx['register'],
            self::makeView(),
            new Pages
        );
    }

    public static function makeUserAdminController(): UserAdminController
    {
        global $plugin_cf, $plugin_tx, $_XH_csrfProtection;

        return new UserAdminController(
            $plugin_cf["register"],
            $plugin_tx["register"],
            $_XH_csrfProtection,
            self::makeDbService(),
            self::makeView()
        );
    }

    public static function makeGroupAdminController(): GroupAdminController
    {
        global $_XH_csrfProtection;

        return new GroupAdminController(
            $_XH_csrfProtection,
            self::makeView(),
            self::makeDbService(),
            new Pages()
        );
    }

    public static function makeHandleUserRegistration(): HandleUserRegistration
    {
        global $plugin_cf, $plugin_tx;

        return new HandleUserRegistration(
            Dic::makeCurrentUser(),
            $plugin_cf["register"],
            $plugin_tx["register"],
            new Random,
            self::makeView(),
            self::makeUserRepository(),
            self::makeMailer()
        );
    }

    public static function makeHandlePageAccess(): HandlePageAccess
    {
        global $plugin_tx;

        return new HandlePageAccess($plugin_tx["register"], Dic::makeCurrentUser());
    }

    public static function makeHandlePageProtection(): HandlePageProtection
    {
        global $plugin_cf;

        return new HandlePageProtection($plugin_cf["register"], Dic::makeCurrentUser(), new Pages);
    }

    public static function makeHandlePasswordForgotten(): HandlePasswordForgotten
    {
        global $plugin_cf;

        return new HandlePasswordForgotten(
            Dic::makeCurrentUser(),
            $plugin_cf["register"],
            self::makeView(),
            self::makeUserRepository(),
            self::makeMailer()
        );
    }

    public static function makeHandleUserPreferences(): HandleUserPreferences
    {
        global $plugin_cf;

        return new HandleUserPreferences(
            Dic::makeCurrentUser(),
            $plugin_cf["register"],
            new Session(),
            new CsrfProtector('register_csrf_token', false),
            self::makeUserRepository(),
            self::makeView(),
            self::makeMailer(),
            new LoginManager(time(), new Session()),
            new Logger()
        );
    }

    public static function makeShowLoginForm(): ShowLoginForm
    {
        global $plugin_cf, $plugin_tx;

        return new ShowLoginForm(
            $plugin_cf["register"],
            $plugin_tx["register"],
            self::makeView(),
            Dic::makeCurrentUser()
        );
    }

    public static function makeShowPageDataTab(): ShowPageDataTab
    {
        global $plugin_tx;

        return new ShowPageDataTab($plugin_tx["register"], self::makeView());
    }

    public static function makeShowPluginInfo(): ShowPluginInfo
    {
        global $plugin_tx;

        return new ShowPluginInfo(
            $plugin_tx['register'],
            self::makeDbService(),
            new SystemChecker(),
            self::makeView()
        );
    }

    private static function makeUserRepository(): UserRepository
    {
        return new UserRepository(self::makeDbService());
    }

    private static function makeCurrentUser(): CurrentUser
    {
        return new CurrentUser(self::makeUserRepository());
    }

    private static function makeDbService(): DbService
    {
        global $pth, $plugin_cf;
        static $instance;

        if (!isset($instance)) {
            $folder = $pth["folder"]["content"];
            if ($pth["folder"]["base"] === "../") {
                $folder = dirname($folder) . "/";
            }
            $folder .= "register/";
            $instance = new DbService($folder, $plugin_cf['register']['group_default']);
        }
        return $instance;
    }

    private static function makeMailer(): Mailer
    {
        global $plugin_cf, $plugin_tx;

        return new Mailer($plugin_cf["register"]["fix_mail_headers"], $plugin_tx["register"]);
    }

    private static function makeView(): View
    {
        global $pth, $plugin_tx;

        return new View("{$pth['folder']['plugins']}register/", $plugin_tx['register']);
    }
}
