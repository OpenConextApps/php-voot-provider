function setupToggle() {
	document.getElementById('detailsButton').addEventListener("click", function() {
        var dT = document.getElementById("detailsTable");
        if(dT.style.display == "block") {
            dT.style.display = "none";
        }
        else {
            dT.style.display = "block";
        }
    }, false);
}

window.onload = setupToggle;
