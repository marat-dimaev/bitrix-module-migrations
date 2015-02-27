<?php

$context = Bitrix\Main\Context::getCurrent();
/** @var \Bitrix\Main\HttpRequest $request */
$request = $context->getRequest();

if ($request->isPost()) {
    $post = $request->getPostList()->toArray();
    $post = \Bitrix\Main\Text\Encoding::convertEncodingArray($post, "UTF-8", $context->getCulture()->getCharset());
    if ($post['changeversion']) {
         \WS\Migrations\Module::getInstance()->runRefreshVersion();
    }
    if ($post['ownersetup']) {
        $options = \WS\Migrations\Module::getInstance()->getOptions();
        $options->owner = $post['ownersetup']['owner'];
        exit();
    }
}

/** @var $localization \WS\Migrations\Localization */
$localization;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?><form id="ws_maigrations_import" method="POST" action="<?=
$APPLICATION->GetCurUri()?>" ENCTYPE="multipart/form-data" name="apply"><?
$form = new CAdminForm('ws_maigrations_import', array(
    array(
        "DIV" => "edit1",
        "TAB" => $localization->getDataByPath('title'),
        "ICON" => "iblock",
        "TITLE" => $localization->getDataByPath('title'),
    ),
    array(
        "DIV" => "edit2",
        "TAB" => $localization->getDataByPath('otherVersions.tab'),
        "ICON" => "iblock",
        "TITLE" => $localization->getDataByPath('otherVersions.tab')
    )
));
$module = \WS\Migrations\Module::getInstance();
$form->BeginPrologContent();
ShowNote($localization->getDataByPath('description'));
$form->EndPrologContent();
$form->Begin(array(
    'FORM_ACTION' => $APPLICATION->GetCurUri()
));

$form->BeginNextFormTab();
$form->BeginCustomField('version', 'vv');
?>
    <tr>
        <td width="30%"><?=$localization->getDataByPath('version')?>:</td>
        <td width="60%"><b><?=$module->getDbVersion()?></b></td>
    </tr>
    <tr>
        <td width="30%"><?=$localization->getDataByPath('owner')?>:</td>
        <td width="60%"><b><?=$module->getVersionOwner()?></b> [<a id="ownerSetupLink" href="#"><?=$localization->getDataByPath('setup')?></a>]</td>
    </tr>
    <tr>
        <td></td>
        <td ><input type="submit" name="changeversion" value="<?=$localization->getDataByPath('button_change')?>"></td>
    </tr><?
$form->EndCustomField('version');
$form->BeginNextFormTab();
$form->BeginCustomField('owner', 'ww');
foreach ($module->getOptions()->getOtherVersions() as $version => $owner) {
    ?>
        <tr>
            <td width="30%"><?=$owner?>:</td>
            <td width="60%"><b><?=$version?></b></td>
        </tr>
    <?
}
$form->EndCustomField('owner');
$form->Buttons();
$form->Show();
$jsParams = array(
    'owner' => array(
        'label' => $localization->getDataByPath('owner'),
        'value' => \WS\Migrations\Module::getInstance()->getVersionOwner() ?: ''
    ),
    'dialog' => array(
        'title' => $localization->getDataByPath('dialog.title')
    )
);
?>
</form>
<script type="text/javascript">
    (function (params) {

        BX.ready(function () {
            var $ownerLink = $(document.getElementById('ownerSetupLink'));
            var save = {};
            $.extend(save, BX.CDialog.btnSave);

            save.action = function (event) {
                BX.ajax.post('?q=changeversion', dialog.GetParameters(), function () {
                    BX.reload();
                });
            };

            var dialog = new BX.CDialog({
                'title': params.dialog.title,
                'content': '<form><table cellspacing="0" cellpadding="0" border="0" width="100%"><tr><td width="40%" text-align="right">'+params.owner.label+':</td><td width="60%" align="left"><input type="text" id="owner" name="ownersetup[owner]" value="'+params.owner.value+'"></td></tr></table></form>',
                'width': 500,
                'height': 70,
                'buttons': [save, BX.CAdminDialog.btnCancel],
                'resizable': false
            });
            $ownerLink.click(function (e) {
                e.preventDefault();
                dialog.Show();
            });
        });
    })(<?=CUtil::PhpToJsObject($jsParams)?>)
</script>
