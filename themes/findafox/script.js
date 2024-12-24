function create_edit_box(e,imageId, commentId) {
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
                let text = span.original_innerHTML;
                const len = text.length;
                const str = "\n*(edited)*"
                const strlength = str.length;
                if (text.substring(len-strlength,len) === str){
                    text = text.substring(0,len-strlength);
                }
                textarea.innerHTML = text;
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
        input.value= commentId;
        form.appendChild(input);
        e.parentNode.appendChild(editBox);
    }
}