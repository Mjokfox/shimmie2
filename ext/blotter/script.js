/*jshint bitwise:true, curly:true, forin:false, noarg:true, noempty:true, nonew:true, undef:true, strict:false, browser:true, jquery:true */

document.addEventListener('DOMContentLoaded', () => {
	const id = $(".blotter").attr("data-id");
	if (id){
		const cookie = shm_cookie_get("ui-blotter-removed");
		if (cookie == null || id > cookie){
			$(".blotter").show()
			$(".shm-blotter2-toggle").click(function() {
				$(".shm-blotter2").slideToggle("slow", function() {
					if($(".shm-blotter2").is(":hidden")) {
						shm_cookie_set("ui-blotter2-hidden", 'true');
					}
					else {
						shm_cookie_set("ui-blotter2-hidden", 'false');
					}
				});
			});
			if(shm_cookie_get("ui-blotter2-hidden") === 'true') {
				$(".shm-blotter2").hide();
			}
		
			$("#blotter-hide").click(function() {
				shm_cookie_set("ui-blotter-removed", id);
				$("#blotter, .blotter").remove()
			})
		} else {
			$("#blotter, .blotter").remove()
		}
	}
	
});
