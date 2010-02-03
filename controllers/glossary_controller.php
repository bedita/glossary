<?php
/*-----8<--------------------------------------------------------------------
 * 
 * BEdita - a semantic content management framework
 * 
 * Copyright 2008 ChannelWeb Srl, Chialab Srl
 * 
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the Affero GNU General Public License as published 
 * by the Free Software Foundation, either version 3 of the License, or 
 * (at your option) any later version.
 * BEdita is distributed WITHOUT ANY WARRANTY; without even the implied 
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the Affero GNU General Public License for more details.
 * You should have received a copy of the Affero GNU General Public License 
 * version 3 along with BEdita (see LICENSE.AGPL).
 * If not, see <http://gnu.org/licenses/agpl-3.0.html>.
 * 
 *------------------------------------------------------------------->8-----
 */

/**
 * glossary controller class 
 * 
 *
 * @version			$Revision: 2535 $
 * @modifiedby 		$LastChangedBy: bato $
 * @lastmodified	$LastChangedDate: 2009-12-30 10:36:21 +0100 (mer, 30 dic 2009) $
 * 
 * $Id: glossary_controller.php 2535 2009-12-30 09:36:21Z bato $
 */
class GlossaryController extends ModulesController {
	
	public $uses = array("DefinitionTerm");
	var $helpers 	= array('BeTree', 'BeToolbar');
	
	protected $moduleName = 'glossary';
	
	public function index($id = null, $order = "", $dir = true, $page = 1, $dim = 20) {
		$conf  = Configure::getInstance() ;
		$filter["object_type_id"] = array($conf->objectTypes['definition_term']["id"]);
		$filter["count_annotation"] = array("Comment","EditorNote");
		$this->paginatedList($id, $filter, $order, $dir, $page, $dim);
	}
	
	public function view($id = null) {
		$this->viewObject($this->DefinitionTerm, $id);
	}
	
	public function delete() {
		$this->checkWriteModulePermission();
		$objectsListDeleted = $this->deleteObjects("DefinitionTerm");
		$this->userInfoMessage(__("Glossary definition term deleted", true) . " -  " . $objectsListDeleted);
		$this->eventInfo("glossary definition term $objectsListDeleted deleted");
	}
	
	public function deleteSelected() {
		$this->checkWriteModulePermission();
		$objectsListDeleted = $this->deleteObjects("DefinitionTerm");
		$this->userInfoMessage(__("Glossary definition term", true) . " -  " . $objectsListDeleted);
		$this->eventInfo("glossary definition term $objectsListDeleted deleted");
	}
	
	public function save() {
		$this->checkWriteModulePermission();
		$this->Transaction->begin();
		$this->saveObject($this->DefinitionTerm);
	 	$this->Transaction->commit() ;
 		$this->userInfoMessage(__("Definition term saved", true)." - ".$this->data["title"]);
		$this->eventInfo("definition_term [". $this->data["title"]."] saved");
	}
	
	public function categories() {
		$this->showCategories($this->DefinitionTerm);
	}
	
	public function saveCategories() {
		$this->checkWriteModulePermission();
		if(empty($this->data["label"])) 
			throw new BeditaException( __("No data", true));
		$this->Transaction->begin() ;
		if(!ClassRegistry::init("Category")->save($this->data)) {
			throw new BeditaException(__("Error saving tag", true), $this->Category->validationErrors);
		}
		$this->Transaction->commit();
		$this->userInfoMessage(__("Category saved", true)." - ".$this->data["label"]);
		$this->eventInfo("category [" .$this->data["label"] . "] saved");
	}

	public function deleteCategories() {
		$this->checkWriteModulePermission();
		if(empty($this->data["id"])) 
			throw new BeditaException( __("No data", true));
		$this->Transaction->begin() ;
		if(!ClassRegistry::init("Category")->del($this->data["id"])) {
			throw new BeditaException(__("Error saving tag", true), $this->Category->validationErrors);
		}
		$this->Transaction->commit();
		$this->userInfoMessage(__("Category deleted", true) . " -  " . $this->data["label"]);
		$this->eventInfo("Category " . $this->data["id"] . "-" . $this->data["label"] . " deleted");
	}
	
	protected function forward($action, $esito) {
		$REDIRECT = array(
			"cloneObject"	=> 	array(
							"OK"	=> "/glossary/view/".@$this->DefinitionTerm->id,
							"ERROR"	=> "/glossary/view/".@$this->DefinitionTerm->id 
							),
			"view"	=> 	array(
							"ERROR"	=> "/glossary" 
							), 
			"save"	=> 	array(
							"OK"	=> "/glossary/view/".@$this->DefinitionTerm->id,
							"ERROR"	=> $this->referer()
							),
			"saveCategories" 	=> array(
							"OK"	=> "/glossary/categories",
							"ERROR"	=> "/glossary/categories"
							),
			"deleteCategories" 	=> array(
							"OK"	=> "/glossary/categories",
							"ERROR"	=> "/glossary/categories"
							),
			"delete" =>	array(
							"OK"	=> $this->fullBaseUrl . $this->Session->read('backFromView'),
							"ERROR"	=> $this->referer()
							),
			"deleteSelected" =>	array(
							"OK"	=> $this->referer(),
							"ERROR"	=> $this->referer() 
							),
			"addItemsToAreaSection"	=> 	array(
							"OK"	=> $this->referer(),
							"ERROR"	=> $this->referer() 
							),
			"changeStatusObjects"	=> 	array(
							"OK"	=> $this->referer(),
							"ERROR"	=> $this->referer() 
							)
		);
		if(isset($REDIRECT[$action][$esito])) return $REDIRECT[$action][$esito] ;
		return false ;
	}
	
}
?>