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

INSERT INTO `Client` VALUES ('voot','Demo Client', 'This is a simple JavaScript client for demonstration purposes.', NULL,'http://localhost/voot/client/vootClient.html','public');
