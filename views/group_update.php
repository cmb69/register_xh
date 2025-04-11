<?php

use Plib\View;

/**
 * @var View $this
 * @var list<array{string}> $errors
 * @var string $token
 * @var string $group
 * @var list<array{selected:string,url:string,heading:string}> $options
 */
?>
<!-- register update group -->
<form method="post">
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
  <input type="hidden" name="register_token" value="<?=$this->esc($token)?>">
  <p>
    <label>
      <span><?=$this->text('label_groupname')?></span>
      <input name="groupname" value="<?=$this->esc($group)?>" disabled>
    </label>
  </p>
  <p>
    <label>
      <span><?=$this->text('label_login')?></span>
      <select name="loginpage">
        <option value=""><?=$this->text('label_none')?></option>
<?foreach ($options as $option):?>
        <option value="<?=$this->esc($option['url'])?>" <?=$this->esc($option['selected'])?>><?=$this->esc($option['heading'])?></option>
<?endforeach?>
      </select>
    </label>
  </p>
  <p>
    <button name="action" value="do_update"><?=$this->text('label_update')?></button>
  </p>
</form>
