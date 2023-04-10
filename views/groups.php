<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var list<array{string}> $errors
 * @var list<array{name:string,loginpage:string}> $groups
 */
?>
<!-- register groups -->
<form method="get">
<?foreach ($errors as $error):?>
  <p class="xh_fail"><?=$this->text(...$error)?></p>
<?endforeach?>
  <input type="hidden" name="selected" value="register">
  <input type="hidden" name="admin" value="groups">
  <table>
    <thead>
      <tr>
        <th><?=$this->text('groupname')?></th>
        <th><?=$this->text('login')?></th>
      </tr>
    </thead>
    <tbody>
<?foreach ($groups as $group):?>
      <tr>
        <td>
          <label><input type="radio" name="group" value="<?=$group['name']?>"> <?=$group['name']?></label>
        </td>
        <td><?=$group['loginpage']?></td>
      </tr>
<?endforeach?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="2">
          <button name="action" value="create"><?=$this->text('label_new')?></button>
          <button name="action" value="update"><?=$this->text('label_edit')?></button>
          <button name="action" value="delete"><?=$this->text('label_delete')?></button>
          <button name="admin" value="users"><?=$this->text('label_users')?></button>
        </td>
      </tr>
    </tfoot>
  </table>
</form>
