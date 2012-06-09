<?php

interface IOAuthStorage {
    public function storeAccessToken       ($accessToken, $issueTime, $clientId, $resourceOwnerId, $scope, $expiry);
    public function getAccessToken         ($accessToken);
    public function storeAuthorizationCode ($authorizationCode, $resourceOwnerId, $issueTime, $clientId, $redirectUri, $scope);
    public function getAuthorizationCode   ($authorizationCode, $redirectUri);
    public function deleteAuthorizationCode($authorizationCode, $redirectUri);

    public function getRefreshToken        ($refreshToken);
    public function storeRefreshToken      ($refreshToken, $clientId, $resourceOwnerId, $scope);

    public function getResourceOwner       ($resourceOwnerId);
    public function storeResourceOwner     ($resourceOwnerId, $resourceOwnerDisplayName);

    public function getClients             ();
    public function getClient              ($clientId);

    public function addClient              ($data);
    public function updateClient           ($clientId, $data);
    public function deleteClient           ($clientId);

    public function getApprovals           ($resourceOwnerId);
    public function getApproval            ($clientId, $resourceOwnerId);
    public function addApproval            ($clientId, $resourceOwnerId, $scope);
    public function updateApproval         ($clientId, $resourceOwnerId, $scope);
    public function deleteApproval         ($clientId, $resourceOwnerId);
}

?>
