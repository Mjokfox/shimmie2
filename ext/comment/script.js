function replyTo(imageId, commentId, userId) {
	var box = document.getElementById("comment_on_"+imageId);
	box.focus();
	box.value += `>>${imageId}#c${commentId}: `;
	shm_blink(document.getElementById("c" + commentId));
}
