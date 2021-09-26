<?php

namespace {

    require_once './vendor/autoload.php';
    require_once "../../cmsimple/classes/CSRFProtection.php";
    require_once './classes/DbService.php';
    require_once "./classes/ForgotPasswordController.php";
    require_once "./classes/HtmlString.php";
    require_once "./classes/InfoController.php";
    require_once "./classes/LoginFormController.php";
    require_once "./classes/MailService.php";
    require_once "./classes/RegistrationController.php";
    require_once "./classes/SystemCheckService.php";
    require_once "./classes/User.php";
    require_once "./classes/UserGroup.php";
    require_once "./classes/UserPrefsController.php";
    require_once "./classes/UserRepository.php";
    require_once "./classes/ValidationService.php";
    require_once "./classes/View.php";

    const CMSIMPLE_URL = "http://example.com";

    function uenc(string $string): string
    {
        return $string;
    }
}

namespace Register {
    function XH_startSession(): void {}
}
