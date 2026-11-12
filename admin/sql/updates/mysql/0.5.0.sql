-- 0.5.0 : catégories, dates de publication, métadonnées
ALTER TABLE `#__flipbook_books` ADD COLUMN `catid` int unsigned NOT NULL DEFAULT 0 AFTER `folder`;
ALTER TABLE `#__flipbook_books` ADD COLUMN `publish_up` datetime NULL DEFAULT NULL AFTER `state`;
ALTER TABLE `#__flipbook_books` ADD COLUMN `publish_down` datetime NULL DEFAULT NULL AFTER `publish_up`;
ALTER TABLE `#__flipbook_books` ADD COLUMN `metakey` text AFTER `params`;
ALTER TABLE `#__flipbook_books` ADD COLUMN `metadesc` text AFTER `metakey`;
ALTER TABLE `#__flipbook_books` ADD COLUMN `metadata` text AFTER `metadesc`;
ALTER TABLE `#__flipbook_books` ADD INDEX `idx_catid` (`catid`);
ALTER TABLE `#__flipbook_books` ADD INDEX `idx_language` (`language`);
