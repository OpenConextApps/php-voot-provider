function setupToggle() {
	document.getElementById('showDetails').addEventListener("click", function() {
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
