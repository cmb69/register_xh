<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var string $username
 * @var array<int,string> $errors
 */
?>

<div><?=$this->text('error_in_user', $username)?></div>
<ul>
<?php foreach ($errors as $error):?>
  <li><?=$this->esc($error)?></li>
<?php endforeach?>
</ul>
