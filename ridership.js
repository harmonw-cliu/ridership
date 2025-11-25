function toggle_visibility(element_id) {
	var element = document.getElementById(element_id);
	if (element.style.display === "none") {
		element.style.display = "block";
	} else {
		element.style.display = "none";
	}
}
