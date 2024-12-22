document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll(".shm-clink").forEach(function(el) {
		var target_id = el.getAttribute("data-clink-sel");
		if(target_id && document.getElementById(target_id.replace("#",""))) {
			// if the target comment is already on this page, don't bother
			// switching pages
			// el.setAttribute("href", target_id);

			// highlight it when clicked
			el.addEventListener("click", function(e) {
				// This needs jQuery UI
				$(target_id).highlight();
			});

			// vanilla target name should already be in the URL tag, but this
			// will include the anon ID as displayed on screen
			el.innerHTML = "Replying to: @"+document.querySelector(target_id+" .username").innerHTML;
		}
	});
});

function markdown_spoiler(el) {
	if (el.style["background-color"] === "unset"){
		el.style["background-color"] = "#000";
		el.style["color"] = "#000";
	} else {
		el.style["background-color"] = "unset";
		el.style["color"] = "unset";
	}
}