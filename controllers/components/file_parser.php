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
			$chapter = $dom->firstChild;
			if (strtolower($chapter->nodeName) != "capitolo") {
				throw new Exception("Wrong XML structure");
			}

			// Parse all terms.
			$boxes = $dom->getElementsByTagName("box");
			for ($i = 0; $i < $boxes->length; $i++) {
				$box = $boxes->item($i);

				// Term ID.
				if (!$box->hasAttribute("id")) {
					throw new Exception("Missing attribute \"id\" (file: {$fileName} ; line: " . $box->getLineNo() . ")");
				}
				$id = $box->getAttribute("id");

				// Term lang.
				$lang = null;
				if ($box->hasAttribute("lang")) {
					$lang = $box->getAttribute("lang");
				}

				// Term title.
				$title = $box->getElementsByTagName("titolo");
				if ($title->length < 1) {
					throw new Exception("Missing tag \"title\" (file: {$fileName} ; line: " . $box->getLineNo() . ")");
				} elseif ($title->length > 1) {
					throw new Exception("Too many tags \"title\" (file: {$fileName} ; line: " . $box->getLineNo() . ")");
				}
				$title = $title->item(0)->nodeValue;

				// Term description.
				$desc = $box->getElementsByTagName("capoverso");
				if ($desc->length) {
					$desc = preg_replace("/^<capoverso.*?>|<\/capoverso>$/i", "", $dom->saveXML($desc->item(0)));
				} else {
					$desc = null;
				}

				// Term category(ies).
				$cat = array();
				if ($box->hasAttribute("categoria")) {
					$cat = explode(" ", $box->getAttribute("categoria"));
				}

				// Push in arrays.
				array_push($definitionTerms, array(
					'nickname' => $id,
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