<?php

use Register\Infra\View;

/**
 * @var View $this
 * @var list<array{heading:string,url:string,indent:string,groups:string}> $pages
 */
?>
<!-- register pages -->
<section class="register_admin">
  <h1>Register – <?=$this->text('menu_main')?></h1>
  <table>
    <thead>
      <tr>
        <th><?=$this->text('label_pages')?></th>
        <th><?=$this->text('accessgroups')?></th>
      </tr>
    </thead>
    <tbody>
<?foreach ($pages as $page):?>
      <tr>
        <td><?=$page['indent']?><a href="<?=$page['url']?>"><?=$page['heading']?></a></td>
        <td><?=$page['groups']?></td>
      </tr>
    </tbody>
<?endforeach?>
  </table>
</section>
