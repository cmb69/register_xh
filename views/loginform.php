<?php

use Register\Infra\View;

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

<div class="regi_regloginarea">
  <form action="<?=$actionUrl?>" method="post">
    <input type="hidden" name="function" value="registerlogin">
    <div class="regi_user"><?=$this->text('username')?></div>
    <div class="regi_userfield"><input class="regi_userfield" type="text" name="username"></div>
    <div class="regi_password"><?=$this->text('password')?></div>
    <div class="regi_forgotpw">
<?php if ($hasForgotPasswordLink):?>
      <a href="<?=$forgotPasswordUrl?>" title="<?=$this->text('forgot_password')?>">
        <?=$this->text('forgot_password')?>
      </a>
<?php endif?>
    </div>
    <div class="regi_passwordfield">
      <input type="password" name="password">
    </div>
    <div class="regi_loginbutton">
      <button class="regi_loginbutton" name="login"><?=$this->text('login')?></button>
    </div>
<?php if ($hasRememberMe):?>
    <div class="regi_remember">
      <label><input type="checkbox" name="remember" class="regi_remember"><?=$this->text('remember')?></label>
    </div>
<?php endif?>
<?php if ($isRegisterAllowed):?>
    <div class="regi_register">
      <a href="<?=$registerUrl?>"><?=$this->text('register')?></a>
    </div>
<?php endif?>
  </form>
  <div style="clear: both;"></div>
</div>
