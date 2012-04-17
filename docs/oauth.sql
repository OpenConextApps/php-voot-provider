CREATE TABLE `Client` (
  `id` varchar(64) NOT NULL,
  `name` text NOT NULL,
  `description` text NOT NULL,
  `secret` text,
  `redirect_uri` text NOT NULL,
  `type` text NOT NULL,
  PRIMARY KEY (`id`)
);
CREATE TABLE `AccessToken` (
  `access_token` varchar(64) NOT NULL,
  `client_id` varchar(64) NOT NULL,
  `resource_owner_id` text NOT NULL,
  `issue_time` int(11) DEFAULT NULL,
  `expires_in` int(11) DEFAULT NULL,
  `scope` text NOT NULL,
  PRIMARY KEY (`access_token`),
  FOREIGN KEY (`client_id`) REFERENCES `Client` (`id`)
);
CREATE TABLE `Approval` (
  `client_id` varchar(64) NOT NULL,
  `resource_owner_id` text NOT NULL,
  `scope` text NOT NULL,
  FOREIGN KEY (`client_id`) REFERENCES `Client` (`id`)
);

CREATE TABLE `AuthorizeNonce` (
  `authorize_nonce` varchar(64) NOT NULL,
  `client_id` varchar(64) NOT NULL,
  `resource_owner_id` text NOT NULL,
  `scope` text NOT NULL,
  PRIMARY KEY (`authorize_nonce`),
  FOREIGN KEY (`client_id`) REFERENCES `Client` (`id`)
);

INSERT INTO "Client" VALUES('http://tutorial.unhosted.5apps.com/receive_token.html','5apps','The 5app Developer Tutorial',NULL,'http://tutorial.unhosted.5apps.com/receive_token.html','public');
INSERT INTO "Client" VALUES('http://libredocs.org/closeDialog.html','Libre Docs','Document editing and collaboration',NULL,'http://libredocs.org/closeDialog.html','public');
INSERT INTO "Client" VALUES('http://todomvc.unhosted.5apps.com/syncer/dialog.html','Todos','Manage your TODO list',NULL,'http://todomvc.unhosted.5apps.com/syncer/dialog.html','public');
