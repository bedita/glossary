-- Create table
CREATE TABLE IF NOT EXISTS `definition_terms` (
  `id` INT(11) UNSIGNED NOT NULL,
  `semantic_equivalent` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Semantic equivalent',
  PRIMARY KEY(`id`),
  FOREIGN KEY(`id`)
    REFERENCES `objects`(`id`)
      ON DELETE CASCADE
      ON UPDATE NO ACTION
) ENGINE='InnoDB' DEFAULT CHARSET='utf8' COMMENT='definition_terms';

-- Insert rows into new table
INSERT INTO `definition_terms` (`id`)
  SELECT `objects`.`id`
  FROM `objects`
  WHERE `objects`.`object_type_id` = (SELECT `object_types`.`id` FROM `object_types` WHERE `object_types`.`name` = 'definition_term')
  ON DUPLICATE KEY UPDATE `id` = `definition_terms`.`id`;

-- Add new object type
INSERT INTO `object_types` (`id`, `name`, `module_name`)
  VALUES ((SELECT MAX(`ot`.`id`) + 1 FROM `object_types` AS `ot`), 'definition_group', 'glossary')
  ON DUPLICATE KEY UPDATE `id` = `id`;
