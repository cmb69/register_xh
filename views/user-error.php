<?php

use Register\View;

/**
 * @var View $this
 * @var string $username
 * @var array<int,string> $errors
 */
?>

<div><?=$this->text('error_in_user')?>"<?=$this->escape($username)?>"</div>
<ul>
<?php foreach ($errors as $error):?>
    <li><?=$this->escape($error)?></li>
<?php endforeach?>
</ul>
