{if strnatcmp($conf->majorVersion, '3.3') > 0}
    {$html->script('libs/jquery/jquery-migrate-1.2.1', false)} {* assure js retrocompatibility *}
{/if}


{$view->element("modulesmenu")}

{assign_associative var="params" method="index"}
{$view->element("menuleft", $params)}

{assign_associative var="params" method="index"}
{$view->element("menucommands", $params)}

{$view->element("toolbar")}



<div class="mainfull">

    {* BE > 3.3 *}
    {if strnatcmp($conf->majorVersion, '3.3') > 0}
        {$view->element("filters")}
    {/if}

	{$view->element("list_objects")}


</div>

