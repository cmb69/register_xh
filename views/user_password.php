<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var list<array{string}> $errors
 * @var string $token
 * @var string $username
 * @var string $password1
 * @var string $password2
 */
?>
<!-- register admin change password -->
<form method="post">
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
  <input type="hidden" name="register_token" value="<?=$token?>">
  <p>
    <label>
      <span><?=$this->text('username')?></span>
      <input value="<?=$username?>" disabled>
    </label>
  </p>
  <p>
    <label>
      <span><?=$this->text('password')?></span>
      <input type="password" autocomplete="new-password" name="password1" value="<?=$password1?>" required>
    </label>
  </p>
  <p>
    <label>
      <span><?=$this->text('password2')?></span>
      <input type="password" name="password2" value="<?=$password2?>" required>
    </label>
  </p>
  <p>
    <button name="action" value="do_change_password"><?=$this->text('change_password')?></button>
  </p>
</form>
