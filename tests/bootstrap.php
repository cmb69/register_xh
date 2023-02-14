<?php

require_once './vendor/autoload.php';
require_once '../../cmsimple/functions.php';
require_once "../../cmsimple/classes/CSRFProtection.php";
require_once "../../cmsimple/classes/Pages.php";

require_once "./classes/value/HtmlString.php";
require_once "./classes/value/User.php";
require_once "./classes/value/UserGroup.php";

require_once "./classes/logic/ValidationService.php";

require_once './classes/infra/DbService.php';
require_once "./classes/infra/Logger.php";
require_once "./classes/infra/LoginManager.php";
require_once "./classes/infra/MailService.php";
require_once "./classes/infra/RedirectResponse.php";
require_once "./classes/infra/Request.php";
require_once "./classes/infra/Session.php";
require_once "./classes/infra/SystemChecker.php";
require_once "./classes/infra/Url.php";
require_once "./classes/infra/UserGroupRepository.php";
require_once "./classes/infra/UserRepository.php";
require_once "./classes/infra/View.php";

require_once "./classes/ActivateUser.php";
require_once "./classes/ChangePassword.php";
require_once "./classes/Dic.php";
require_once "./classes/EditUser.php";
require_once "./classes/GroupAdminController.php";
require_once "./classes/LoginController.php";
require_once "./classes/PasswordForgotten.php";
require_once "./classes/Plugin.php";
require_once "./classes/RegisterUser.php";
require_once "./classes/ResetPassword.php";
require_once "./classes/SpecialPageController.php";
require_once "./classes/ShowLoginForm.php";
require_once "./classes/ShowPageDataTab.php";
require_once "./classes/ShowPasswordForgottenForm.php";
require_once "./classes/ShowPluginInfo.php";
require_once "./classes/ShowRegistrationForm.php";
require_once "./classes/ShowUserPreferences.php";
require_once "./classes/UnregisterUser.php";
require_once "./classes/UserAdminController.php";

const CMSIMPLE_XH_VERSION = "CMSimple_XH 1.7.5";
const CMSIMPLE_URL = "http://example.com";
const CMSIMPLE_ROOT = "/";
