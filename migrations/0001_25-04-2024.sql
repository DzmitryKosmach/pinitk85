ALTER TABLE `administrators`
    CHANGE COLUMN `login` `login` VARCHAR(128) NOT NULL DEFAULT '' COLLATE 'utf8mb3_general_ci' AFTER `id`,
    CHANGE COLUMN `pass` `pass` VARCHAR(128) NOT NULL DEFAULT '' COLLATE 'utf8mb3_general_ci' AFTER `login`,
    ADD COLUMN `token` VARCHAR(128) NOT NULL DEFAULT '' COMMENT 'Токен пользователя' AFTER `pass`;

ALTER TABLE `administrators`
    ADD COLUMN `password` VARCHAR(255) NOT NULL DEFAULT '' AFTER `login`;

ALTER TABLE `administrators` DROP `pass`;

ALTER TABLE `administrators`
    DROP INDEX `login`,
    ADD UNIQUE INDEX `login` (`login`);
