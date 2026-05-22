document.addEventListener('DOMContentLoaded', () => {
	const id = document.body.querySelector(".blotter")?.dataset.id;
	const dismiss = document.getElementById("blotter-dismiss");
	if (id && dismiss){
		dismiss.addEventListener("click", () => {
			shm_cookie_set("blotter-removed", id);
			const blotter = document.getElementById("blotter");
			if (blotter) blotter.remove();
		})
	}
});
