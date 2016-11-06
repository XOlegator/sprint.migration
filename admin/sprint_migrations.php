<?php

/** @noinspection PhpIncludeInspection */
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

\CModule::IncludeModule("sprint.migration");

/** @global $APPLICATION \CMain */

global $APPLICATION;
$APPLICATION->SetTitle(GetMessage('SPRINT_MIGRATION_TITLE'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    CUtil::JSPostUnescape();
}

$configName = !empty($_POST['config']) ? $_POST['config'] : false;
$versionManager = new Sprint\Migration\VersionManager($configName);

include __DIR__ .'/steps/migration_execute.php';
include __DIR__ .'/steps/migration_list.php';
include __DIR__ .'/steps/migration_status.php';
include __DIR__ .'/steps/migration_create.php';

/** @noinspection PhpIncludeInspection */
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

?>
<style type="text/css">
    .c-migration-item-installed,
    .c-migration-item-new,
    .c-migration-item-unknown {
        text-decoration: none;
    }

    .c-migration-item-installed,
    .c-migration-item-installed:link,
    .c-migration-item-installed:hover,
    .c-migration-item-installed:visited,
    a.c-migration-item-installed,
    a.c-migration-item-installed:link,
    a.c-migration-item-installed:hover,
    a.c-migration-item-installed:visited
    {
        color: #080;
    }

    .c-migration-item-new,
    .c-migration-item-new:link,
    .c-migration-item-new:hover,
    .c-migration-item-new:visited,
    a.c-migration-item-new,
    a.c-migration-item-new:link,
    a.c-migration-item-new:hover,
    a.c-migration-item-new:visited
    {
        color: #a00;
    }

    .c-migration-item-unknown,
    .c-migration-item-unknown:link,
    .c-migration-item-unknown:hover,
    .c-migration-item-unknown:visited,
    a.c-migration-item-unknown,
    a.c-migration-item-unknown:link,
    a.c-migration-item-unknown:hover,
    a.c-migration-item-unknown:visited
    {
        color: #00a;
    }
</style>


<div style="height: 40px;">
<?php $info = $versionManager->getConfigInfo(); ?>
<select name="migration_config">
    <?foreach ($info as $val):?>
        <option <?if ($val['current'] == 1):?>selected="selected"<?endif;?> value="<?=$val['name']?>"><?=$val['title']?></option>
    <?endforeach?>
</select>

</div>

<? $tabControl1 = new CAdminTabControl("tabControl2", array(
    array("DIV" => "tab1", "TAB" => GetMessage('SPRINT_MIGRATION_TAB1'), "TITLE" => GetMessage('SPRINT_MIGRATION_TAB1_TITLE')),
    array("DIV" => "tab2", "TAB" => GetMessage('SPRINT_MIGRATION_TAB2'), "TITLE" => GetMessage('SPRINT_MIGRATION_TAB2_TITLE')),
    array("DIV" => "tab3", "TAB" => GetMessage('SPRINT_MIGRATION_TAB3'), "TITLE" => GetMessage('SPRINT_MIGRATION_TAB3_TITLE')),
));

$tabControl1->Begin();
$tabControl1->BeginNextTab();
?>
<tr>
    <td style="vertical-align: top">
        <div id="migration_migrations"></div>
    </td>
</tr>
<?$tabControl1->BeginNextTab();?>
<tr>
    <td style="width:50%;padding: 5px 5px;vertical-align: top;text-align: left">
        <p><?= GetMessage('SPRINT_MIGRATION_HELP_DOC') ?></p>
        <p>
            <a href="https://bitbucket.org/andrey_ryabin/sprint.migration" target="_blank">https://bitbucket.org/andrey_ryabin/sprint.migration</a>
        </p>
    </td>
    <td style="width:50%;padding: 5px 5px;vertical-align: top;text-align: left">
        <div class="c-migration-adm-create">
            <p>
                <?= GetMessage('SPRINT_MIGRATION_FORM_PREFIX') ?><br/>
                <input type="text" id="migration_migration_prefix" value="Version" />
            </p>
            <p>
                <?= GetMessage('SPRINT_MIGRATION_FORM_DESCR') ?> <br/>
                <textarea rows="3" cols="50" id="migration_migration_descr"></textarea>
            </p>
            <p>
                <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_GENERATE') ?>" onclick="migrationCreateMigration();" />
            </p>
        </div>
    </td>
</tr>
<?$tabControl1->BeginNextTab();?>
<tr>
    <td style="vertical-align: top">
        <div id="migration_progress" style="overflow-x:auto;overflow-y: scroll;max-height: 320px;"></div>
    </td>
</tr>
<? $tabControl1->Buttons(); ?>

<div style="text-align: center">
    <div style="float: left;">
        <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_UP_START') ?>" onclick="migrationMigrationsUpConfirm();" class="adm-btn-green" />
        <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_DOWN_START') ?>" onclick="migrationMigrationsDownConfirm();" />

    </div>

    <input style="" type="text" value="" class="adm-input" name="migration_search"/>
    <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_SEARCH') ?>" class="c-migration-search" />

    <div style="float: right;">
        <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_TOGGLE_LIST') ?>" onclick="migrationMigrationToggleView('list');" class="adm-btn c-migration-stat c-migration-stat-list" />
        <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_TOGGLE_NEW') ?>" onclick="migrationMigrationToggleView('new');" class="adm-btn c-migration-stat c-migration-stat-new" />
        <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_TOGGLE_STATUS') ?>" onclick="migrationMigrationToggleView('status');" class="adm-btn c-migration-stat c-migration-stat-status" />
    </div>
</div>

<input type="hidden" value="<?= bitrix_sessid() ?>" name="send_sessid" />
<? $tabControl1->End(); ?>


<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<script type="text/javascript">
    function migrationMigrationsUpConfirm() {
        if (confirm('<?=GetMessage('SPRINT_MIGRATION_UP_CONFIRM')?>')) {
            migrationExecuteStep('migration_execute', {
                'next_action': 'up'
            });
        }
    }

    function migrationMigrationsDownConfirm() {
        if (confirm('<?=GetMessage('SPRINT_MIGRATION_DOWN_CONFIRM')?>')) {
            migrationExecuteStep('migration_execute', {
                'next_action'  : 'down'
            });
        }
    }

    function migrationOutProgress(result) {
        var outProgress = $('#migration_progress');
        var lastOutElem = outProgress.children('div').last();
        if (lastOutElem.hasClass('migration-bar') && $(result).first().hasClass('migration-bar')){
            lastOutElem.replaceWith(result);
        } else {
            outProgress.append(result);
            outProgress.scrollTop(outProgress.prop("scrollHeight"));
        }
    }

    function migrationExecuteStep(step_code, postData, succesCallback) {
        postData = postData || {};
        postData['step_code'] = step_code;
        postData['send_sessid'] = $('input[name=send_sessid]').val();
        postData['search'] = $('input[name=migration_search]').val();
        postData['config'] = $('select[name=migration_config]').val();

        migrationEnableButtons(0);

        jQuery.ajax({
            type: "POST",
            url: '<?=pathinfo(__FILE__, PATHINFO_BASENAME)?>?lang=ru',
            dataType: "html",
            data: postData,
            success: function (result) {
                if (succesCallback) {
                    succesCallback(result)
                } else {
                    migrationOutProgress(result);
                }
            },
            error: function(result){

            }
        });
    }

    function migrationEnableButtons(enable) {
        var buttons = $('#tabControl2_layout').find('input[type=button]');
        if (enable == 1){
            buttons.removeAttr('disabled');
        } else {
            buttons.attr('disabled', 'disabled');
        }
    }

    function migrationCreateMigration() {
        migrationExecuteStep('migration_create', {
            description: $('#migration_migration_descr').val(),
            prefix: $('#migration_migration_prefix').val()
        }, function (result) {
            $('#migration_migration_descr').val('');
            migrationOutProgress(result);
            migrationMigrationRefresh();
        });
    }

    function migrationMigrationToggleView(view){
        migrationView = view;

        $('.c-migration-stat').removeClass('adm-btn-active');
        $('.c-migration-stat-' + view).addClass('adm-btn-active');

        migrationMigrationRefresh(function(){
            migrationEnableButtons(1);
            $('#tab_cont_tab1').click();
            $("html, body").scrollTop(0);
        });
    }

    function migrationMigrationRefresh(callbackAfterRefresh) {
        migrationExecuteStep('migration_' + migrationView, {}, function (data) {
            $('#migration_migrations').empty().html(data);
            if (callbackAfterRefresh) {
                callbackAfterRefresh()
            } else {
                migrationEnableButtons(1);
            }
        });
    }

</script>

<script type="text/javascript">
    <?
    
    $views = array('list', 'new', 'status');
    $curView = \Sprint\Migration\Module::getDbOption('admin_versions_view');
    $curView = in_array($curView, $views) ? $curView : 'list';

    ?>
    
    var migrationView = '<?=$curView?>';

    $(document).ready(function () {
        migrationMigrationToggleView(migrationView);

        $('#tab_cont_tab3').on('click', function(){
            var outProgress = $('#migration_progress');
            outProgress.scrollTop(outProgress.prop("scrollHeight"));
        });


        $('input[name=migration_search]').bind('keypress', function(e){
            if(e.keyCode==13){
                migrationMigrationRefresh(function(){
                    migrationEnableButtons(1);
                    $('#tab_cont_tab1').click();
                    $("html, body").scrollTop(0);
                });
            }
        });


        $('select[name=migration_config]').on('change', function(){
            migrationMigrationRefresh(function(){
                migrationEnableButtons(1);
                $('#tab_cont_tab1').click();
                $("html, body").scrollTop(0);
            });
        });

        $('.c-migration-search').on('click', function(){
            migrationMigrationRefresh(function(){
                migrationEnableButtons(1);
                $('#tab_cont_tab1').click();
                $("html, body").scrollTop(0);
            });
        });

    });
    
</script>

<? /** @noinspection PhpIncludeInspection */
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
?>
