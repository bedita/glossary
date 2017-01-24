<?php
class GlossarySchema extends CakeSchema {
    var $name = 'Glossary';

    function before($event = array()) {
        return true;
    }

    function after($event = array()) {
    }

    var $definition_terms = array(
        'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 10, 'key' => 'primary'),
        'semantic_equivalent' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 255, 'collate' => 'utf8_general_ci', 'comment' => 'Semantic equivalent', 'charset' => 'utf8'),
        'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1)),
        //'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'InnoDB')
    );
}