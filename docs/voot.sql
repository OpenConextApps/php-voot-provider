PRAGMA foreign_keys = ON;

CREATE TABLE `groups` (
  `id` varchar(64) NOT NULL,
  `title` varchar(64) NOT NULL,
  `description` text,
  PRIMARY KEY (`id`)
);
CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `voot_membership_role` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
);
INSERT INTO `roles` VALUES (10,'member');
INSERT INTO `roles` VALUES (20,'manager');
INSERT INTO `roles` VALUES (50,'admin');

CREATE TABLE `membership` (
  `id` varchar(64) NOT NULL,
  `groupid` varchar(64) NOT NULL,
  `role` int(11) NOT NULL,
  FOREIGN KEY (`groupid`) REFERENCES `groups` (`id`),
  FOREIGN KEY (`role`) REFERENCES `roles` (`id`)
);