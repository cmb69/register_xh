<?php

use Plib\View;

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
  <input type="hidden" name="register_token" value="<?=$this->esc($token)?>">
  <p>
    <label>
      <span><?=$this->text('label_username')?></span>
      <input name="username" value="<?=$this->esc($username)?>" disabled>
    </label>
  </p>
  <p>
    <label>
      <span><?=$this->text('label_name')?></span>
      <input name="name" value="<?=$this->esc($name)?>" required>
    </label>
  </p>
  <p>
    <label>
      <span><?=$this->text('label_email')?></span>
      <input type="email" name="email" value="<?=$this->esc($email)?>" required>
    </label>
  </p>
  <p>
    <label><span><?=$this->text('label_status')?></span>
      <select name="status">
<?foreach ($states as [$status, $label, $selected]):?>
        <option value="<?=$this->esc($status)?>" <?=$this->esc($selected)?>><?=$this->text($label)?></option>
<?endforeach?>
      </select>
    </label>
  </p>
  <fieldset class="register_groups">
    <legend><?=$this->text('label_accessgroups')?></legend>
<?foreach ($groups as [$group, $checked]):?>
    <p>
      <label>
        <input type="checkbox" name="groups[]" value="<?=$this->esc($group)?>" <?=$this->esc($checked)?>>
        <span><?=$group?></span>
      </label>
    </p>
<?endforeach?>
  </fieldset>
  <p class="register_buttons">
    <button name="action" value="do_update"><?=$this->text('label_update')?></button>
  </p>
</form>
