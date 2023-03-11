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
<div class="regi_loggedin_loggedinarea">
  <div class="regi_loggedin_user"><?=$this->text('loggedin_welcometext', $fullName)?></div>
  <div class="regi_loggedin_loggedin"><?=$this->text('loggedin')?></div>
  <div class="regi_loggedin_settings">
<?if ($hasUserPrefs):?>
    <a href="<?=$userPrefUrl?>" title="<?=$this->text('user_prefs')?>">
      <?=$this->text('user_prefs')?>
    </a>
<?endif?>
  </div>
  <div class="regi_loggedin_logout">
    <a href="<?=$logoutUrl?>" title="<?=$this->text('logout')?>">
      <?=$this->text('logout')?>
    </a>
  </div>
  <div style="clear: both;"></div>
</div>