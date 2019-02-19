/*
Navicat MariaDB Data Transfer

Source Server         : maria
Source Server Version : 100208
Source Host           : localhost:3307
Source Database       : core

Target Server Type    : MariaDB
Target Server Version : 100208
File Encoding         : 65001

Date: 2019-02-18 19:19:08
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for auth_assignment
-- ----------------------------
DROP TABLE IF EXISTS `auth_assignment`;
CREATE TABLE `auth_assignment` (
  `item_name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`item_name`,`user_id`),
  KEY `idx-auth_assignment-user_id` (`user_id`),
  CONSTRAINT `auth_assignment_ibfk_1` FOREIGN KEY (`item_name`) REFERENCES `auth_item` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for auth_item
-- ----------------------------
DROP TABLE IF EXISTS `auth_item`;
CREATE TABLE `auth_item` (
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `type` smallint(6) NOT NULL,
  `description` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `rule_name` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `data` blob DEFAULT NULL,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`name`),
  KEY `rule_name` (`rule_name`),
  KEY `idx-auth_item-type` (`type`),
  CONSTRAINT `auth_item_ibfk_1` FOREIGN KEY (`rule_name`) REFERENCES `auth_rule` (`name`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for auth_item_child
-- ----------------------------
DROP TABLE IF EXISTS `auth_item_child`;
CREATE TABLE `auth_item_child` (
  `parent` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `child` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`parent`,`child`),
  KEY `child` (`child`),
  CONSTRAINT `auth_item_child_ibfk_1` FOREIGN KEY (`parent`) REFERENCES `auth_item` (`name`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `auth_item_child_ibfk_2` FOREIGN KEY (`child`) REFERENCES `auth_item` (`name`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for auth_rule
-- ----------------------------
DROP TABLE IF EXISTS `auth_rule`;
CREATE TABLE `auth_rule` (
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `data` blob DEFAULT NULL,
  `created_at` int(11) DEFAULT NULL,
  `updated_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for log
-- ----------------------------
DROP TABLE IF EXISTS `log`;
CREATE TABLE `log` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `userID` int(10) NOT NULL COMMENT 'User who did the action.\r\nNull is system actions',
  `code` smallint(5) NOT NULL COMMENT 'Action code.\r\nCodes are defined in excel file',
  `action` tinyint(1) NOT NULL COMMENT '\n1: Insert\r\n2: Update\r\n3: Delete',
  `model` char(15) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Table of item.\r\nNULL is reserved.',
  `modelID` int(10) DEFAULT NULL COMMENT 'Item ID\r\nNULL is reserved for system activities.',
  `values` text COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'JSON decoded values of old and new values.\r\n{''old'':[], ''new'':[]}',
  `date` date NOT NULL,
  `time` time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `UserID` (`userID`) USING BTREE,
  KEY `Code` (`code`) USING BTREE,
  KEY `Action` (`action`) USING BTREE,
  KEY `Model` (`model`) USING BTREE,
  KEY `ModelID` (`modelID`) USING BTREE,
  KEY `Date` (`date`) USING BTREE,
  KEY `Time` (`time`) USING BTREE,
  KEY `FKLog440613` (`userID`) USING BTREE,
  CONSTRAINT `log_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Audit table.\r\n! This is only about actions are done by operators or auto-system, it is not the search log.';

-- ----------------------------
-- Table structure for migration
-- ----------------------------
DROP TABLE IF EXISTS `migration`;
CREATE TABLE `migration` (
  `version` varchar(180) COLLATE utf8_persian_ci NOT NULL,
  `apply_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_persian_ci;

-- ----------------------------
-- Table structure for ugroup
-- ----------------------------
DROP TABLE IF EXISTS `ugroup`;
CREATE TABLE `ugroup` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `members` blob DEFAULT NULL COMMENT 'JSON array:\r\nU[userID] => [Status:0,1]',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `status` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `created` (`created`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='User groups';

-- ----------------------------
-- Table structure for user
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` char(255) COLLATE utf8_unicode_ci NOT NULL,
  `username` char(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` char(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Hashed\r\nIt maybe 512',
  `roleID` varchar(64) COLLATE utf8_unicode_ci DEFAULT NULL,
  `dyna` blob DEFAULT NULL,
  `created` datetime NOT NULL,
  `status` tinyint(1) NOT NULL COMMENT '-1: Suspended\r\n0: Inactive\r\n1: Active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `Username` (`username`) USING BTREE,
  KEY `Name` (`name`) USING BTREE,
  KEY `Status` (`status`) USING BTREE,
  KEY `ruleID` (`roleID`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Table of users';

-- ----------------------------
-- Table structure for userugroup
-- ----------------------------
DROP TABLE IF EXISTS `userugroup`;
CREATE TABLE `userugroup` (
  `userID` int(11) NOT NULL,
  `ugroupID` int(11) NOT NULL,
  PRIMARY KEY (`userID`,`ugroupID`),
  KEY `ugroupID` (`ugroupID`) USING BTREE,
  CONSTRAINT `userugroup_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `userugroup_ibfk_2` FOREIGN KEY (`ugroupID`) REFERENCES `ugroup` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
