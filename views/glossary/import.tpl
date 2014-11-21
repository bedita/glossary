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
		<label>
			{t}Source file{/t}:
			<input type="file" name="source" />
		</label>
		
		<input type="submit" value="{t}find it{/t}" />
	</form>
</div>

{$view->element("menuright")}