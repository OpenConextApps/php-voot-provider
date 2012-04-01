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
  `secret` text,
  `redirect_uri` text,
  `type` text
);
INSERT INTO `Client` VALUES ('voot',NULL,'http://localhost/voot/client/vootClient.html','public');
