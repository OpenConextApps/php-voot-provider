PRAGMA foreign_keys = ON;

INSERT INTO `groups` VALUES ('guests','Guests','This is a group containing Guests.');
INSERT INTO `groups` VALUES ('employees','Employees','This is a group containing Employees.');
INSERT INTO `groups` VALUES ('students','Students','This is a group containing Students.');

INSERT INTO `membership` VALUES ('fkooman', 'guests', 10);
INSERT INTO `membership` VALUES ('fkooman', 'employees', 20);
INSERT INTO `membership` VALUES ('fkooman', 'students', 50);
INSERT INTO `membership` VALUES ('john.doe', 'guests', 10);
INSERT INTO `membership` VALUES ('jane.doe', 'guests', 10);
INSERT INTO `membership` VALUES ('weird.guy', 'guests', 10);
INSERT INTO `membership` VALUES ('the.boss', 'employees', 50);
INSERT INTO `membership` VALUES ('the.house.cat', 'employees', 10);
INSERT INTO `membership` VALUES ('nerdy.guy', 'students', 10);
