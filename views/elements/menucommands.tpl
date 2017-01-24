{$view->set('method', $method)}
<div class="secondacolonna {if !empty($fixed)}fixed{/if}">
    {* Column heading *}
    {$back = $session->read('backFromView')}
    {if empty($method) || $method == 'index'}
        {$back = $html->url(['controller' => $moduleName])}
    {/if}
    <div class="modules">
        <label class="{$moduleName}" rel="{$back}">{t}{$currentModule.label}{/t}</label>
    </div>

    {* Object view controls *}
    {if !empty($method) && $method == 'view' && $module_modify == 1}
    <div class="insidecol">
        <input class="bemaincommands" type="button" value=" {t}Save{/t} " name="save" id="saveBEObject" />
        <input class="bemaincommands" type="button" value=" {t}Clone{/t} " name="clone" id="cloneBEObject" />
        <input class="bemaincommands" type="button" value="{t}Delete{/t}" name="delete" id="delBEObject" />
    </div>

    {$view->element('prevnext')}
    {/if}

    {if !empty($view->action) && $view->action == "index"}
        {$view->element('select_categories')}
    {/if}
</div>
