<?php

use Register\Infra\View;

if (!defined("CMSIMPLE_XH_VERSION")) {header("HTTP/1.1 403 Forbidden"); exit;}

/**
 * @var View $this
 * @var string $action
 * @var string $token
 * @var list<array{string}> $errors
 * @var string $password1
 * @var string $password2
 * @var string $cancel
 */
?>
<!-- register user preferences -->
<div class="register_settings">
  <p><?=$this->text('message_change_password')?></p>
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
  <form action="<?=$action?>" method="post">
    <input type="hidden" name="register_token" value="<?=$token?>">
    <p class="register_field">
      <label>
        <span><?=$this->text('label_oldpassword')?></span>
        <input class="text" name="oldpassword" type="password" value="" required>
      </label>
    </p>
    <p class="register_field">
      <label>
        <span><?=$this->text('label_password')?></span>
        <input class="text" name="password1" type="password" value="<?=$password1?>" required>
      </label>
    </p>
    <p class="register_field">
      <label>
        <span><?=$this->text('label_password2')?></span>
        <input class="text" name="password2" type="password" value="<?=$password2?>" required>
      </label>
    </p>
    <p class="register_buttons">
      <button><?=$this->text('label_change_password')?></button>
      <a href="<?=$cancel?>"><?=$this->text('label_cancel')?></a>
    </p>
  </form>
</div>
