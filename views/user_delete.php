<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var list<array{string}> $errors
 * @var string $token
 * @var string $username
 */
?>
<!-- register admin delete -->
<form method="post">
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
  <?=$token?>
  <p>
    <label>
      <span><?=$this->text('username')?></span>
      <input value="<?=$username?>" disabled>
    </label>
  </p>
  <p>
    <button name="action" value="do_delete"><?=$this->text('label_delete')?></button>
  </p>
</form>
