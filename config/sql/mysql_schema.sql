DROP TABLE IF EXISTS `definition_terms`;

CREATE TABLE `definition_terms` IF NOT EXISTS (
  `id` INTEGER UNSIGNED NOT NULL,
  `semantic_equivalent` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Semantic equivalent',
  PRIMARY KEY(`id`),
  FOREIGN KEY(`id`)
    REFERENCES `objects`(`id`)
      ON DELETE CASCADE
      ON UPDATE NO ACTION
) ENGINE='InnoDB' DEFAULT CHARSET='utf8' COMMENT='definition_terms';
