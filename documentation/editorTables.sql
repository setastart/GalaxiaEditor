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
  CONSTRAINT FOREIGN KEY (`_geUserId`) REFERENCES `_geUser` (`_geUserId`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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


CREATE TABLE `_geUserEmailChangeRequest` (
  `_geUserEmailChangeRequestId` int unsigned NOT NULL AUTO_INCREMENT,
  `_geUserId` int unsigned NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `emailNew` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `timestampCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`_geUserEmailChangeRequestId`),
  KEY `validate` (`_geUserId`,`token`,`timestampCreated`),
  CONSTRAINT FOREIGN KEY (`_geUserId`) REFERENCES `_geUser` (`_geUserId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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
  CONSTRAINT FOREIGN KEY (`_geUserId`) REFERENCES `_geUser` (`_geUserId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `_geUserPasswordResetRequest` (
  `_geUserPasswordResetRequestId` int unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `timestampCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`_geUserPasswordResetRequestId`),
  KEY `email` (`email`),
  KEY `search` (`token`,`email`,`timestampCreated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE `_geUserSession` (
  `_geUserSessionId` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '',
  `_geUserId` int unsigned DEFAULT NULL,
  `timestampCreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `timestampModified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sessionData` blob NOT NULL,
  PRIMARY KEY (`_geUserSessionId`),
  KEY `search` (`_geUserSessionId`,`timestampCreated`,`timestampModified`),
  KEY `search-user` (`_geUserId`),
  CONSTRAINT FOREIGN KEY (`_geUserId`) REFERENCES `_geUser` (`_geUserId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
