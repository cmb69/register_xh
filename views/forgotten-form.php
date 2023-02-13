<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var string $actionUrl
 * @var string $email
 */
?>

<form method="post" action="<?=$this->esc($actionUrl)?>" target="_self">
  <table class="regi_register">
    <tr>
      <td><input type="hidden" name="action" value="forgotten_password"><?=$this->text('email')?></td>
      <td><input class="text" name="email" type="email" size="35" value="<?=$this->esc($email)?>"></td>
      <td><input class="submit" type="submit" value="<?=$this->text('send')?>"></td>
    </tr>
  </table>
</form>
