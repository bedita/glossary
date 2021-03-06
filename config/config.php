<?php
/*-----8<--------------------------------------------------------------------
 *
 * BEdita - a semantic content management framework
 *
 * Copyright 2016 ChannelWeb Srl, Chialab Srl
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

$config['objRelationType'] = array(
    'definition_terms' => array(
        'hidden' => false,
        'label' => 'definition terms',
        'left' => array('definition_group'),
        'right' => array('definition_term'),
        'inverse' => 'in_definition_group',
        'inverseLabel' => 'in definition group',
    ),
    'is_equivalent_to' => array(
        'hidden' => false,
        'label' => 'is equivalent to',
        'left'   => array('definition_term'),
        'right'  => array('definition_term'),
    ),
);
