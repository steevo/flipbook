-- 0.6.0 : sommaire des livres
ALTER TABLE `#__flipbook_books` ADD COLUMN `toc` mediumtext AFTER `description`;
ALTER TABLE `#__flipbook_books` ADD COLUMN `toc_page` int NOT NULL DEFAULT 0 AFTER `toc`;
