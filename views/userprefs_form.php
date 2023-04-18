<?php

use Register\Infra\View;

if (!defined("CMSIMPLE_XH_VERSION")) {header("HTTP/1.1 403 Forbidden"); exit;}

/**
 * @var View $this
 * @var string $token
 * @var list<array{string}> $errors
 * @var string $name
 * @var string $email
 * @var string $cancel
 */
?>
<!-- register user preferences -->
<div class="register_settings">
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
  <p><?=$this->text('message_changeexplanation')?></p>
  <form method="post">
    <fieldset>
      <legend><?=$this->text('label_user_prefs')?></legend>
      <input type="hidden" name="register_token" value="<?=$token?>">
      <p class="register_field">
        <label>
          <span><?=$this->text('label_name')?></span>
          <input class="text" name="name" type="text" value="<?=$name?>" required>
        </label>
      </p>
      <p class="register_field">
        <label>
          <span><?=$this->text('label_oldpassword')?></span>
          <input class="text" name="oldpassword" type="password" value="" required>
        </label>
      </p>
      <p class="register_field">
        <label>
          <span><?=$this->text('label_password')?></span>
          <input class="text" name="password1" type="password" value="" required>
        </label>
      </p>
      <p class="register_field">
        <label>
          <span><?=$this->text('label_password2')?></span>
          <input class="text" name="password2" type="password" value="" required>
        </label>
      </p>
      <p class="register_field">
        <label>
          <span><?=$this->text('label_email')?></span>
          <input class="text" name="email" type="email" value="<?=$email?>" required>
        </label>
      </p>
      <p class="register_buttons">
        <button name="register_action" value="change_prefs"><?=$this->text('label_change')?></button>
        <a href="<?=$cancel?>"><?=$this->text('label_cancel')?></a>
      </p>
    </fieldset>
  </form>
  <form method="post">
    <fieldset>
      <input type="hidden" name="register_token" value="<?=$token?>">
      <legend><?=$this->text('label_user_delete')?></legend>
      <p>
        <label>
          <span><?=$this->text('label_oldpassword')?></span>
          <input class="text" name="oldpassword" type="password" value="" required>
        </label>
      </p>
      <p class="register_buttons">
        <button name="register_action" value="unregister"><?=$this->text('label_user_delete')?></button>
        <a href="<?=$cancel?>"><?=$this->text('label_cancel')?></a>
      </p>
    </fieldset>
  </form>
</div>
