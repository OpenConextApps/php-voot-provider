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
  `resource_owner_display_name` text NOT NULL,
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
  `resource_owner_id` text NOT NULL,
  `client_id` varchar(64) NOT NULL,
  `response_type` text NOT NULL,
  `redirect_uri` text,
  `scope` text NOT NULL,
  `state` text,
  PRIMARY KEY (`authorize_nonce`),
  FOREIGN KEY (`client_id`) REFERENCES `Client` (`id`)
);

CREATE TABLE `AuthorizationCode` (
  `client_id` varchar(64) NOT NULL,
  `authorization_code` varchar(64) NOT NULL,
  `redirect_uri` text,
  `access_token` varchar(64) NOT NULL,
  `issue_time` int(11) DEFAULT NULL,
  FOREIGN KEY (`client_id`) REFERENCES `Client` (`id`),
  FOREIGN KEY (`access_token`) REFERENCES `AccessToken` (`access_token`)
);

INSERT INTO `Client` VALUES ('manage', 'Management Client', 'Web application to manage OAuth client registrations.', NULL, 'http://localhost/phpvoot/manage/index.html', 'user_agent_based_application');
INSERT INTO `Client` VALUES ('voot','VOOT Demo Client', 'Simple web application to demonstrate the VOOT API.', NULL,'http://localhost/phpvoot/client/index.html','user_agent_based_application');
INSERT INTO `Client` VALUES ('webapp', 'Web Application Test', 'This client registration is for testing the authorization code grant', 's3cr3t', 'http://localhost/phpvoot/web/index.php', 'web_application');
INSERT INTO `Client` VALUES('mujina','Mujina Test Client','A simple test client available at https://mujina-sp.dev.surfconext.nl/social/social-queries.shtml','abc','https://mujina-sp.dev.surfconext.nl/social/oauth-callback.shtml','web_application');
