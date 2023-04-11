<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var list<array{string}> $errors
 * @var string $token
 * @var string $group
 * @var list<array{selected:string,url:string,heading:string}> $options
 */
?>
<!-- register create group -->
<form method="post">
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
  <input type="hidden" name="register_token" value="<?=$token?>">
  <p>
    <label>
      <span><?=$this->text('groupname')?></span>
      <input name="groupname" value="<?=$group?>">
    </label>
  </p>
  <p>
    <label>
      <span><?=$this->text('login')?></span>
      <select name="loginpage">
        <option value=""><?=$this->text('label_none')?></option>
<?foreach ($options as $option):?>
        <option value="<?=$option['url']?>" <?=$option['selected']?>><?=$option['heading']?></option>
<?endforeach?>
      </select>
    </label>
  </p>
  <p>
    <button name="action" value="do_create"><?=$this->text('label_create')?></button>
  </p>
</form>
