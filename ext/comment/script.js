function replyTo(imageId, commentId, userId) {
	var box = document.getElementById("comment_on_"+imageId);
	var text = ">>"+imageId+"#c"+commentId+": ";

	box.focus();
	box.value += text;
	$("#c"+commentId).highlight();
}
