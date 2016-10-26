{*
** document form template
*}
<form action="{$html->url(['controller' => $currentModule.url, 'action' => 'save'])}" method="post" name="updateForm" id="updateForm" class="cmxform">
    <input type="hidden" name="data[id]" value="{$object.id|default:''}"/>
    <input type="hidden" name="data[object_type_id]" value="{$conf->objectTypes.definition_group.id}" />

    {$beForm->csrf()}

    {$view->element('form_title_subtitle')}

    {$view->element('form_properties', ['comments' => true])}

    {$view->element('form_tree')}

    {$view->element('form_textbody', ['height' => 500])}

    {$view->element('form_assoc_objects', ['object_type_id' => $conf->objectTypes.definition_group.id])}

    {$view->element('form_categories')}

    {$view->element('form_tags')}

    {$view->element('form_links')}

    {$view->element('form_geotag')}

    {$view->element('form_translations')}

    {$view->element('form_advanced_properties', ['el' => $object])}

    {$view->element('form_custom_properties')}

    {$view->element('form_permissions', ['el' => $object, 'recursion' => true])}

    {$view->element('form_versions')}

    {$view->element('form_previews')}
</form>

{$view->element('form_print')}
