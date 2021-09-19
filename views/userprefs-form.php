<?php

use Register\View;

/**
 * @var View $this
 * @var string $actionUrl
 * @var string $csrfTokenInput
 * @var string $name
 * @var string $email
 */
?>

<div class="regi_settings">
  <form method="post" action="<?=$this->esc($actionUrl)?>" target="_self">
    <input type="hidden" name="action" value="edit_user_prefs">
    <?=$this->esc($csrfTokenInput)?>
    <table style="margin: auto;">
      <tr>
        <td><?=$this->text('name')?></td>
        <td><input class="text" name="name" type="text" size="35" value="<?=$this->esc($name)?>"></td>
      </tr>
      <tr>
        <td><?=$this->text('oldpassword')?></td>
        <td><input class="text" name="oldpassword" type="password" size="10" value=""></td>
      </tr>
      <tr>
        <td><?=$this->text('password')?></td>
        <td><input class="text" name="password1" type="password" size="10" value=""></td>
      </tr>
      <tr>
        <td><?=$this->text('password2')?></td>
        <td><input class="text" name="password2" type="password" size="10" value=""></td>
      </tr>
      <tr>
        <td><?=$this->text('email')?></td>
        <td><input class="text" name="email" type="text" size="35" value="<?=$this->esc($email)?>"></td>
      </tr>
      <tr>
        <td colspan="2">
          <input class="submit" name="submit" type="submit" value="<?=$this->text('change')?>">
          <input class="submit" name="delete" type="submit" value="<?=$this->text('user_delete')?>">
        </td>
      </tr>
    </table>
  </form>
</div>
