class ReverseImage{};

ReverseImage.dropZoneInit = function(){
    const fileInput = document.getElementById("file_input");
    const dropZone = document.getElementById("dropZone")
    if (fileInput && dropZone) {
        dropZone.addEventListener("dragover", function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (e.dataTransfer.types.includes("Files")) {
                dropZone.style.borderStyle = "dashed";
            } else {
                e.dataTransfer.dropEffect = "dotted";
            }
        });
        dropZone.addEventListener("dragleave", function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.style.borderStyle = "dotted";
        });
        dropZone.addEventListener("drop", function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.style.borderStyle = "dotted";
            const files = e.dataTransfer.files;
            const dt = new DataTransfer();

            ([...files]).forEach(file => dt.items.add(file));
            fileInput.files = dt.files;

            fileInput.dispatchEvent(new Event("input", { bubbles: true }));

        },false);
    }
}

ReverseImage.submit_button = function(){
    document.getElementById('submit_button').click();
}

document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById("reverse_image_search")) return;
    document.getElementById("file_input").addEventListener('input', ReverseImage.submit_button);
    ReverseImage.dropZoneInit();
});

/**
 * @param {CustomEvent<{panel: UploadPanel, json: Object}>} e
 */
ReverseImage.upload_page_process = function(e) {
    if (!ReverseImage.only_duplicates) {
        ReverseImage.upload_page_tags(e.detail);
    }
    ReverseImage.upload_page_prepare_mini_duplicate(e.detail);
}

ReverseImage.upload_page_tags = function(detail) {
    if (!detail.json?.tag_predictions) return;
    const panel = detail.panel;
    const predictions = detail.json.tag_predictions;
    ReverseImage.panel_cache[panel.index] = predictions;
    ReverseImage.panel_predictions(panel, true);
    ui_cookie_set("reverse_image_panelcache", JSON.stringify(ReverseImage.panel_cache));
    panel.add_panel_listener(ReverseImage.panel_predictions);
}

ReverseImage.upload_page_prepare_mini_duplicate = function(detail) {
    if (!detail.json?.visual_duplicate) return;
    const panel = detail.panel;
    const data = detail.json.visual_duplicate;
    ReverseImage.duplicate_cache[panel.index] = data;
    ui_cookie_set("reverse_image_duplicatecache", JSON.stringify(ReverseImage.duplicate_cache));
    panel.add_panel_listener(ReverseImage.panel_show_mini_duplicate);
}

/**
 * @param {CustomEvent<{panels: UploadPanel[]}>} e
 */
ReverseImage.upload_page_recover = function(e){
    document.getElementById("reset_button")?.addEventListener("click", () =>{
        ReverseImage.panel_cache = {};
        ReverseImage.duplicate_cache = {};
        ui_cookie_set("reverse_image_panelcache", "{}");
        ui_cookie_set("reverse_image_duplicatecache", "{}");
    })

    e.detail.panels.forEach(p => {
        if (!ReverseImage.only_duplicates && ReverseImage.panel_cache[p.index]) {
            p.add_panel_listener(ReverseImage.panel_predictions);
        }
        if (ReverseImage.duplicate_cache[p.index]) {
            p.add_panel_listener(ReverseImage.panel_show_mini_duplicate);
        }
    });
}

ReverseImage.panel_show_mini_duplicate = function(panel) {
    if (panel.is_video) return;
    const data = ReverseImage.duplicate_cache[panel.index];
    if (!data) return;
    const image_data = data.image_data;
    document.getElementById("duplicate_img")?.remove();
    const img = build("IMG", {src: image_data.thumb_link})
    if (data.distance <= data.threshold){
        img.classList.add("auto_dupe");
    } else{
        img.classList.add("no_auto_dupe");
    }

    const hide = qbuild("B", "", "Hide");
    const div = build("DIV", {id: "duplicate_img", class: "media-preview"}, 
        qbuild("SPAN", "markdown", 
            "Closest visually similar ",
            build("A", {target: "_blank", href: `/post/view/${data.image_id}`}, "image on this site"),
            " please check if it is not the same. ",
            hide
        ),
        img,
        qbuild("B", "", `${image_data.width} x ${image_data.height}, ${fileSize(image_data.filesize)}`)
    )
    hide.addEventListener("click", () => {
        delete(ReverseImage.duplicate_cache[panel.index]);
        div.remove();
    })
    panel.parent.media_preview.append(div);
}

ReverseImage.panel_predictions = function(panel, can_input=false) {
    const predictions = ReverseImage.panel_cache[panel.index]
    if (!predictions) return;
    const sim_max = Object.values(predictions)[0];
    const threshold = 2.55*AUTO_TAG_THRESHOLD;
    for (const [tag, similarity] of Object.entries(predictions)) {
        const el = panel.selector_panel.querySelector(`INPUT[value=${CSS.escape(tag)}]`)
        if (el && el.parentElement) {
            const r = 127*(1- (similarity/sim_max));
            const g = 255*(similarity/sim_max);
            el.parentElement.style["background-color"] = `rgba(${r},${g},0,0.5)`;
            if (g > threshold) {
                el.parentElement.style["font-weight"] = "bold";
                if (can_input && ENABLE_AUTO_TAG){
                    if (el.type == "checkbox") el.checked = true;
                    el.dispatchEvent(new Event("click", { bubbles: true })); // TODO fix actual input
                    el.checked = true;
                }
            }
        }
    };
}
ReverseImage.only_duplicates = true;
if (typeof(ENABLE_REVERSE_IMAGE) !== "undefined" && ENABLE_REVERSE_IMAGE) {
    ReverseImage.panel_cache = JSON.parse(ui_cookie_get("reverse_image_panelcache") ?? "{}"); 
    ReverseImage.only_duplicates = false;
}

ReverseImage.duplicate_cache = JSON.parse(ui_cookie_get("reverse_image_duplicatecache") ?? "{}");
window.addEventListener("upload_result", ReverseImage.upload_page_process);
    window.addEventListener("upload_page_initialized", ReverseImage.upload_page_recover);