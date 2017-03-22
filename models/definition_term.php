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
 * Glossary definition_term object
 */
class DefinitionTerm extends BEAppObjectModel {

	public $objectTypesGroups = array('leafs', 'related');

    public $searchFields = array('title' => 10 , 'description' => 6, 'semantic_equivalent' => 4);

    protected $modelBindings = array(
        'detailed' =>  array(
            'BEObject' => array(
                'ObjectType',
                'UserCreated',
                'UserModified',
                'Permission',
                'ObjectProperty',
                'LangText',
                'RelatedObject',
                'Annotation',
                'Category',
                'Version' => array('User.realname', 'User.userid'),
            ),
        ),
        'default' => array(
            'BEObject' => array('ObjectProperty', 'LangText', 'ObjectType', 'Annotation', 'Category', 'RelatedObject'),
        ),
        'minimum' => array('BEObject' => array('ObjectType')),
        'frontend' => array('BEObject' => array('ObjectProperty', 'LangText', 'RelatedObject', 'Category')),
    );

	/**
	 * find a glossary definition term by word
	 * try to find the lowercased word and eventually the singular or plural
	 *
	 * @param string $word word to look for
	 * @param array $options
	 *				'conditions' => CakePHP conditions used in Model::find()
	 *				'exactMatch' => true to search the exact word lowercased
	 *								false or empty [default] to search eventually singular or plural of the searched word
	 * @return mixed
	 */
	public function findByWord($word, $options = array()) {
		$title = strtolower($word);
		$conditions = array(
			'BEObject.object_type_id' => Configure::read("objectTypes.definition_term.id"),
			'lower(BEObject.title) = \'' . $title . '\'',
		);
		// if (!empty($options['conditions'])) {
		// 	$conditions = $options['conditions'];
		// }
		// $conditions['BEObject.object_type_id'] = ;
		// array_push($conditions, 'lower(BEObject.title) = \'' . $title . '\'');
		$definitionTerm = $this->find('all', array_merge_recursive($options, compact('conditions')));

		// if no result found through out exact match try to find singular or plural
		$exactMatch = (!empty($options['exactMatch']))? true : false;
		if (empty($definitionTerm) && !$exactMatch) {
			$titleSingular = Inflector::singularize($title);
			$titlePlural = Inflector::pluralize($title);
			$title = ($title == $titleSingular)? $titlePlural : $titleSingular;
			array_pop($conditions);
			array_push($conditions, 'lower(BEObject.title) = \'' . $title . '\'');
			$definitionTerm = $this->find('first', array(
				'conditions' =>	$conditions
			));
		}

		return $definitionTerm;
	}
}
