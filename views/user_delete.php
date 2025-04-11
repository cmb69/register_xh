<?php

use Plib\View;

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
  <input type="hidden" name="register_token" value="<?=$this->esc($token)?>">
  <p>
    <label>
      <span><?=$this->text('label_username')?></span>
      <input value="<?=$this->esc($username)?>" disabled>
    </label>
  </p>
  <p>
    <button name="action" value="do_delete"><?=$this->text('label_delete')?></button>
  </p>
</form>
