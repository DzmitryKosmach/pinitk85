ALTER TABLE `catalog_series`
    ADD COLUMN `admin_comment` TEXT NOT NULL COMMENT 'Комментарий админа' AFTER `name`;
