{$view->element('texteditor')}

{* title and description *}

{assign var="newtitle" value=$html->params.named.title|default:''}
<div class="tab"><h2>{t}Title{/t}</h2></div>

<fieldset id="title">

	<label>{t}title{/t}:</label>
	<br />
	<input type="text" name="data[title]" value="{$object.title|escape:'html'|escape:'quotes'|default:$newtitle}" id="titleBEObject" />
	<br />
	<label>{t}description{/t}:</label>
	<br />
	<textarea class="richtextSimple subtitle" style="height:280px" name="data[description]">{$object.description|default:''|escape:'html'}</textarea>
    <br />

    <label>{t}semantic equivalent{/t}:</label>
    <input type="text" name="data[semantic_equivalent]" value="{$object.semantic_equivalent|escape:'html'|escape:'quotes'}"></ins>

</fieldset>