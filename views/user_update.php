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
      <input name="username" value="<?=$username?>" disabled>
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
    <label><span><?=$this->text('status')?></span>
      <select name="status">
<?foreach ($states as [$status, $label, $selected]):?>
        <option value="<?=$status?>" <?=$selected?>><?=$this->text($label)?></option>
<?endforeach?>
      </select>
    </label>
  </p>
  <fieldset class="register_groups">
    <legend><?=$this->text('accessgroups')?></legend>
<?foreach ($groups as [$group, $checked]):?>
    <p>
      <label>
        <input type="checkbox" name="groups[]" value="<?=$group?>" <?=$checked?>>
        <span><?=$group?></span>
      </label>
    </p>
<?endforeach?>
  </fieldset>
  <p class="register_buttons">
    <button name="action" value="do_update"><?=$this->text('label_update')?></button>
  </p>
</form>
