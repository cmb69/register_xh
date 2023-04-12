<?php

use Register\Infra\View;

if (!defined("CMSIMPLE_XH_VERSION")) {header("HTTP/1.1 403 Forbidden"); exit;}

/**
 * @var View $this
 * @var string $token
 * @var list<array{string}> $errors
 * @var string $name
 * @var string $email
 */
?>
<!-- register user preferences -->
<div class="register_settings">
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
  <form method="post">
    <fieldset>
      <legend><?=$this->text('user_prefs')?></legend>
      <input type="hidden" name="register_token" value="<?=$token?>">
      <p>
        <label>
          <span><?=$this->text('name')?></span>
          <input class="text" name="name" type="text" value="<?=$name?>" required>
        </label>
      </p>
      <p>
        <label>
          <span><?=$this->text('oldpassword')?></span>
          <input class="text" name="oldpassword" type="password" value="" required>
        </label>
      </p>
      <p>
        <label>
          <span><?=$this->text('password')?></span>
          <input class="text" name="password1" type="password" value="" required>
        </label>
      </p>
      <p>
        <label>
          <span><?=$this->text('password2')?></span>
          <input class="text" name="password2" type="password" value="" required>
        </label>
      </p>
      <p>
        <label>
          <span><?=$this->text('email')?></span>
          <input class="text" name="email" type="email" value="<?=$email?>" required>
        </label>
      </p>
      <p class="register_buttons">
        <button name="register_action" value="change_prefs"><?=$this->text('change')?></button>
      </p>
    </fieldset>
  </form>
  <form method="post">
    <fieldset>
      <input type="hidden" name="register_token" value="<?=$token?>">
      <legend><?=$this->text('user_delete')?></legend>
      <p>
        <label>
          <span><?=$this->text('oldpassword')?></span>
          <input class="text" name="oldpassword" type="password" value="" required>
        </label>
      </p>
      <p class="register_buttons">
        <button name="register_action" value="unregister"><?=$this->text('user_delete')?></button>
      </p>
    </fieldset>
  </form>
</div>
