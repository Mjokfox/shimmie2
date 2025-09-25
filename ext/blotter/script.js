document.addEventListener('DOMContentLoaded', () => {
	const id = $(".blotter").attr("data-id");
	if (id){
		const cookie = ui_cookie_get("blotter-removed");
		if (cookie == null || id > cookie){
			$(".blotter").show()
			$(".shm-blotter2-toggle").click(function() {
				$(".shm-blotter2").slideToggle("slow");
			});
		
			$("#blotter-hide").click(function() {
				ui_cookie_set("blotter-removed", id);
				$("#blotter, .blotter").remove()
			})
		} else {
			$("#blotter, .blotter").remove()
		}
	}
});
