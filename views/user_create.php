<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var list<array{string}> $errors
 * @var string $token
 * @var string $username
 * @var string $name
 * @var string $email
 * @var list<array{string,string}> $groups
 * @var array{string,string,string} $states
 * @var string $password1
 * @var string $password2
 */
?>
<!-- register admin user form -->
<form method="post">
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
  <input type="hidden" name="register_token" value="<?=$token?>">
  <p>
    <label>
      <span><?=$this->text('username')?></span>
      <input name="username" value="<?=$username?>" required>
    </label>
  </p>
  <p>
    <label>
      <span><?=$this->text('name')?></span>
      <input name="name" value="<?=$name?>" required>
    </label>
  </p>
  <p>
    <label>
      <span><?=$this->text('email')?></span>
      <input type="email" name="email" value="<?=$email?>" required>
    </label>
  </p>
  <p>
    <label><span><?=$this->text('accessgroups')?></span>
      <select name="groups[]" multiple required>
<?foreach ($groups as [$group, $selected]):?>
        <option <?=$selected?>><?=$group?></option>
<?endforeach?>
      </select>
    </label>
  </p>
  <p>
    <label><span><?=$this->text('status')?></span>
      <select name="status">
<?foreach ($states as [$status, $label, $selected]):?>
        <option value="<?=$status?>" <?=$selected?>><?=$this->text($label)?></option>
<?endforeach?>
      </select>
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
    <button name="action" value="do_create"><?=$this->text('label_create')?></button>
  </p>
</form>
