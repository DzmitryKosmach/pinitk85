-- Основные изменения в структуру БД сайта

ALTER TABLE `articles`
    CHANGE COLUMN `a_title` `a_title` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `date`,
    CHANGE COLUMN `title` `title` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `brief`,
    CHANGE COLUMN `dscr` `dscr` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `title`,
    CHANGE COLUMN `kwrd` `kwrd` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `dscr`;

ALTER TABLE `articles`
    CHANGE COLUMN `url` `url` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Адрес'  AFTER `text`,
    DROP INDEX `url`,
    ADD UNIQUE INDEX `url` (`url`);


ALTER TABLE `catalog_categories2filters`
    ADD INDEX `filter_id` (`filter_id`);

ALTER TABLE `catalog_domains`
    CHANGE COLUMN `domain` `domain` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `id`,
    DROP INDEX `domain`,
    ADD UNIQUE INDEX `domain` (`domain`);

ALTER TABLE `catalog_filters`
    CHANGE COLUMN `name` `name` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `order`,
    CHANGE COLUMN `type` `type` ENUM('checkbox','radio') NOT NULL DEFAULT 'radio'  AFTER `name`,
    DROP INDEX `in_series_list`;

ALTER TABLE `catalog_filters_values`
    CHANGE COLUMN `value` `value` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `order`;

ALTER TABLE `catalog_items_groups`
    CHANGE COLUMN `category_id` `category_id` INT(10) UNSIGNED NOT NULL AFTER `id`,
    CHANGE COLUMN `name` `name` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `order`;

ALTER TABLE `catalog_markers`
    CHANGE COLUMN `text` `text` VARCHAR(128) NOT NULL DEFAULT ''  AFTER `padding`;

ALTER TABLE `catalog_materials`
    CHANGE COLUMN `supplier_id` `supplier_id` INT(10) UNSIGNED NOT NULL AFTER `order`,
    CHANGE COLUMN `parent_id` `parent_id` INT(10) UNSIGNED NOT NULL AFTER `supplier_id`,
    CHANGE COLUMN `name` `name` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `parent_id`;

ALTER TABLE `catalog_metall_series`
    CHANGE COLUMN `name` `name` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `id`,
    DROP INDEX `name`;

ALTER TABLE `catalog_pages`
    CHANGE COLUMN `group_id` `group_id` INT(10) UNSIGNED NOT NULL AFTER `id`,
    CHANGE COLUMN `url` `url` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `text`,
    DROP INDEX `group_id-url`;

ALTER TABLE `catalog_pages`
    CHANGE COLUMN `name` `name` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `order`,
    CHANGE COLUMN `title` `title` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `name`,
    CHANGE COLUMN `h1` `h1` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `title`,
    CHANGE COLUMN `kwrd` `kwrd` VARCHAR(192) NOT NULL DEFAULT ''  AFTER `dscr`;

ALTER TABLE `catalog_pages_filters`
    DROP INDEX `page_id`,
    ADD INDEX `page_id` (`page_id`, `value_id`) USING BTREE;

ALTER TABLE `catalog_pages_groups`
    CHANGE COLUMN `category_id` `category_id` INT(10) UNSIGNED NOT NULL AFTER `id`,
    CHANGE COLUMN `name` `name` VARCHAR(128) NOT NULL DEFAULT ''  AFTER `order`;

ALTER TABLE `catalog_search_history`
    CHANGE COLUMN `date` `date` DATETIME NOT NULL AFTER `id`,
    CHANGE COLUMN `text` `text` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `date`,
    DROP INDEX `date`,
    DROP INDEX `text-frequency`;

ALTER TABLE `catalog_series`
    CHANGE COLUMN `supplier_id` `supplier_id` INT(10) UNSIGNED NOT NULL AFTER `id`,
    CHANGE COLUMN `category_id` `category_id` INT(10) UNSIGNED NOT NULL AFTER `supplier_id`,
    CHANGE COLUMN `marker_id` `marker_id` INT(10) UNSIGNED NOT NULL AFTER `category_id`,
    DROP INDEX `order`;

ALTER TABLE `catalog_series`
    CHANGE COLUMN `name` `name` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `order`,
    CHANGE COLUMN `title` `title` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `keywords`,
    CHANGE COLUMN `h1` `h1` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `title`,
    CHANGE COLUMN `kwrd` `kwrd` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `dscr`,
    CHANGE COLUMN `video` `video` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `text`,
    CHANGE COLUMN `url` `url` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `video`;

ALTER TABLE `catalog_series_linkage`
    CHANGE COLUMN `name` `name` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `order`,
    CHANGE COLUMN `color_name` `color_name` VARCHAR(32) NOT NULL DEFAULT ''  AFTER `name`;

ALTER TABLE `catalog_series_options`
    CHANGE COLUMN `series_id` `series_id` INT(10) UNSIGNED NOT NULL AFTER `id`,
    CHANGE COLUMN `name` `name` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `order`,
    DROP INDEX `value`,
    DROP INDEX `name`;

ALTER TABLE `catalog_series_photos`
    CHANGE COLUMN `series_id` `series_id` INT(10) UNSIGNED NOT NULL AFTER `id`,
    CHANGE COLUMN `alt` `alt` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `order`;

ALTER TABLE `catalog_suppliers`
    CHANGE COLUMN `name` `name` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `id`,
    CHANGE COLUMN `fio` `fio` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `description`,
    CHANGE COLUMN `phone` `phone` VARCHAR(128) NOT NULL DEFAULT ''  AFTER `fio`,
    CHANGE COLUMN `email` `email` VARCHAR(192) NOT NULL DEFAULT ''  AFTER `phone`,
    CHANGE COLUMN `discount` `discount` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `email`,
    CHANGE COLUMN `delivery` `delivery` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `discount`,
    CHANGE COLUMN `assembly` `assembly` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `delivery`,
    DROP INDEX `name`;

ALTER TABLE `catalog_usdcourses`
    CHANGE COLUMN `name` `name` VARCHAR(128) NOT NULL DEFAULT ''  AFTER `id`,
    DROP INDEX `name`;

ALTER TABLE `catalog_yml_files`
    CHANGE COLUMN `file` `file` VARCHAR(128) NOT NULL DEFAULT ''  AFTER `id`;

ALTER TABLE `clients_letters`
    DROP INDEX `order`;

ALTER TABLE `clients_projects`
    CHANGE COLUMN `name` `name` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `date`,
    CHANGE COLUMN `city` `city` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `name`,
    CHANGE COLUMN `title` `title` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `city`,
    CHANGE COLUMN `dscr` `dscr` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `title`,
    CHANGE COLUMN `kwrd` `kwrd` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `dscr`,
    CHANGE COLUMN `url` `url` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `text`,
    CHANGE COLUMN `order` `order` INT(10) UNSIGNED NOT NULL AFTER `url`,
    DROP INDEX `url`,
    DROP INDEX `in_index-order`,
    DROP INDEX `order`,
    ADD UNIQUE INDEX `url` (`url`);

ALTER TABLE `clients_projects_pics`
    CHANGE COLUMN `project_id` `project_id` INT(10) UNSIGNED NOT NULL AFTER `id`,
    CHANGE COLUMN `alt` `alt` VARCHAR(128) NOT NULL DEFAULT ''  AFTER `type`;

ALTER TABLE `dealers`
    CHANGE COLUMN `name` `name` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `regdate`,
    CHANGE COLUMN `email` `email` VARCHAR(192) NOT NULL DEFAULT ''  AFTER `name`,
    CHANGE COLUMN `login` `login` VARCHAR(64) NOT NULL DEFAULT ''  AFTER `email`,
    CHANGE COLUMN `pass` `pass` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `login`,
    DROP INDEX `email`,
    DROP INDEX `login`,
    DROP INDEX `regdate`,
    ADD UNIQUE INDEX `email` (`email`),
    ADD UNIQUE INDEX `login` (`login`);

ALTER TABLE `dealers_offers`
    CHANGE COLUMN `dealer_id` `dealer_id` INT(10) UNSIGNED NOT NULL AFTER `id`,
    CHANGE COLUMN `cart` `cart` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `date`,
    CHANGE COLUMN `discounts` `discounts` VARCHAR(64) NOT NULL DEFAULT ''  AFTER `cart`,
    CHANGE COLUMN `options` `options` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `amount`,
    DROP INDEX `date`,
    DROP INDEX `saved-date`,
    DROP INDEX `dealer_id-saved-date`,
    ADD INDEX `dealer_id` (`dealer_id`);

ALTER TABLE `email_queue`
    CHANGE COLUMN `subj` `subj` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `id`,
    CHANGE COLUMN `to` `to` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `subj`,
    CHANGE COLUMN `from` `from` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `to`,
    CHANGE COLUMN `from_name` `from_name` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `from`;

ALTER TABLE `email_tpl`
    CHANGE COLUMN `label` `label` VARCHAR(128) NOT NULL DEFAULT ''  AFTER `id`,
    CHANGE COLUMN `name` `name` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `label`,
    CHANGE COLUMN `subj` `subj` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `desc`,
    CHANGE COLUMN `from` `from` VARCHAR(192) NOT NULL DEFAULT ''  AFTER `subj`,
    CHANGE COLUMN `from_name` `from_name` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `from`,
    DROP INDEX `label`,
    ADD UNIQUE INDEX `label` (`label`);

ALTER TABLE `news`
    CHANGE COLUMN `url` `url` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `text`,
    DROP INDEX `date`,
    DROP INDEX `url`,
    ADD UNIQUE INDEX `url` (`url`);

ALTER TABLE `news`
    CHANGE COLUMN `n_title` `n_title` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `date`,
    CHANGE COLUMN `title` `title` VARCHAR(255) NOT NULL DEFAULT '0'  AFTER `brief`,
    CHANGE COLUMN `kwrd` `kwrd` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `dscr`;

ALTER TABLE `options`
    CHANGE COLUMN `name` `name` VARCHAR(128) NOT NULL DEFAULT ''  AFTER `id`,
    CHANGE COLUMN `dsc` `dsc` VARCHAR(255) NOT NULL DEFAULT '0'  AFTER `value`,
    DROP INDEX `name`;

ALTER TABLE `orders`
    CHANGE COLUMN `status_date` `status_date` DATETIME NOT NULL AFTER `date`,
    CHANGE COLUMN `code` `code` VARCHAR(64) NOT NULL DEFAULT ''  AFTER `status_date`,
    CHANGE COLUMN `options` `options` TEXT NOT NULL  AFTER `amount`,
    CHANGE COLUMN `paymethod` `paymethod` ENUM('no','cash','bank','card') NOT NULL DEFAULT 'no'  AFTER `user`,
    CHANGE COLUMN `status_name` `status_name` VARCHAR(192) NOT NULL DEFAULT ''  AFTER `status_id`,
    CHANGE COLUMN `status_color` `status_color` VARCHAR(32) NOT NULL DEFAULT ''  AFTER `status_name`,
    DROP INDEX `in_archive-date`;

ALTER TABLE `orders_options`
    CHANGE COLUMN `name` `name` VARCHAR(128) NOT NULL DEFAULT ''  AFTER `id`,
    DROP INDEX `name`;

ALTER TABLE `orders_statuses`
    CHANGE COLUMN `name` `name` VARCHAR(128) NOT NULL DEFAULT ''  AFTER `order`,
    CHANGE COLUMN `color` `color` VARCHAR(32) NOT NULL DEFAULT ''  AFTER `name`;

ALTER TABLE `pages`
    CHANGE COLUMN `url` `url` VARCHAR(128) NOT NULL DEFAULT ''  AFTER `seo_hide`,
    DROP INDEX `url-parent_id`;

ALTER TABLE `pages`
    CHANGE COLUMN `parent_id` `parent_id` INT(10) UNSIGNED NOT NULL AFTER `id`,
    CHANGE COLUMN `alias` `alias` TEXT NOT NULL  AFTER `url`,
    CHANGE COLUMN `seo_hide` `seo_hide` TINYINT(3) UNSIGNED NOT NULL AFTER `module`,
    CHANGE COLUMN `admin` `admin` TINYINT(3) UNSIGNED NOT NULL AFTER `seo_hide`,
    CHANGE COLUMN `in_sitemap` `in_sitemap` TINYINT(3) UNSIGNED NOT NULL AFTER `spec`,
    CHANGE COLUMN `in_menu1` `in_menu1` TINYINT(3) UNSIGNED NOT NULL AFTER `in_sitemap`,
    CHANGE COLUMN `in_menu2` `in_menu2` TINYINT(3) UNSIGNED NOT NULL AFTER `in_menu1`,
    CHANGE COLUMN `in_menu1_hightlight` `in_menu1_hightlight` TINYINT(3) UNSIGNED NOT NULL AFTER `in_menu2`,
    DROP INDEX `alias`,
    DROP INDEX `in_sitemap-order`;

ALTER TABLE `pages_http_headers`
    CHANGE COLUMN `url` `url` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `id`,
    CHANGE COLUMN `redirect` `redirect` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `url`,
    CHANGE COLUMN `code` `code` ENUM('301','404') NOT NULL DEFAULT '301'  AFTER `redirect`,
    DROP INDEX `url`,
    ADD UNIQUE INDEX `url` (`url`);

ALTER TABLE `slider_index`
    CHANGE COLUMN `alt` `alt` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `id`,
    CHANGE COLUMN `link` `link` VARCHAR(255) NOT NULL DEFAULT ''  AFTER `alt`,
    CHANGE COLUMN `order` `order` INT(10) UNSIGNED NOT NULL AFTER `link`,
    DROP INDEX `active-order`;
