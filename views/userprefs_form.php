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
  <p><?=$this->text('message_change_prefs')?></p>
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
  <form method="post">
    <input type="hidden" name="register_token" value="<?=$token?>">
    <p class="register_field">
      <label>
        <span><?=$this->text('label_oldpassword')?></span>
        <input class="text" name="oldpassword" type="password" value="" required>
      </label>
    </p>
    <p class="register_field">
      <label>
        <span><?=$this->text('label_name')?></span>
        <input class="text" name="name" type="text" value="<?=$name?>" required>
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
  </form>
</div>
