{*
** XML/CSV import form
*}
{assign_associative var="params" inline="false"}
{$html->css("ui.datepicker", null, $params)}

{$view->element("modulesmenu")}

{assign_associative var="params" method="import"}
{$view->element("menuleft", $params)}

<div class="head">
    <h1>{t}Import many glossary terms{/t}</h1>
</div>

{assign var=objIndex value=0}

{assign_associative var="params" method="import" fixed=false}
{$view->element("menucommands", $params)}

<div class="main">
    <form name="import" action="{$html->url('/glossary/importSave')}" method="post" enctype="multipart/form-data">
        {$beForm->csrf()}

        <label>
            {t}Source file{/t}:
            <input type="file" name="source" />
        </label>

        <label>
            {t}Force import on duplicate nickname(s){/t}:
            <input type="checkbox" name="data[force]" value="1" />
        </label>

        <input type="submit" value="{t}import{/t}" />
    </form>
</div>

{$view->element("menuright")}