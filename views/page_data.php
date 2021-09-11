<form action="<?=$this->action()?>" method="post" id="register">
    <p>
        <?=$this->helpIcon()?>
        <label>
            <?=$this->text("accessgroups")?><br/>
            <input name="register_access" value="<?=$this->accessGroups()?>">
        </label>
    </p>
    <input name="save_page_data" type="hidden">
    <p>
        <button><?=$this->text("label_submit")?></button>
    </p>
</form>
