$(document).ready(function () {
	$("a.infoButton").click(function() {
        $("table.detailsTable").toggle();
	});
    $("table.detailsTable").hide();
});
