<?php

use Register\Infra\View;

if (!defined("CMSIMPLE_XH_VERSION")) {header("HTTP/1.1 403 Forbidden"); exit;}

/**
 * @var View $this
 * @var string $actionUrl
 * @var list<array{string}> $errors
 * @var string $name
 * @var string $username
 * @var string $password1
 * @var string $password2
 * @var string $email
 * @var string $cancel
 */
?>
<!-- register registration form -->
<div class="register_register">
  <form method="post">
<?foreach ($errors as $error):?>
    <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
    <p><?=$this->text('message_register_form1')?></p>
    <p class="register_field">
      <label>
        <span><?=$this->text('label_name')?></span>
        <input class="text" name="name" type="text" value="<?=$name?>" required>
      </label>
    </p>
    <p class="register_field">
      <label>
        <span><?=$this->text('label_username')?></span>
        <input class="text" name="username" type="text" value="<?=$username?>" required>
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
    <p class="register_field">
      <label>
        <span><?=$this->text('label_email')?></span>
        <input class="text" name="email" type="email" value="<?=$email?>" required>
      </label>
    </p>
    <p class="register_buttons">
      <button name="register_action" value="register"><?=$this->text('label_register')?></button>
      <a href="<?=$cancel?>">Cancel</a>
    </p>
    <p><?=$this->text('message_register_form2')?></p>
  </form>
</div>
