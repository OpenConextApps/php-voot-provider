$(document).ready(function () {

    var apiRoot = 'http://localhost/voot';

    jso_configure({
        "manage": {
            client_id: "manage",
            redirect_uri: apiRoot + "/manage/index.html",
            authorization: apiRoot + "/oauth/authorize"
        }
    });
    jso_ensureTokens({
        "manage": ["read"]
    });

    function renderClientList() {
        $.oajax({
            url: apiRoot + "/oauth/client",
            jso_provider: "manage",
            jso_scopes: ["read"],
            jso_allowia: true,
            dataType: 'json',
            success: function (data) {
                $("#clientList").html($("#clientListTemplate").render(data));
                addClientListHandlers();
            }
        });
    }

    function addClientListHandlers() {
        $("a.editClient").click(function () {
            editClient($(this).data('clientId'));
        });
        $("a.deleteClient").click(function() {
            if(confirm("Are you sure you want to delete '" + $(this).data('clientName') + "'")) {
               deleteClient($(this).data('clientId'));
            }
        });
    }

    function deleteClient(clientId) { 
        $.oajax({
            url: apiRoot + "/oauth/client/" + clientId,
            jso_provider: "manage",
            jso_scopes: ["read"],
            jso_allowia: true,
            type: "DELETE",
            success: function (data) {
                renderClientList();
            }
        });
    }

    function editClient(clientId) {
        if(clientId) {
            // client specified, we edit
            $.oajax({
                url: apiRoot + "/oauth/client/" + clientId,
                jso_provider: "manage",
                jso_scopes: ["read"],
                jso_allowia: true,
                success: function (data) {
                    $("#editModal").html($("#clientEditTemplate").render(data));
                    $("#editModal").show();
                    addEditClientHandlers();
                }
            });
        } else {
            // no client specified, we add
            var data = {};
            $("#editModal").html($("#clientEditTemplate").render(data));
            $("#editModal").show();
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
        $("#editModal a.close").click(function () {
            $("#editModal").hide();
        });
        $("#editModal a.editClose").click(function () {
            $("#editModal").hide();
        });
        $("#editModal a.editSave").click(function () {
            // FIXME: not really a nice way to fetch form data...
            var clientData = parseForm($('form.editClient'));
            if($(this).data('clientId')) {
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
            jso_provider: "manage",
            jso_scopes: ["read"],
            jso_allowia: true,
            type: "PUT",
            dataType: 'json',
            data: clientData,
            success: function (data) {
                $("#editModal").hide();
                renderClientList();
            }
        });
    }

    function addClient(clientData) {
        $.oajax({
            url: apiRoot + "/oauth/client",
            jso_provider: "manage",
            jso_scopes: ["read"],
            jso_allowia: true,
            type: "POST",
            dataType: 'json',
            data: clientData,
            success: function (data) {
                $("#editModal").hide();
                renderClientList();
            }
        });
    }

    $("a.addClient").click(function () {
        editClient();
    });


    function initPage() {
        $("#editModal").hide();
        renderClientList();
    }

    initPage();

});
