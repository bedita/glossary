{*
** glossary view template
*}

{$html->css('/timelines/js/spectrum/spectrum.css', null, ['inline' => false])}

{if strnatcmp($conf->majorVersion, '3.3') <= 0}
    {$javascript->link('jquery/jquery.form', false)}
    {$javascript->link('jquery/jquery.selectboxes.pack', false)}
    {$javascript->link('jquery/ui/ui.sortable.min', true)}
    {$javascript->link('jquery/ui/ui.datepicker.min', false)}
    {if $currLang != 'eng'}
        {$javascript->link('jquery/ui/i18n/ui.datepicker-$currLang.js', false)}
    {/if}
    {$html->script('/timelines/js/spectrum/spectrum.js', false)}
{else}
    {$html->script('libs/jquery/jquery-migrate-1.2.1', false)} {* assure js retrocompatibility*}
    {$html->script('libs/jquery/plugins/jquery.form', false)}
    {$html->script('libs/jquery/plugins/jquery.selectboxes.pack', false)}
    {$html->script('libs/jquery/ui/jquery.ui.sortable.min', true)}
    {$html->script('/timelines/js/spectrum/spectrum.js', false)}
{/if}

{$html->script('https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.3/moment-with-locales.min.js')}

{*<script type="text/javascript">*}
{$html->scriptStart(['inline' => false])}
    $(document).ready(function() {
        openAtStart('#title, #long_desc_langs_container');
    });
{$html->scriptEnd()}
{*</script>*}

{$view->element('form_common_js')}

{$view->element('modulesmenu')}

{$view->element('menuleft', ['method' => 'view'])}

<div class="head">
    <h1>{if !empty($object)}{$object.title|default:'<i>[no title]</i>'}{else}<i>[{t}New item{/t}]</i>{/if}</h1>
</div>

{$objIndex = 0}
{$view->element('menucommands', ['method' => 'view', 'fixed' => true])}

<div class="main">
    {if $objectTypeId == $conf->objectTypes.definition_group.id}{$view->element('form_group')}{else}{$view->element('form_term')}{/if}
</div>

{$view->element('menuright')}
