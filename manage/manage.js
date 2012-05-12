$(document).ready(function () {
    var apiRoot = 'http://localhost/phpvoot';
    var apiScopes = ["oauth_admin", "oauth_approval", "oauth_whoami"];
    var apiClientId = 'manage';
    jso_configure({
        "admin": {
            client_id: apiClientId,
            redirect_uri: apiRoot + "/manage/index.html",
            authorization: apiRoot + "/oauth/authorize"
        }
    });
    jso_ensureTokens({
        "admin": apiScopes
    });

    function renderClientList() {
        $.oajax({
            url: apiRoot + "/oauth/client",
            jso_provider: "admin",
            jso_scopes: apiScopes,
            jso_allowia: true,
            dataType: 'json',
            success: function (data) {
                $("#clientList").html($("#clientListTemplate").render(data));
                addClientListHandlers();
            }
        });
    }

    function renderApprovalList() {
        $.oajax({
            url: apiRoot + "/oauth/approval",
            jso_provider: "admin",
            jso_scopes: apiScopes,
            jso_allowia: true,
            dataType: 'json',
            success: function (data) {
                $("#approvalList").html($("#approvalListTemplate").render(data));
                addApprovalListHandlers();
            }
        });
    }

    function getResourceOwner() {
        $.oajax({
            url: apiRoot + "/oauth/whoami",
            jso_provider: "admin",
            jso_scopes: apiScopes,
            jso_allowia: true,
            dataType: 'json',
            success: function (data) {
                $("#userId").append(data.displayName);
                $("#userId").attr('title', data.id);
            }
        });
    }

    function addClientListHandlers() {
        $("a.editClient").click(function () {
            editClient($(this).data('clientId'));
        });
        $("a.deleteClient").click(function () {
            if (confirm("Are you sure you want to delete '" + $(this).data('clientName') + "'")) {
                deleteClient($(this).data('clientId'));
            }
        });
    }

    function addApprovalListHandlers() {
        $("a.deleteApproval").click(function () {
            if (confirm("Are you sure you want to delete '" + $(this).data('clientName') + "'")) {
                deleteApproval($(this).data('clientId'));
            }
        });
    }

    function deleteClient(clientId) {
        $.oajax({
            url: apiRoot + "/oauth/client/" + clientId,
            jso_provider: "admin",
            jso_scopes: apiScopes,
            jso_allowia: true,
            type: "DELETE",
            success: function (data) {
                renderClientList();
            }
        });
    }

    function deleteApproval(clientId) {
        $.oajax({
            url: apiRoot + "/oauth/approval/" + clientId,
            jso_provider: "admin",
            jso_scopes: apiScopes,
            jso_allowia: true,
            type: "DELETE",
            success: function (data) {
                renderApprovalList();
            }
        });
    }

    function editClient(clientId) {
        if (clientId) {
            // client specified, we edit
            $.oajax({
                url: apiRoot + "/oauth/client/" + clientId,
                jso_provider: "admin",
                jso_scopes: apiScopes,
                jso_allowia: true,
                success: function (data) {
                    $("#editModal").html($("#clientEditTemplate").render(data));
                    addEditClientHandlers();
                }
            });
        } else {
            // no client specified, we add
            var data = {};
            $("#editModal").html($("#clientEditTemplate").render(data));
            addEditClientHandlers();
        }
    }

    function parseForm(formData) {
        var params = {};
        $.each(formData.serializeArray(), function (k, v) {
            params[v.name] = (v.value === '') ? null : v.value;
        });
        return JSON.stringify(params);
    }

    function addEditClientHandlers() {
        $(".icon-info-sign").popover();
        $("#editModal a.editClose").click(function () {
            $("#editModal").modal('hide');
        });
        $("#editModal a.editSave").click(function () {
            // FIXME: not really a nice way to fetch form data...
            var clientData = parseForm($('form.editClient'));
            if ($(this).data('clientId')) {
                // if clientId was available, we update
                updateClient($(this).data('clientId'), clientData);
            } else {
                // or add...
                addClient(clientData);
            }
        });
    }

    function updateClient(clientId, clientData) {
        $.oajax({
            url: apiRoot + "/oauth/client/" + clientId,
            jso_provider: "admin",
            jso_scopes: apiScopes,
            jso_allowia: true,
            type: "PUT",
            dataType: 'json',
            data: clientData,
            success: function (data) {
                $("#editModal").modal('hide');
                renderClientList();
            }
        });
    }

    function addClient(clientData) {
        $.oajax({
            url: apiRoot + "/oauth/client",
            jso_provider: "admin",
            jso_scopes: apiScopes,
            jso_allowia: true,
            type: "POST",
            dataType: 'json',
            data: clientData,
            success: function (data) {
                $("#editModal").modal('hide');
                renderClientList();
            }
        });
    }
    $("a.addClient").click(function () {
        editClient();
    });

    $("a#clientsButton").click(function() {
        $(this).parent().siblings().removeClass("active");
        $(this).parent().addClass("active");
        $("#approvedClientsList").hide();
        $("#registeredClientsList").show();
        renderClientList();
    });

    $("a#approvalsButton").click(function() {
        $(this).parent().siblings().removeClass("active");
        $(this).parent().addClass("active");
        $("#registeredClientsList").hide();
        $("#approvedClientsList").show();
        renderApprovalList();
    });

    function initPage() {
        $("#editModal").hide();
        $("#approvedClientsList").hide();
        getResourceOwner();
        renderClientList();
    }
    initPage();
});
