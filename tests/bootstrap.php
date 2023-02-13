<?php

require_once './vendor/autoload.php';
require_once '../../cmsimple/functions.php';
require_once "../../cmsimple/classes/CSRFProtection.php";
require_once "../../cmsimple/classes/Pages.php";

require_once "./classes/value/User.php";
require_once "./classes/value/UserGroup.php";

require_once "./classes/logic/ValidationService.php";

require_once './classes/infra/DbService.php';
require_once "./classes/infra/Logger.php";
require_once "./classes/infra/MailService.php";
require_once "./classes/infra/Session.php";
require_once "./classes/infra/SystemChecker.php";
require_once "./classes/infra/UserRepository.php";

require_once "./classes/ForgotPasswordController.php";
require_once "./classes/HtmlString.php";
require_once "./classes/InfoController.php";
require_once "./classes/LoginController.php";
require_once "./classes/LoginFormController.php";
require_once "./classes/LoginManager.php";
require_once "./classes/MainAdminController.php";
require_once "./classes/Plugin.php";
require_once "./classes/RedirectResponse.php";
require_once "./classes/RegistrationController.php";
require_once "./classes/UserGroupRepository.php";
require_once "./classes/UserPrefsController.php";
require_once "./classes/View.php";

const CMSIMPLE_XH_VERSION = "CMSimple_XH 1.7.5";
const CMSIMPLE_URL = "http://example.com";
