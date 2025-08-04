ALTER TABLE `reviews`
    CHANGE COLUMN `object_id` `object_id` BIGINT(20) UNSIGNED NOT NULL AFTER `id`,
    CHANGE COLUMN `date` `date` DATETIME NOT NULL AFTER `object_id`,
    CHANGE COLUMN `name` `name` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8mb3_general_ci' AFTER `date`,
    CHANGE COLUMN `email` `email` VARCHAR(192) NOT NULL DEFAULT '' COLLATE 'utf8mb3_general_ci' AFTER `name`,
    ADD COLUMN `ip` VARCHAR(255) NOT NULL DEFAULT '' AFTER `text`,
    CHANGE COLUMN `object` `object` ENUM('site','series') NOT NULL DEFAULT 'site' COLLATE 'utf8mb3_general_ci' AFTER `ip`,
    CHANGE COLUMN `rate` `rate` TINYINT(2) UNSIGNED NOT NULL AFTER `object`,
    CHANGE COLUMN `approved` `approved` TINYINT(1) UNSIGNED NOT NULL AFTER `rate_allow`;

ALTER TABLE `reviews`
    DROP INDEX `object-approved-date`,
    DROP INDEX `date`,
    DROP INDEX `object-object_id-approved-date`,
    DROP INDEX `object-object_id-rate_allow`;

ALTER TABLE `reviews`
    ADD INDEX `object_id` (`object_id`);
