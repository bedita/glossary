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
 *
 * @version			$Revision: 2534 $
 * @modifiedby 		$LastChangedBy: bato $
 * @lastmodified	$LastChangedDate: 2009-12-29 13:23:14 +0100 (mar, 29 dic 2009) $
 * 
 * $Id: definition_term.php 2534 2009-12-29 12:23:14Z bato $
 */

class DefinitionTerm extends BeditaObjectModel {
			
	public $objectTypesGroups = array("leafs", "related");

	public $actsAs = array();

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
		$exactMatch = (!empty($options['exactMatch']))? true : false;
		if (!empty($options['conditions'])) {
			$conditions = $options['conditions'];
		}
		$conditions['BEObject.object_type_id'] = Configure::read("objectTypes.definition_term.id");
		array_push($conditions, 'lower(BEObject.title) = \'' . $title . '\'');
		$definitionTerm = $this->find('first', array(
			'conditions' =>	$conditions
		));
		
		// if no result found through out exact match try to find singular or plural
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
?>
