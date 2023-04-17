<?php

use Register\Infra\View;

if (!defined("CMSIMPLE_XH_VERSION")) {header("HTTP/1.1 403 Forbidden"); exit;}

/**
 * @var View $this
 * @var string $fullName
 * @var bool $hasUserPrefs
 * @var string $userPrefUrl
 * @var string $logoutUrl
 */
?>
<!-- register logged in area -->
<div class="register_loggedin">
  <p class="register_welcome"><?=$this->text('message_loggedin_welcometext', $fullName)?></p>
  <p class="register_links">
<?if ($hasUserPrefs):?>
    <a href="<?=$userPrefUrl?>"><?=$this->text('label_user_prefs')?></a>
<?endif?>
    <a href="<?=$logoutUrl?>"><?=$this->text('label_logout')?></a>
  </p>
</div>
