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
 */
?>
<!-- register registration form -->
<div class="register_register">
  <form method="post">
<?foreach ($errors as $error):?>
    <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
    <p>
      <label>
        <span><?=$this->text('name')?></span>
        <input class="text" name="name" type="text" value="<?=$name?>" required>
      </label>
    </p>
    <p>
      <label>
        <span><?=$this->text('username')?></span>
        <input class="text" name="username" type="text" value="<?=$username?>" required>
      </label>
    </p>
    <p>
      <label>
        <span><?=$this->text('password')?></span>
        <input class="text" name="password1" type="password" value="<?=$password1?>" required>
      </label>
    </p>
    <p>
      <label>
        <span><?=$this->text('password2')?></span>
        <input class="text" name="password2" type="password" value="<?=$password2?>" required>
      </label>
    </p>
    <p>
      <label>
        <span><?=$this->text('email')?></span>
        <input class="text" name="email" type="email" value="<?=$email?>" required>
      </label>
    </p>
    <p class="register_buttons">
      <button name="register_action" value="register"><?=$this->text('register')?></button>
    </p>
    <p><?=$this->text('register_form2')?></p>
  </form>
</div>
