function joinUrlSegments(base, query) {
    let  separatorChar = "?";
    if(base.includes("?")) {
        separatorChar = "&";
    }
    return base + separatorChar + query;
}

/**
 * @param {HTMLElement} el
 */
function autosize(el) {
	setTimeout(function() {
		if(el.offsetHeight < el.scrollHeight) {
			el.style.height = `calc(${el.scrollHeight}px + 0.5em)`;
			el.style.width = el.offsetWidth + 'px';
		}
	}, 0);
}

function clearViewMode() {
	document.querySelectorAll('.image_info').forEach((element) => {
		element.classList.remove('infomode-view');
	});
	document.querySelectorAll('.image_info textarea').forEach((el) => {
		autosize(el);
	});
}

function updateAttr(selector, attr, value) {
	document.querySelectorAll(selector).forEach(function(e) {
		let current = e.getAttribute(attr);
		let newval = joinUrlSegments(current, value);
		e.setAttribute(attr, newval);
	});
}

document.addEventListener('DOMContentLoaded', () => {
	// find elements with class image_info and set them to view mode
	// (by default, with no js, they are in edit mode - so that no-js
	// users can still edit them)
	document.querySelectorAll('.image_info').forEach((element) => {
		element.classList.add('infomode-view');
	});

	document.querySelectorAll('.image_info textarea').forEach((el) => {
		el.addEventListener('keydown', () => autosize(el));
		autosize(el);
	});

	if(document.location.hash.length > 3) {
		var query = document.location.hash.substring(1);
		var decodequery = decodeURIComponent(query.split("=")[1]);
		if(decodequery.match(/^order[=:]/)){
			const thing = document.getElementById("post_controls");
			if (thing) thing.parentElement.removeChild(thing);
			document.querySelectorAll("LINK#prevlink").forEach(function(e) {
				e.parentElement.removeChild(e);
			});
			document.querySelectorAll("LINK#nextlink").forEach(function(e) {
				e.parentElement.removeChild(e);
			});
		} else {
			updateAttr("LINK#prevlink", "href", query);
			updateAttr("LINK#nextlink", "href", query);
			updateAttr("A#prevlink", "href", query);
			updateAttr("A#nextlink", "href", query);
			updateAttr("A#searchlink", "href", query);
			updateAttr("form#image_delete_form", "action", query);
		}

		var searchinput = document.getElementById("searchinput");
		if (searchinput) searchinput.value = decodequery;
		var searchlink = document.getElementById("searchlink");
		if (searchlink) searchlink.innerHTML = "Search: " + decodequery;
	}
});
