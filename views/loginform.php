<?php

use Register\Infra\View;

if (!defined("CMSIMPLE_XH_VERSION")) {header("HTTP/1.1 403 Forbidden"); exit;}

/**
 * @var View $this
 * @var string $actionUrl
 * @var bool $hasForgotPasswordLink
 * @var string $forgotPasswordUrl
 * @var bool $hasRememberMe
 * @var bool $isRegisterAllowed
 * @var string $registerUrl
 */
?>
<!-- register login form -->
<div class="regi_regloginarea">
  <form action="<?=$actionUrl?>" method="post">
    <input type="hidden" name="function" value="registerlogin">
    <div class="regi_user"><?=$this->text('username')?></div>
    <div class="regi_userfield"><input class="regi_userfield" type="text" name="username"></div>
    <div class="regi_password"><?=$this->text('password')?></div>
    <div class="regi_forgotpw">
<?if ($hasForgotPasswordLink):?>
      <a href="<?=$forgotPasswordUrl?>" title="<?=$this->text('forgot_password')?>">
        <?=$this->text('forgot_password')?>
      </a>
<?endif?>
    </div>
    <div class="regi_passwordfield">
      <input type="password" name="password">
    </div>
    <div class="regi_loginbutton">
      <button class="regi_loginbutton" name="login"><?=$this->text('login')?></button>
    </div>
<?if ($hasRememberMe):?>
    <div class="regi_remember">
      <label><input type="checkbox" name="remember" class="regi_remember"><?=$this->text('remember')?></label>
    </div>
<?endif?>
<?if ($isRegisterAllowed):?>
    <div class="regi_register">
      <a href="<?=$registerUrl?>"><?=$this->text('register')?></a>
    </div>
<?endif?>
  </form>
  <div style="clear: both;"></div>
</div>
