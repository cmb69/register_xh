<?php

use Register\View;

/**
 * @var View $this
 * @var string $username
 * @var array<int,string> $errors
 */
?>

<div><?=$this->text('error_in_user')?>"<?=$this->esc($username)?>"</div>
<ul>
<?php foreach ($errors as $error):?>
  <li><?=$this->esc($error)?></li>
<?php endforeach?>
</ul>
