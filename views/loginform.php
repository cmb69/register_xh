<?php

use Register\Infra\View;

if (!defined("CMSIMPLE_XH_VERSION")) {header("HTTP/1.1 403 Forbidden"); exit;}

/**
 * @var View $this
 * @var list<array{string}> $errors
 * @var string $username
 * @var string $password
 * @var string $checked
 * @var bool $hasForgotPasswordLink
 * @var string $forgotPasswordUrl
 * @var bool $hasRememberMe
 * @var bool $isRegisterAllowed
 * @var string $registerUrl
 */
?>
<!-- register login form -->
<div class="register_login">
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
  <form method="post">
    <input type="hidden" name="function" value="registerlogin">
    <p class="register_field">
      <label>
        <span><?=$this->text('username')?></span>
        <input name="username" value="<?=$username?>">
    </p>
    <p class="register_field">
      <label>
        <span><?=$this->text('password')?></span>
        <input type="password" name="password" value="<?=$password?>">
      </label>
    </p>
    <p class="register_buttons">
      <button name="login"><?=$this->text('login')?></button>
<?if ($hasRememberMe):?>
      <label>
        <input type="checkbox" name="remember" <?=$checked?>>
        <span><?=$this->text('remember')?></span>
      </label>
<?endif?>
    </p>
    <p class="register_links">
<?if ($hasForgotPasswordLink):?>
      <a href="<?=$forgotPasswordUrl?>"><?=$this->text('forgot_password')?></a>
<?endif?>
<?if ($isRegisterAllowed):?>
      <a href="<?=$registerUrl?>"><?=$this->text('register')?></a>
<?endif?>
    </p>
  </form>
</div>
