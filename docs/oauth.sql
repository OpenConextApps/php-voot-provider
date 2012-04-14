CREATE TABLE `AccessToken` (
  `client_id` text,
  `resource_owner_id` text,
  `issue_time` int(11) DEFAULT NULL,
  `expires_in` int(11) DEFAULT NULL,
  `scope` text,
  `access_token` text
);
CREATE TABLE `Approval` (
  `client_id` text,
  `resource_owner_id` text,
  `scope` text
);
CREATE TABLE `AuthorizeNonce` (
  `client_id` text,
  `resource_owner_id` text,
  `scope` text,
  `authorize_nonce` text
);
CREATE TABLE `Client` (
  `id` text,
  `name` text,
  `secret` text,
  `redirect_uri` text,
  `type` text
);

INSERT INTO "Client" VALUES('http://tutorial.unhosted.5apps.com/receive_token.html','5apps',NULL,'http://tutorial.unhosted.5apps.com/receive_token.html','public');
INSERT INTO "Client" VALUES('http://libredocs.org/closeDialog.html','Libre Docs',NULL,'http://libredocs.org/closeDialog.html','public');
