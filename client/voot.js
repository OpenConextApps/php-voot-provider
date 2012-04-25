$(document).ready(function () {
    var apiRoot = 'http://localhost/voot';
    var apiScopes = ["read", "oauth_whoami"];
    jso_configure({
        "voot": {
            client_id: "voot",
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
                addClientListHandlers();
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

    function addClientListHandlers() {
        /*$("a.editClient").click(function () {
            editClient($(this).data('clientId'));
        });
        $("a.deleteClient").click(function () {
            if (confirm("Are you sure you want to delete '" + $(this).data('clientName') + "'")) {
                deleteClient($(this).data('clientId'));
            }
        });*/
    }

    function initPage() {
        renderGroupList();
	    getUserId();
    }
    initPage();
});
