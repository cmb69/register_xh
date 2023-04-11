<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var list<array{string}> $errors
 * @var string $token
 * @var string $groupname
 */
?>
<!-- register delete group -->
<form method="post">
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
  <input type="hidden" name="register_token" value="<?=$token?>">
  <p>
    <label>
      <span><?=$this->text('groupname')?></span>
      <input value="<?=$groupname?>" disabled>
    </label>
  </p>
  <p>
    <button name="action" value="do_delete"><?=$this->text("label_delete")?></button>
  </p>
</form>
