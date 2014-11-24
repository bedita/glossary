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

class FileParserComponent {
	public function parse($fileContents, $fileName) {
		$chapter = null;
		$definitionTerms = array();

		$dom = DOMDocument::loadXML($fileContents);
		if ($dom !== false) {
			// XML.
			if (!$dom->validate()) {
				throw new Exception("Failed DTD validation");
			}

			$chapter = $dom->firstChild;
			if (strtolower($chapter->nodeName) != "collection") {
				throw new Exception("Wrong XML structure");
			}

			// Parse all terms.
			$objects = $dom->getElementsByTagName("object");
			for ($i = 0; $i < $objects->length; $i++) {
				$obj = $objects->item($i);

				// Term ID.
				if (!$obj->hasAttribute("nickname")) {
					throw new Exception("Missing attribute \"nickname\" (file: {$fileName} ; line: " . $obj->getLineNo() . ")");
				}
				$nickname = $obj->getAttribute("nickname");

				// Term lang.
				$lang = null;
				if ($obj->hasAttribute("lang")) {
					$lang = $obj->getAttribute("lang");
				}

				// Term title.
				$title = $obj->getElementsByTagName("title");
				if ($title->length < 1) {
					throw new Exception("Missing tag \"title\" (file: {$fileName} ; line: " . $obj->getLineNo() . ")");
				} elseif ($title->length > 1) {
					throw new Exception("Too many tags \"title\" (file: {$fileName} ; line: " . $obj->getLineNo() . ")");
				}
				$title = $title->item(0)->textContent;

				// Term description.
				$desc = $obj->getElementsByTagName("description");
				if ($desc->length) {
					$desc = $desc->item(0)->textContent;
				} else {
					$desc = null;
				}

				// Term category(ies).
				$cat = array();
				if ($obj->hasAttribute("categories")) {
					$cat = explode(" ", $obj->getAttribute("categories"));
				}

				// Push in arrays.
				array_push($definitionTerms, array(
					'nickname' => $nickname,
					'lang' => $lang,
					'categories' => $cat,
					'title' => $title,
					'description' => $desc,
				));  // Object details.
			}
		} else {
			throw new Exception("Unsupported file format");
		}

		return $definitionTerms;
	}
}