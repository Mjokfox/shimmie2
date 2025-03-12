function comment_edit_box(e, imageId, commentId) {
    const exists = document.getElementById(`cedit_${commentId}`)
    if (exists){
        exists.remove();
        return;
    }
	const postBox = document.getElementById(`cadd${imageId}`);
    const editBox = postBox.cloneNode(true);
    editBox.id = `cedit_${commentId}`;
    const form = editBox.querySelector("form");
    if (form) {
        const textarea = form.querySelector("textarea");
        if (textarea) {
            textarea.id = `edit_${commentId}`;
            const span = e.parentNode.querySelector("SPAN.markdown");
            if (span) {
                textarea.value = span.original_innerHTML;
            }
        }
        const submit = form.querySelector("input[type=submit]");
        if (submit){
            submit.value = "Edit Comment";
        }
        form.action = "/comment/edit";
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "comment_id";
        input.value = commentId;
        form.appendChild(input);
        e.parentNode.appendChild(editBox);
    }
}

function forum_edit_box(e, threadID, postID) {
    const exists = document.getElementById(`cedit_${postID}`)
    if (exists){
        exists.remove();
        return;
    }
	const postBox = document.getElementById(`cadd${threadID}`);
    const editBox = postBox.cloneNode(true);
    editBox.id = `cedit_${postID}`;
    const form = editBox.querySelector("form");
    if (form) {
        const textarea = form.querySelector("textarea");
        if (textarea) {
            textarea.id = `edit_${postID}`;
            const span = e.parentNode.querySelector("SPAN.markdown");
            if (span) {
                textarea.value = span.original_innerHTML;
            }
        }
        const submit = form.querySelector("input[type=submit]");
        if (submit){
            submit.value = "Edit Comment";
        }
        form.action = "/forum/edit";
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "threadID";
        input.value = threadID;
        const input1 = document.createElement("input");
        input1.type = "hidden";
        input1.name = "postID";
        input1.value = postID;
        form.appendChild(input);
        form.appendChild(input1);
        e.parentNode.appendChild(editBox);
    }
}