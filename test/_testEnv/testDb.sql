DROP DATABASE IF EXISTS _galaxiaEditorTest;
CREATE DATABASE IF NOT EXISTS _galaxiaEditorTest;
USE _galaxiaEditorTest;

-- _geUser: table
CREATE TABLE `_geUser` (
  `_geUserId` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `passwordHash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `perms` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestampLastOnline` timestamp NULL DEFAULT NULL,
  `timestampCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `timestampModified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`_geUserId`),
  UNIQUE KEY `unique` (`email`),
  KEY `search` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- _geHistory: table
CREATE TABLE `_geHistory` (
  `_geHistoryId` int unsigned NOT NULL AUTO_INCREMENT,
  `_geUserId` int unsigned DEFAULT NULL,
  `uniqueId` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` tinyint DEFAULT NULL COMMENT '0: deleted / 2: modified / 3: created',
  `tabName` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `tabId` int NOT NULL,
  `fieldKey` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `inputKey` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `timestampCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`_geHistoryId`),
  KEY `search` (`uniqueId`,`tabName`,`tabId`,`fieldKey`,`inputKey`),
  KEY `browse` (`timestampCreated`),
  KEY `userId` (`_geUserId`),
  CONSTRAINT `_geHistory_ibfk_1` FOREIGN KEY (`_geUserId`) REFERENCES `_geUser` (`_geUserId`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- _geUserEmailChangeRequest: table
CREATE TABLE `_geUserEmailChangeRequest` (
  `_geUserEmailChangeRequestId` int unsigned NOT NULL AUTO_INCREMENT,
  `_geUserId` int unsigned NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `emailNew` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `timestampCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`_geUserEmailChangeRequestId`),
  KEY `validate` (`_geUserId`,`token`,`timestampCreated`),
  CONSTRAINT `_geUserEmailChangeRequest_ibfk_1` FOREIGN KEY (`_geUserId`) REFERENCES `_geUser` (`_geUserId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- _geUserOption: table
CREATE TABLE `_geUserOption` (
  `_geUserOptionId` int unsigned NOT NULL AUTO_INCREMENT,
  `_geUserId` int unsigned NOT NULL,
  `fieldKey` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '',
  `position` int unsigned NOT NULL DEFAULT '0',
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `timestampCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `timestampModified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`_geUserOptionId`),
  UNIQUE KEY `unique` (`_geUserId`,`fieldKey`),
  CONSTRAINT `_geUserOption_ibfk_1` FOREIGN KEY (`_geUserId`) REFERENCES `_geUser` (`_geUserId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- _geUserPasswordResetRequest: table
CREATE TABLE `_geUserPasswordResetRequest` (
  `_geUserPasswordResetRequestId` int unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `timestampCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`_geUserPasswordResetRequestId`),
  KEY `email` (`email`),
  KEY `search` (`token`,`email`,`timestampCreated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- _geUserRegisterRequest: table
CREATE TABLE `_geUserRegisterRequest` (
  `_geUserRegisterRequestId` int unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `passwordHash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `timestampCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`_geUserRegisterRequestId`),
  KEY `search` (`token`,`email`,`timestampCreated`),
  KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- _geUserSession: table
CREATE TABLE `_geUserSession` (
  `_geUserSessionId` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `_geUserId` int unsigned DEFAULT NULL,
  `timestampCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `timestampModified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sessionData` blob NOT NULL,
  PRIMARY KEY (`_geUserSessionId`),
  KEY `search` (`_geUserSessionId`,`timestampCreated`,`timestampModified`),
  KEY `search-user` (`_geUserId`),
  CONSTRAINT `_geUserSession_ibfk_1` FOREIGN KEY (`_geUserId`) REFERENCES `_geUser` (`_geUserId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- page: table
CREATE TABLE `page` (
  `pageId` int unsigned NOT NULL AUTO_INCREMENT,
  `pageStatus` tinyint NOT NULL DEFAULT '1' COMMENT '0: deleted / 1: draft / 2: special / 3: published',
  `pageType` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `pageSlug_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `pageSlug_es` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `pageSlug_pt` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `pageTitle_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `pageTitle_es` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `pageTitle_pt` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `position` int unsigned NOT NULL DEFAULT '0',
  `timestampCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `timestampModified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pageId`),
  UNIQUE KEY `unique-en` (`pageSlug_en`) USING BTREE,
  UNIQUE KEY `unique-es` (`pageSlug_es`) USING BTREE,
  UNIQUE KEY `unique-pt` (`pageSlug_pt`) USING BTREE,
  KEY `browse` (`pageStatus`,`timestampCreated`),
  KEY `search-en` (`pageStatus`,`pageSlug_en`),
  KEY `search-es` (`pageStatus`,`pageSlug_es`),
  KEY `search-pt` (`pageStatus`,`pageSlug_pt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- pageContent: table
CREATE TABLE `pageContent` (
  `pageContentId` int unsigned NOT NULL AUTO_INCREMENT,
  `pageId` int unsigned NOT NULL,
  `pageContentField` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `pageContentValue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `pageContentValue_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `pageContentValue_es` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `pageContentValue_pt` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `position` int unsigned NOT NULL DEFAULT '0',
  `timestampCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `timestampModified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pageContentId`),
  KEY `search` (`pageId`),
  CONSTRAINT `rel-pagesFields-pages` FOREIGN KEY (`pageId`) REFERENCES `page` (`pageId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- pageImage: table
CREATE TABLE `pageImage` (
  `pageImageId` int unsigned NOT NULL AUTO_INCREMENT,
  `pageId` int unsigned NOT NULL,
  `pageImageField` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `imgSlug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `position` int unsigned NOT NULL DEFAULT '0',
  `timestampCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `timestampModified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pageImageId`),
  KEY `pageId` (`pageId`) USING BTREE,
  CONSTRAINT `pageImage_ibfk_1` FOREIGN KEY (`pageId`) REFERENCES `page` (`pageId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- pageRedirect: table
CREATE TABLE `pageRedirect` (
  `pageRedirectId` int unsigned NOT NULL AUTO_INCREMENT,
  `pageId` int unsigned DEFAULT NULL,
  `fieldKey` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pageRedirectSlug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `position` int unsigned NOT NULL DEFAULT '0',
  `timestampLastAccessed` timestamp NULL DEFAULT NULL,
  `timestampCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `timestampModified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pageRedirectId`),
  UNIQUE KEY `unique` (`pageRedirectSlug`),
  KEY `browse` (`timestampCreated`),
  KEY `pageId` (`pageId`),
  CONSTRAINT `pageRedirect_ibfk_1` FOREIGN KEY (`pageId`) REFERENCES `page` (`pageId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

