class DuplicateDetector{};

DuplicateDetector.popup_file = function(file, image_data, post_id) {
    const url = URL.createObjectURL(file);
    const out = DuplicateDetector.popup(url, image_data, post_id, fileSize(file.size));
    URL.revokeObjectURL(url);
    return out;
}

DuplicateDetector.popup = function(url, image_data, post_id, size = "unknown size") {
    const divider = build("DIV", {class: "upload-duplicate-divider", title: "click to switch"});
    
    const user_img_info = document.createElement('b');
    const user_img = build("IMG", {src: url});
    user_img.onload = () => {
        user_img_info.textContent = `${user_img.naturalWidth} x ${user_img.naturalHeight}, ${size}`;
    }
    
    const user_img_container = qbuild("DIV", "upload-duplicate-img", 
                    user_img,
                    user_img_info,
                    qbuild("SPAN", "", "Your image")
                );
    const existing_img_container = qbuild("DIV", "upload-duplicate-img", 
                    build("IMG", {src: image_data.link}),
                    qbuild("B", "", `${image_data.width} x ${image_data.height}, ${fileSize(image_data.filesize)}`),
                    build("A", {href: `/post/view/${post_id}`}, "Existing image"),
                );
    const subcontainer = qbuild("DIV", "upload-duplicate-subcontainer",
                user_img_container,
                divider,
                existing_img_container,
            );
    const order = [user_img_container, existing_img_container];

    divider.addEventListener("click", () => {
        order.push(order.shift());
        subcontainer.append(order[0], divider, order[1]);
    })

    const accept = qbuild("BUTTON", "upload-popup-accept", "Yes, mine is unique");
    const greater = qbuild("BUTTON", "upload-duplicate-greater", "Yes, mine is higher quality⤴");
    const reject = qbuild("BUTTON", "upload-popup-reject", "No, mine is lower quality");

    const container = qbuild("DIV", "upload-popup-container",
        qbuild("DIV", "upload-popup-centering",
            subcontainer,
            qbuild("DIV", "upload-duplicate-actions-container",
                qbuild("H2", "", "Similar image found, continue upload?"),
                qbuild("DIV", "upload-duplicate-actions",
                    accept,
                    greater,
                    reject
                )
            )
        )
    );
    return {container: container, accept: accept, reject: reject, greater: greater};
}

/**
 * @param {CustomEvent} e
 * @param {UploadPanel} e.detail.panel
 * @param {Object} e.detail.json
 */
DuplicateDetector.upload_page_process = async function(json, panel) {
    if (!json.duplicate_detection) return true;
    const detection = json.duplicate_detection;
    const visual = json.visual_duplicate;
    let runner = {};
    if (detection.distance <= detection.threshold) {
        runner = detection;
    } else if (visual && visual.distance <= visual.threshold) {
        runner = visual
    } else return true;

    let filename = "";
    if (panel.file_input.files?.length) {
        const file = panel.file_input.files[0];
        filename = file.name;
        var nodes = DuplicateDetector.popup_file(file, runner.image_data, runner.image_id);
    } else if (panel.url_input.value) {
        filename = panel.url_input.value;
        var nodes = DuplicateDetector.popup(panel.url_input.value, runner.image_data, runner.image_id);
    } else return true;
 
        document.body.append(nodes.container);
    const out = await new Promise((resolve, reject) => {
        nodes.accept.addEventListener("click", () => resolve(true));
        nodes.reject.addEventListener("click", () => resolve(false));
        nodes.greater.addEventListener("mousedown", () => {
            window.open(shm_make_link(`duplicate_replace/${runner.image_id}`, `flash=The filename or url was: ${filename}`), '_blank').focus();
            resolve(false);
        });
        document.addEventListener("keydown", (e) => {
            switch (e.key) {
                case "Escape":
                    resolve(false)
                    break;
                case "Enter":
                    resolve(true)
                    break;
                default:
                    break;
            }
        })
    });
    nodes.container.remove();
    return out;
}

window.addEventListener("upload_result", (e) => e.detail.async_listeners.push(DuplicateDetector.upload_page_process))