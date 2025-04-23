<?php

use Plib\View;

/**
 * @var View $this
 * @var list<array{string}> $errors
 * @var string $token
 * @var string $group
 * @var list<array{selected:string,url:string,heading:string}> $options
 * @var string $disabled
 * @var bool $show_details
 * @var string $button
 * @var string $label
 */
?>

<form method="post">
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
  <input type="hidden" name="register_token" value="<?=$this->esc($token)?>">
  <p>
    <label>
      <span><?=$this->text('label_groupname')?></span>
      <input name="groupname" value="<?=$this->esc($group)?>" <?=$this->esc($disabled)?>>
    </label>
  </p>
<?if ($show_details):?>
  <p>
    <label>
      <span><?=$this->text('label_login')?></span>
      <select name="loginpage">
        <option value=""><?=$this->text('label_none')?></option>
<?  foreach ($options as $option):?>
        <option value="<?=$this->esc($option['url'])?>" <?=$this->esc($option['selected'])?>><?=$this->esc($option['heading'])?></option>
<?  endforeach?>
      </select>
    </label>
  </p>
<?endif?>
  <p>
    <button name="action" value="<?=$this->esc($button)?>"><?=$this->text($label)?></button>
  </p>
</form>
