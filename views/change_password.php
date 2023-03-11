<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var string $url
 */
?>

<form class="register_change_password" action="<?=$url?>" method="post">
  <p>
    <label>
      <span><?=$this->text('password')?></span>
      <input name="password1" type="password" value="" required>
    </label>
  </p>
  <p>
    <label>
      <span><?=$this->text('password2')?></span>
      <input name="password2" type="password" value="" required>
    </label>
  </p>
  <p>
    <button><?=$this->text('change')?></button>
  </p>
</form>
