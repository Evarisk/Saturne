CREATE TABLE IF NOT EXISTS llx_links_extrafields
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  tms                       timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fk_object                 integer NOT NULL,
  import_key                varchar(14)                             -- import key
) ENGINE=innodb;

ALTER TABLE llx_links_extrafields ADD INDEX idx_links_extrafields (fk_object);