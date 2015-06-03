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
	
	public $uses = array('DefinitionTerm', 'Category');
	var $helpers 	= array('BeTree', 'BeToolbar');
	public $components = array("FileParser");
	
	protected $moduleName = 'glossary';
	protected $categorizableModels = array('DefinitionTerm');
	
	public function index($id = null, $order = "", $dir = true, $page = 1, $dim = 20) {
		$conf  = Configure::getInstance() ;
		$filter["object_type_id"] = array($conf->objectTypes['definition_term']["id"]);
		$filter["count_annotation"] = array("Comment","EditorNote");
		$this->paginatedList($id, $filter, $order, $dir, $page, $dim);
		$this->loadCategories($filter["object_type_id"]);
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

	public function import() {
	}

	public function importSave($xml = null) {
		$this->checkWriteModulePermission();

		$DefinitionTermId = Configure::read('objectTypes.definition_term.id');
		$Category = $this->Category;
		$DefinitionTerm = $this->DefinitionTerm;

		// Get XML string.
		if (empty($xml)) {
			// Uploaded file.
			if ($_FILES['source']['error'] != UPLOAD_ERR_OK) {
				throw new BeditaException(__('File upload failed', true));
			}
			$xml = file_get_contents($_FILES['source']['tmp_name']);
		}

		// Parse XML data.
		$definitionTerms = array();
		try {
			$definitionTerms = $this->FileParser->parse($xml);
		} catch (Exception $e) {
			throw new BeditaException(__($e->getMessage(), true));
		}

		// Check for duplicate nicknames.
		if (empty($this->data) || !array_key_exists('force', $this->data) || !$this->data['force']) {
			$nicknames = array();
			foreach ($definitionTerms as $term) {
				array_push($nicknames, $term['nickname']);
			}

			$duplicates = ClassRegistry::init('BEObject')->find('list', array(
				'fields' => array('BEObject.id', 'BEObject.nickname'),
				'conditions' => array('BEObject.nickname' => $nicknames)
			));

			if (count($duplicates)) {
				throw new BeditaException(__('Duplicate nicknames: ', true) . implode(', ', $duplicates));
			}
		}

		$this->Transaction->begin();

		// Categories.
		$categories = array();
		foreach ($definitionTerms as $term) {
			$categories = array_merge($categories, $term['categories']);
		}
		$categories = array_unique($categories);  // List of used categories.
		$existingCat = $Category->find('all', array('conditions' => array(
				'Category.name' => $categories,
				'Category.object_type_id' => $DefinitionTermId,
		)));
		$existingCat = Set::combine($existingCat, '{n}.id', '{n}.name');  // Search for existing categories.
		$missingCat = array_diff($categories, $existingCat);  // Find missing categories.
		foreach ($missingCat as $cat) {
			// Save missing categories.
			$this->Category->create();
			if (!$this->Category->save(array('object_type_id' => $DefinitionTermId, 'label' => $cat))) {
				throw new BeditaException(__('Error saving tag', true), $this->Category->validationErrors);
			}
			$this->eventInfo('category [' .$cat . '] saved');
			$existingCat[$this->Category->id] = $cat;

			$data = $this->Category->find('first', array('conditions' => array('id' => $this->Category->id)));
			if ($data['name'] != $cat) {
				$this->userWarnMessage("Category \"$cat\" didn't exist. It was automatically created, but name was already in use. Used \"" . $data['name'] . '" instead.');
			}
		}
		$categories = array_flip($existingCat);  // Finally, categories' list.

		// Save definition terms.
		foreach ($definitionTerms as &$term) {
			foreach ($term['categories'] as $cat) {
				$id = $categories[$cat];
				$term['Category'][$id] = $id;
			}
			unset($term['categories']);
			$this->DefinitionTerm->create();
			if (!$this->DefinitionTerm->save($term)) {
				throw new BeditaException(__('Error saving object', true), $this->DefinitionTerm->validationErrors);
			}
			$this->eventInfo('definition_term [' . $term['title'] . '] saved');
		}

		$this->Transaction->commit();
 		$this->userInfoMessage(__('Import completed', true));
	}

	public function categories() {
		$this->showCategories($this->DefinitionTerm);
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
			"importSave" => array(
							"OK"	=> "/glossary",
							"ERROR"	=> "/glossary/import"
							),
			"saveCategories" 	=> array(
							"OK"	=> "/glossary/categories",
							"ERROR"	=> "/glossary/categories"
							),
			"deleteCategories" 	=> array(
							"OK"	=> "/glossary/categories",
							"ERROR"	=> "/glossary/categories"
							),
            'bulkCategories' => array(
                'OK' => '/glossary/categories',
                'ERROR' => '/glossary/categories',
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
							),
			"assocCategory"	=> 	array(
							"OK"	=> $this->referer(),
							"ERROR"	=> $this->referer() 
							),
			"disassocCategory"	=> 	array(
							"OK"	=> $this->referer(),
							"ERROR"	=> $this->referer() 
							)
		);
		if(isset($REDIRECT[$action][$esito])) return $REDIRECT[$action][$esito] ;
		return false ;
	}
	
}
