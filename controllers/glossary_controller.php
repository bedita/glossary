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
 */
class GlossaryController extends ModulesController {

    /**
     * Module name.
     *
     * @var string
     */
    public $name = 'Glossary';

    /**
     * Models used by this controller.
     *
     * @var array
     */
    public $uses = array('DefinitionTerm', 'DefinitionGroup', 'Category');

    /**
     * Helpers.
     *
     * @var array
     */
    public $helpers = array('BeTree', 'BeToolbar', 'ImageInfo');

    /**
     * Components.
     *
     * @var array
     */
    public $components = array(
        'BeFileHandler',
        'BeSecurity' => array(
            'disableActions' => array('loadObjectToAssoc'),
        ),
        'BeUploadToObj',
        'FileParser',
    );

    /**
     * BEdita module name.
     *
     * @var string
     */
    protected $moduleName = 'glossary';

    /**
     * Models categorizable within this controller.
     *
     * @var array
     */
    protected $categorizableModels = array('DefinitionTerm');

    /**
     * View a list of all Definition Terms.
     *
     * @param $id int
     * @param $order string
     * @param $dir boolean
     * @param $page int
     * @param $dim int
     */
    public function index($id = null, $order = '', $dir = true, $page = 1, $dim = 20) {
        $filter['object_type_id'] = Configure::read('objectTypes.definition_term.id');
        $filter['count_annotation'] = array('Comment', 'EditorNote');

        $this->paginatedList($id, $filter, $order, $dir, $page, $dim);
        $this->loadCategories($filter['object_type_id']);
    }

    /**
     * View a list of all Definition Groups.
     *
     * @param $id int
     * @param $order string
     * @param $dir boolean
     * @param $page int
     * @param $dim int
     */
    public function definition_groups($id = null, $order = '', $dir = true, $page = 1, $dim = 20) {
        $filter['object_type_id'] = Configure::read('objectTypes.definition_group.id');
        $filter['count_annotation'] = array('Comment', 'EditorNote');

        $this->paginatedList($id, $filter, $order, $dir, $page, $dim);
        $this->loadCategories($filter['object_type_id']);

        $this->render('index');
    }

    /**
     * View a DefinitionTerm or a DefinitionGroup.
     *
     * @param $id int|null
     */
    public function view($id = null) {
        $Model = $this->DefinitionTerm;
        $modelName = 'definition_term';
        if ($id === 'definition_group') {
            $id = null;
            $Model = $this->DefinitionGroup;
            $modelName = 'definition_group';
        } elseif (ClassRegistry::init('BEObject')->findObjectTypeId($id) === Configure::read('objectTypes.definition_group.id')) {
            $Model = $this->DefinitionGroup;
            $modelName = 'definition_group';
        }

        $this->viewObject($Model, $id);
        $this->set('objectTypeId', Configure::read("objectTypes.{$modelName}.id"));
    }

    /**
     * Save a DefinitionTerm or a DefinitionGroup.
     */
    public function save() {
        $modelName = 'definition_term';
        $objectTypeId = $this->data['object_type_id'];
        if (!empty($this->data['id'])) {
            $objectTypeId = ClassRegistry::init('BEObject')->findObjectTypeId($this->data['id']);
        }
        if ($objectTypeId === Configure::read('objectTypes.definition_group.id')) {
            $modelName = 'definition_group';
        }

        $this->checkWriteModulePermission();
        $this->Transaction->begin();
        if ($modelName === 'definition_group') {
            $this->savePlan();
        } else {
            $this->data['coords'] = array(array(0, 0), array(0, 10), array(10, 0));
            $this->saveObject($this->DefinitionTerm);
        }
        $this->Transaction->commit();
        $this->userInfoMessage(__($modelName . ' saved', true) . ' - ' . $this->data['title']);
        $this->eventInfo("{$modelName} [{$this->data['title']}] saved");
    }

    /**
     * Method to save a DefinitionGroup.
     *
     * @return void
     * @throws BeditaException Throws an exception on save error.
     */
    protected function savePlan() {
        if (empty($this->data)) {
            throw new BeditaException(__('No data', true));
        }

        $new = empty($this->data['id']);

        if (!$new) {
            $this->checkObjectWritePermission($this->data['id']);
        }

        $this->prepareRelationsToSave();

        // Format custom properties
        $this->BeCustomProperty->setupForSave();

        // save data
        if (!empty($this->params['form']['tags'])) {
            $this->data['Category'] = ClassRegistry::init('Category')->saveTagList($this->params['form']['tags']);
        }

        if (!empty($this->params['form']['Filedata']['name'])) {
            unset($this->data['url']);

            $this->params['form']['forceupload'] = true;

            $this->data['id'] = $this->BeUploadToObj->upload($this->data);
            $this->DefinitionGroup->Behaviors->disable('ForeignDependenceSave');
            if (!$this->DefinitionGroup->save($this->data)) {
                throw new BeditaException(__('Error saving definition_group', true), $this->DefinitionGroup->validationErrors);
            }
            $this->DefinitionGroup->Behaviors->enable('ForeignDependenceSave');
            $BEObject = ClassRegistry::init('BEObject');
            $BEObject->id = $this->DefinitionGroup->id;
            $BEObject->saveField('object_type_id', Configure::read('objectTypes.definition_group.id'));
        } elseif (!empty($this->data['url'])) {
            $this->DefinitionGroup->id = $this->BeUploadToObj->uploadFromURL($this->data, true);
        } else {
            unset($this->data['url']);

            if (!isset($this->data['Permission'])) {
                $this->data['Permission'] = array();
            }

            if (!$this->DefinitionGroup->save($this->data)) {
                throw new BeditaException(__('Error saving definition_group', true), $this->DefinitionGroup->validationErrors);
            }
        }

        $this->data['id'] = $this->DefinitionGroup->id;

        if (isset($this->data['destination'])) {
            if (!$new) {
                $this->BeTree->setupForSave($this->DefinitionGroup->id, $this->data['destination']);
            }
            ClassRegistry::init('Tree')->updateTree($this->DefinitionGroup->id, $this->data['destination']);
        }
    }

    /**
     * Delete a DefinitionTerm or a DefinitionGroup.
     */
    public function delete() {
        $Model = $this->DefinitionTerm;
        $modelName = 'definition_term';
        $objectTypeId = ClassRegistry::init('BEObject')->findObjectTypeId($this->data['id']);
        if ($objectTypeId === Configure::read('objectTypes.definition_group.id')) {
            $Model = $this->DefinitionGroup;
            $modelName = 'definition_group';
        }

        $this->checkWriteModulePermission();
        $objectsListDeleted = $this->deleteObjects($Model->name);
        $this->userInfoMessage(__($Model->name . ' deleted', true) . ' - ' . $objectsListDeleted);
        $this->eventInfo("{$modelName} {$objectsListDeleted} deleted");
    }

    /**
     * Delete multiple Definition Terms or Definition Groups.
     */
    public function deleteSelected() {
        $Model = $this->DefinitionTerm;
        $modelName = 'definition_term';
        $objectTypeId = ClassRegistry::init('BEObject')
            ->findObjectTypeId(current($this->params['form']['objects_selected']));
        if ($objectTypeId === Configure::read('objectTypes.definition_group.id')) {
            $Model = $this->DefinitionGroup;
            $modelName = 'definition_group';
        }

        $this->checkWriteModulePermission();
        $objectsListDeleted = $this->deleteObjects($Model->name);
        $this->userInfoMessage(__($Model->name . 's deleted', true) . ' - ' . $objectsListDeleted);
        $this->eventInfo("{$modelName}s {$objectsListDeleted} deleted");
    }

    /**
     * Shows Definition Terms categories.
     */
    public function categories() {
        $this->showCategories($this->DefinitionTerm);
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

    /**
     * After-action redirect.
     *
     * @param string $action Controller action.
     * @param string $status Either `OK` or `ERROR`.
     * @return string|bool Redirect URL or `false`.
     */
    protected function forward($action, $status) {
        $id = null;
        if (!empty($this->DefinitionTerm->id)) {
            $id = $this->DefinitionTerm->id;
        } elseif (!empty($this->DefinitionGroup->id)) {
            $id = $this->DefinitionGroup->id;
        }

        $REDIRECT = array(
            'view' => array(
                'ERROR' => '/' . $this->moduleName,
            ),
            'save' => array(
                'OK' => '/' . $this->moduleName . '/view/' . $id,
                'ERROR' => $this->referer(),
            ),
            'delete' => array(
                'OK' => $this->fullBaseUrl . $this->Session->read('backFromView'),
                'ERROR' => $this->referer(),
            ),
            'update' => array(
                'OK' => '/events',
                'ERROR' => $this->referer(),
            ),
            'deleteSelected' => array(
                'OK' => $this->referer(),
                'ERROR' => $this->referer(),
            ),
            'importSave' => array(
                'OK' => '/' . $this->moduleName,
                'ERROR' => '/' . $this->moduleName . '/import',
            ),

            'addItemsToAreaSection' => array(
                'OK' => $this->referer(),
                'ERROR' => $this->referer(),
            ),
            'changeStatusObjects' => array(
                'OK' => $this->referer(),
                'ERROR' => $this->referer(),
            ),
            'cloneObject' => array(
                'OK' => '/' . $this->moduleName . '/view/' . $id,
                'ERROR' => '/' . $this->moduleName . '/view/' . $id,
            ),

            'deleteCategories' => array(
                'OK' => '/' . $this->moduleName . '/categories',
                'ERROR' => '/' . $this->moduleName . '/categories',
            ),
            'bulkCategories' => array(
                'OK' => '/' . $this->moduleName . '/categories',
                'ERROR' => '/' . $this->moduleName . '/categories',
            ),
        );

        if (isset($REDIRECT[$action][$status])) {
            return $REDIRECT[$action][$status];
        }

        return false;
    }
}
