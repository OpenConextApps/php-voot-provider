$(document).ready(function () {
    var apiRoot = 'http://localhost/phpvoot';
    var apiScopes = ["read", "oauth_userinfo"];
    var apiClientId = 'voot';
    jso_configure({
        "voot": {
            client_id: apiClientId,
            redirect_uri: apiRoot + "/client/index.html",
            authorization: apiRoot + "/oauth/authorize"
        }
    });
    jso_ensureTokens({
        "voot": apiScopes
    });

    function renderGroupList() {
        $.oajax({
            url: apiRoot + "/groups/@me",
            jso_provider: "voot",
            jso_scopes: apiScopes,
            jso_allowia: true,
            dataType: 'json',
            success: function (data) {
                $("#groupList").html($("#groupListTemplate").render(data.entry));
                addGroupListHandlers();
            }
        });
    }

    function getGroupMembers(groupId) {
        $.oajax({
            url: apiRoot + "/people/@me/" + groupId,
            jso_provider: "voot",
            jso_scopes: apiScopes,
            jso_allowia: true,
            dataType: 'json',
            success: function (data) {
                $("#memberList").html($("#memberListTemplate").render(data.entry));
            }
        });
    }

    function getResourceOwner() {
        $.oajax({
            url: apiRoot + "/oauth/userinfo",
            jso_provider: "voot",
            jso_scopes: apiScopes,
            jso_allowia: true,
            dataType: 'json',
            success: function (data) {
                $("#userId").append(data.name);
                $("#userId").attr('title', data.user_id);
            }
        });
    }

    function addGroupListHandlers() {
        $("a.groupEntry").click(function () {
            getGroupMembers($(this).data('groupId'));
        });
    }

    function initPage() {
        $("#memberListModal").hide();
        renderGroupList();
        getResourceOwner();
    }
    initPage();
});
