{$view->set('method', $method)}
<div class="primacolonna">
    {* Column heading *}
    <div class="modules">
        <label class="bedita" rel="{$html->url('/')}">{$conf->projectName|default:''}</label>
    </div>

    {* Actions menu *}
    {$actions = ['index' => $currentModule.label, 'definition_groups' => 'Definition Groups', 'categories' => 'Categories', 'import' => 'Import many glossary terms']}
    <ul class="menuleft insidecol">
        {foreach $actions as $action => $label}
        <li {if $method == $action}class="on"{/if}>
            {$tr->link($label, $html->url(['controller' => $moduleName, 'action' => $action]))}
        </li>
        {/foreach}
    </ul>

    {* Create new object *}
    {if $module_modify == 1}
    <ul class="menuleft insidecol">
        <li>
            {$tr->link('Create new glossary term', $html->url(['controller' => $moduleName, 'action' => 'view']))}
        </li>
        <li>
            {$tr->link('Create new glossary group', $html->url(['controller' => $moduleName, 'action' => 'view', 'definition_group']))}
        </li>
    </ul>
    {/if}

    {$view->element('export')}

    {* Publications' tree *}
    {if (!empty($method) && $method == 'index')}
    <div class="insidecol publishingtree">
        {$view->element('tree')}
    </div>
    {/if}

    {$view->element('previews')}

    {$view->element('user_module_perms')}
</div>
