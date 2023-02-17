<?php

require_once './vendor/autoload.php';
require_once '../../cmsimple/functions.php';
require_once "../../cmsimple/classes/CSRFProtection.php";
require_once "../../cmsimple/classes/PageDataRouter.php";
require_once "../../cmsimple/classes/Pages.php";

require_once "./classes/value/HtmlString.php";
require_once "./classes/value/User.php";
require_once "./classes/value/UserGroup.php";

require_once "./classes/logic/AdminProcessor.php";
require_once "./classes/logic/Validator.php";

require_once "./classes/infra/CurrentUser.php";
require_once './classes/infra/DbService.php';
require_once "./classes/infra/Logger.php";
require_once "./classes/infra/LoginManager.php";
require_once "./classes/infra/MailService.php";
require_once "./classes/infra/Pages.php";
require_once "./classes/infra/Request.php";
require_once "./classes/infra/Response.php";
require_once "./classes/infra/Session.php";
require_once "./classes/infra/SystemChecker.php";
require_once "./classes/infra/Url.php";
require_once "./classes/infra/UserGroupRepository.php";
require_once "./classes/infra/UserRepository.php";
require_once "./classes/infra/View.php";

require_once "./classes/Dic.php";
require_once "./classes/GroupAdminController.php";
require_once "./classes/HandlePageAccess.php";
require_once "./classes/HandlePageProtection.php";
require_once "./classes/HandlePasswordForgotten.php";
require_once "./classes/HandleSpecialPages.php";
require_once "./classes/HandleUserPreferences.php";
require_once "./classes/HandleUserRegistration.php";
require_once "./classes/LoginController.php";
require_once "./classes/Plugin.php";
require_once "./classes/ShowLoginForm.php";
require_once "./classes/ShowPageDataTab.php";
require_once "./classes/ShowPluginInfo.php";
require_once "./classes/UserAdminController.php";

const CMSIMPLE_XH_VERSION = "CMSimple_XH 1.7.5";
const CMSIMPLE_URL = "http://example.com/";
const CMSIMPLE_ROOT = "/";
const XH_URICHAR_SEPARATOR = "|";
