$(document).ready(function () {
    var apiRoot = 'http://localhost/voot';
    var apiScopes = ["read", "oauth_whoami"];
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
                //$("#memberListModal").show();
            }
        });
    }

    function getUserId() {
        $.oajax({
            url: apiRoot + "/oauth/whoami",
            jso_provider: "voot",
            jso_scopes: apiScopes,
            jso_allowia: true,
            dataType: 'json',
            success: function (data) {
                $("#userId").html(data.id);
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
	    getUserId();
    }
    initPage();
});
