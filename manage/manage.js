$(document).ready(function () {
    jso_configure({
        "manage": {
            client_id: "manage",
            redirect_uri: "http://localhost/voot/manage/index.html",
            authorization: "http://localhost/voot/oauth/authorize"
        }
    });
    jso_ensureTokens({
        "manage": ["read"]
    });

    function loadClients() {
        $.oajax({
            url: "http://localhost/voot/oauth/client",
            jso_provider: "manage",
            jso_scopes: ["read"],
            jso_allowia: true,
            dataType: 'json',
            success: function (data) {
                if (data) {
                    $("#clientList").html(
                    $("#clientListTemplate").render(data));
                    addClickHandlers();
                } else {
                    alert("An error occurred.");
                }
            }
        });
    }

    function addClickHandlers() {
        $("a.delete").click(function () {
            var clientId = $(this).attr('href').slice(1);
            //var clientName = $(this).parent().prev().children("a").html();
            if (confirm("Are you sure you want to delete this client?")) {
                $.oajax({
                    url: "http://localhost/voot/oauth/client/" + clientId,
                    jso_provider: "manage",
                    jso_scopes: ["read"],
                    jso_allowia: true,
                    type: "DELETE",
                    success: function (data) {
                        loadClients();
                    }
                });
            }
        });
        $("a.edit").click(function () {
            var clientId = $(this).attr('href').slice(1);
            $.oajax({
                url: "http://localhost/voot/oauth/client/" + clientId,
                jso_provider: "manage",
                jso_scopes: ["read"],
                jso_allowia: true,
                success: function (data) {
                    $("div.editForm").html($("#clientEditTemplate").render(data));
                    $("div.editClient").show();
                    $('form').submit(function () {
                        var params = {};
                        jQuery.each(jQuery('form').serializeArray(), function (k, v) {
                            params[v.name] = (v.value === '') ? null : v.value;
                        });
                        x = JSON.stringify(params);
                        $.oajax({
                            url: "http://localhost/voot/oauth/client/" + clientId,
                            jso_provider: "manage",
                            jso_scopes: ["read"],
                            jso_allowia: true,
                            type: "PUT",
                            dataType: 'json',
                            data: x,
                            success: function (data) {
                                $("div.editClient").hide();
                                loadClients();
                            }
                        });
                        return false;
                    });
                }
            });
        });
    }

    function addOtherClickHandlers() {}
    $("button#addClient").click(function () {
        $("div.addForm").html($("#clientEditTemplate").render({}));
        $("div.addClient").show();
        $('form').submit(function () {
            var params = {};
            jQuery.each(jQuery('form').serializeArray(), function (k, v) {
                params[v.name] = (v.value === '') ? null : v.value;
            });
            x = JSON.stringify(params);
            $.oajax({
                url: "http://localhost/voot/oauth/client",
                jso_provider: "manage",
                jso_scopes: ["read"],
                jso_allowia: true,
                type: "POST",
                dataType: 'json',
                data: x,
                success: function (data) {
                    loadClients();
                }
            });
            return false;
        });
    });
    loadClients();
});
