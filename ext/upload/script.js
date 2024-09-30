// preview and input div handling
function isValidHttpUrl(string="") {
    let url;
    
    try {
      url = new URL(string);
    } catch (_) {
      return false;  
    }
  
    return url.protocol === "http:" || url.protocol === "https:";
}

function showPreview(file,url="", background_color="#0000") {
    const imagePreview = document.getElementById('imagePreview');
    if (imagePreview){
        if (file){
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
            }
            reader.readAsDataURL(file);
            imagePreview.parentElement.style["background-color"] = background_color;
        } else if (url) {
            imagePreview.src = url;
            imagePreview.style.display = 'block';
            imagePreview.parentElement.style["background-color"] = background_color;
        } 
        else{
            imagePreview.src = '';
            imagePreview.style.display = 'none';
        }
    }
}

function preview_handler(file_input,url_input,preview_button,background_color="#0000") {
    var output = false;
    if (isValidHttpUrl(url_input.value)){
        showPreview(null,url_input.value,background_color);
        output = true;
    } else if (file_input.files.length){
        showPreview(file_input.files[0],null,background_color);
        output = true;
    } else {
        output = false;
    }

    if(output){
        document.querySelectorAll("DIV.showPreviewButton").forEach((button) => {
            button.style.border = 'none';
        });
    }
    if (preview_button) {
        preview_button.style.border = output ? '2px dotted white' : 'none';
        preview_button.style.visibility = output ? 'visible' : 'hidden';
    }
    return output;

}

function full_input_handler(file_input,url_input,preview_button,input_button,background_color="#0000") {
    if (preview_handler(file_input,url_input,preview_button,background_color)) {
        if(input_button){input_button.style.visibility = 'visible';}
        return true;
    } else {
        if(input_button){
            input_button.style.visibility = 'hidden';
            input_button.style.border = 'none';
            input_button.textContent = 'Show Input';
        }
        return false;
    }
}

function onlyVisibility(file_input,url_input,preview_button,input_button){
    const show = isValidHttpUrl(url_input.value) || file_input.files.length
    if (preview_button) {
        preview_button.style.border = 'none';
        preview_button.style.visibility = show ? 'visible' : 'hidden';
    }
    if(input_button){
        if (show) {
            input_button.style.visibility = 'visible';
        } else{
            input_button.style.visibility = 'hidden';
            input_button.style.border = 'none';
            input_button.textContent = 'Show Input';
        }
    }
}

function preview_button_handler(suffix,preview_button,background_color="#0000") {
    const file_input = document.getElementById(`data${suffix}`);
    const url_input = document.getElementById(`urldata${suffix}`);
    preview_handler(file_input,url_input,preview_button,background_color);
}

function input_button_handler(suffix,input_button,background_color="#0000") {
    const file_input = document.getElementById(`data${suffix}`);
    const url_input = document.getElementById(`urldata${suffix}`);
    const preview_button = document.getElementById(`showpreviewdata${suffix}`);
    const input_div = document.getElementById(`inputdivdata${suffix}`);
    if (SPLIT_VIEW_ENABLED) {
        // reset other input divs
        document.querySelectorAll(".upload-split-view").forEach((e) => {
            if (e != input_div) {
                e.style.display = 'none'
            }
        });
        // reset other input buttons
        document.querySelectorAll("DIV.showInputButton").forEach((e) => {
            e.textContent = 'Show Input';
            e.style.border = 'none';
        });
        input_button.style.border = '2px dotted white';
    }
    if (input_div.style.display === 'none' || input_div.style.display === '') {
        input_div.style.display = 'block';
        input_button.textContent = 'Hide Input';
        
        full_input_handler(file_input,url_input,preview_button,input_button,background_color);

        const right_column = document.querySelector(".right-column");
        if (right_column) {
            right_column.style["background-color"] = background_color;
        }
    } else {
        input_div.style.display = 'none';
        self.textContent = 'Show Input';
        input_button.style.border = 'none';
    }
}

function show_input_div(input_button,suffix,background_color="#0000"){
    const input_div = document.getElementById(`inputdivdata${suffix}`);
    if (SPLIT_VIEW_ENABLED) {
        // reset other input divs
        document.querySelectorAll(".upload-split-view").forEach((e) => {
            if (e != input_div) {
                e.style.display = 'none'
            }
        });
        // reset other input buttons
        document.querySelectorAll("DIV.showInputButton").forEach((e) => {
            e.textContent = 'Show Input';
            e.style.border = 'none';
        });
        input_button.style.border = '2px dotted white';
    }

    input_div.style.display = 'block';
    input_button.textContent = 'Hide Input';

    const right_column = document.querySelector(".right-column");
    if (right_column) {
        right_column.style["background-color"] = background_color;
    }

}

function urlInputEvent(e){
    urlInput(e.target)
}

function urlInput(url_input) {
    const suffix = url_input.name.split("url")[1]
    var preview_button = document.getElementById("showpreviewdata"+suffix);
    var input_button = document.getElementById("showinputdata"+suffix);
    var background_color = "#0000"
    if (url_input.parentElement.parentElement) {
        background_color = url_input.parentElement.parentElement.style["background-color"];
    }
    const file_input = document.getElementById("data"+suffix);
    full_input_handler(file_input, url_input,preview_button,input_button,background_color)
}

// updatetracker

function fileSize(size) {
    var i = Math.floor(Math.log(size) / Math.log(1024));
    return (size / Math.pow(1024, i)).toFixed(2) * 1 + ['B', 'kB', 'MB', 'GB', 'TB'][i];
}

function updateTracker(e) {
    var size = 0;
    var upbtn = document.getElementById("uploadbutton");
    var tracker = document.getElementById("upload_size_tracker");
    var lockbtn = false;
    var previewed = false;
    
    let fileInputs = document.querySelectorAll("#large_upload_form input[type='file']");
    fileInputs = Array.from(fileInputs);
    // sort so event target is first
    if (e){
        fileInputs.sort((a, b) => {
            if (a === e.target) {
                return -1; 
            } else if (b === e.target) {
                return 1;
            } else {
                return 0; 
            }
        });
    }
    // check that each individual file is less than the max file size
    fileInputs.forEach((file_input) => {
        const suffix = file_input.id.split("data")[1];
        const url_input = document.querySelector(`input[name=url${suffix}]`);
        const cancel_button = document.getElementById("cancel"+file_input.id);
        const preview_button = document.getElementById("showpreview"+file_input.id);
        const input_button = document.getElementById("showinput"+file_input.id);
        const browse_button = document.getElementById("browse"+file_input.id)
        const background_color = document.getElementById("row"+file_input.id).style["background-color"];
        var toobig = false;
        if (!previewed){
            previewed = full_input_handler(file_input,url_input,preview_button,input_button,background_color);
            if (previewed) show_input_div(input_button,suffix,background_color);
        } else{
            onlyVisibility(file_input,url_input,preview_button,input_button);
        }

        if (file_input.files.length) {
            if(cancel_button) cancel_button.style.visibility = 'visible';
            
            for (var i = 0; i < file_input.files.length; i++) {
                size += file_input.files[i].size + 1024; // extra buffer for metadata
                if (window.shm_max_size > 0 && file_input.files[i].size > window.shm_max_size) {
                    toobig = true;
                }
            }
            if (toobig) {
                lockbtn = true;
                browse_button.style = 'color:red';
            } else {
                browse_button.style = 'color:inherit';
            }
        } else {
            if(cancel_button) cancel_button.style.visibility = 'hidden';
            browse_button.style = 'color:inherit';
        }
    });
    if (!previewed) {
        showPreview();
    }

    // check that the total is less than the max total size
    if (size) {
        tracker.innerText = fileSize(size);
        if (window.shm_max_total_size > 0 && size > window.shm_max_total_size) {
            lockbtn = true;
            tracker.style = 'color:red';
        } else {
            tracker.style = 'color:inherit';
        }
    } else {
        tracker.innerText = '0MB';
    }
    upbtn.disabled = lockbtn;
}

// file handling
function clearFiles(){
    document.querySelectorAll("#large_upload_form input[type='file']").forEach((input) => {
            input.value="";
    });
    updateTracker();
}

function distributefiles(){
    const self = document.getElementById("multiFileInput");
    const files = self.files;
    const override = document.getElementById('multiFileOverride').checked;
    const fileInputs = document.querySelectorAll('#large_upload_form input[type="file"]');

    let lastFileInput = null;
    let fileIndex = 0;

    for (let i = 0; i < fileInputs.length && fileIndex < files.length; i++) {
        const fileInput = fileInputs[i];

        if (override || !fileInput.files.length) {
            // If override is true or the input is empty, add the file to this input
            const dt = new DataTransfer();
            dt.items.add(files[fileIndex]);
            fileInput.files = dt.files;
            lastFileInput = fileInput;
            fileIndex++;
        }
    }
    if (files.length){
        self.value='';
    }
    if (lastFileInput){
        updateTracker({"target":lastFileInput})
    } else{
        updateTracker();
    }
}

// tag handling
const preset_tags = {
    "red_fox":["red_fur","white_fur","black_nose","orange_eyes","white_tail_tip"],
    "arctic_fox":["white_fur","black_nose","orange_eyes","white_tail_tip"],
    "fennec_fox":["tan_fur","black_nose","black_eyes","black_tail_tip"],
    "gray_fox":["red_fur","white_fur","gray_fur","black_nose","orange_eyes","black_tail_tip"],
    "bat-eared_fox":["black_fur","gray_fur","black_nose","black_eyes","black_tail_tip"],
    "bengal_fox":["tan_fur","gray_fur","black_nose","black_eyes","black_tail_tip"],
    "blanford's_fox":["tan_fur","gray_fur","black_nose","black_eyes","black_tail_tip"],
    "cape_fox":["tan_fur","gray_fur","black_nose","black_eyes","black_tail_tip"],
    "corsac_fox":["red_fur","white_fur","gray_fur","black_nose","orange_eyes","black_tail_tip"],
    "crab-eating_fox ":["black_fur","gray_fur","black_nose","black_eyes","black_tail_tip"],
    "culpeo_fox":["red_fur","black_fur","gray_fur","black_nose","orange_eyes","black_tail_tip"],
    "darwin's_fox":["red_fur","black_fur","gray_fur","black_nose","orange_eyes","black_tail_tip"],
    "hoary_fox":["tan_fur","gray_fur","black_nose","orange_eyes","black_tail_tip"],
    "island_fox":["red_fur","white_fur","gray_fur","black_nose","orange_eyes","black_tail_tip"],
    "kit_fox":["tan_fur","white_fur","gray_fur","black_nose","orange_eyes","black_tail_tip"],
    "pale_fox":["tan_fur","black_nose","orange_eyes","black_tail_tip"],
    "pampas_fox":["red_fur","gray_fur","black_nose","orange_eyes","black_tail_tip"],
    "ruppell's_fox":["tan_fur","black_nose","orange_eyes","white_tail_tip"],
    "sechuran_fox":["white_fur","gray_fur","black_nose","orange_eyes","black_tail_tip"],
    "south_american_gray_fox":["red_fur","white_fur","gray_fur","black_nose","orange_eyes","black_tail_tip"],
    "swift_fox":["tan_fur","white_fur","black_nose","orange_eyes","black_tail_tip"],
    "tibetan_fox":["tan_fur","white_fur","black_fur","gray_fur","black_nose","orange_eyes","white_tail_tip"],
}

var changed_tags = {};
var previous_presettag = [];
function presettags(self) {
    var tag = "";
    var add = false
    var split_id = "";
    if (self.nodeName === "OPTION"){
        split_id = self.parentNode.id.split("_");
    } else {split_id = self.id.split("_");}
    const suffix = split_id[1];
    if (split_id[0] === "tagsDropdown"){
        tag = self.value;
        add = true;
    } else {
        tag = self.value;
        add = self.checked;
    }
    if (!(suffix in changed_tags)) changed_tags[suffix] = [];
    if (self.type === "radio" || self.nodeName === "OPTION"){
        if (suffix in previous_presettag){
            preset_tags[previous_presettag[suffix]].forEach((tagg) =>{
                if (!changed_tags[suffix].includes(tagg)){
                    document.querySelector(`input[value="${tagg}"].tagsInput_${suffix}`).checked = false;
                }
            });
        }
    }
    preset_tags[tag].forEach((tagg) =>{
        if (!changed_tags[suffix].includes(tagg)){
            document.querySelector(`input[value="${tagg}"].tagsInput_${suffix}`).checked = add;
        }
    });
    previous_presettag[suffix] = tag;
    updateTags(self)
}

function getUrlTag(url) {
    const mimeTypes = {
        'jpg': 'image',
        'jpeg': 'image',
        'png': 'image',
        'bmp': 'image',
        'webp': 'image',
        'svg': 'image',

        'gif': 'gif',
        
        'mp4': 'video',
        'webm': 'video',
        'ogv': 'video',
        'mov': 'video',
        'avi': 'video',
        'mkv': 'video'
    };

    return mimeTypes[url.split('.').pop().toLowerCase().split('?')[0].split('#')[0]] || null;
}

function updateUserInput(e){
    updateTags(e.target);
}

function updateTags(self) {
    var split_id = "";
    if (self.nodeName === "OPTION"){
        split_id = self.parentNode.id.split("_");
    } else {split_id = self.id.split("_");}
    const suffix = split_id[1];
    const fake_tags_input = document.getElementById(`faketags_${suffix}`);
    const user_tags_input = document.getElementById(`usertags_${suffix}`);
    const tags_input = document.getElementById(`tags${suffix}`);
    const file_input = document.getElementById(`data${suffix}`);
    const url_input = document.getElementById(`urldata${suffix}`);
    var tags = [];
    if (!(suffix in changed_tags)) changed_tags[suffix] = [];
    if (split_id !== "tagsDropdown"){
        if (!changed_tags[suffix].includes(self.value)) changed_tags[suffix].push(self.value);
    }
    if (isValidHttpUrl(url_input.value)) {
        const type_tag = getUrlTag(url_input.value);
        if (type_tag) {
            tags.push(type_tag);
        }
    } else if (file_input.files[0]){
        const splitType = file_input.files[0].type.split("/");
        if (splitType[0] === "video"){
            tags.push("video");
        }
        else if (splitType[0] === "image"){
            if (splitType[1] === "gif"){
                tags.push("gif");
            } else
                tags.push("image");
        }
    }
    if (tags_input){
        document.querySelectorAll("#tagsInput_" + suffix).forEach((input) =>{
            if (input.checked) {
                tags.push(input.value);
            }
        });
        document.querySelectorAll("#tagsDropdown_" + suffix).forEach((input) =>{
            tags.push(input.value);
        });
    }
    const tagsString = tags.join(" ");
    fake_tags_input.value = tagsString;
    fake_tags_input.dispatchEvent(new Event('input'));

    tags_input.value = tagsString + " " + user_tags_input.value;
    tags_input.dispatchEvent(new Event('input'));
}

function clearInputs(self) {
    const suffix = self.id.split("_")[1];
    document.querySelectorAll("#tagsInput_" + suffix).forEach((input) =>{
            input.checked = false;
    });
    document.querySelectorAll("#tagsDropdown_" + suffix).forEach((input) =>{
            input.selectedIndex = 0;
    });
    document.getElementById("faketags_"+suffix).value = '';
    document.getElementById("usertags_"+suffix).value = '';
    document.getElementById("tags"+suffix).value = '';
}

function copyTagsTo(self, target){
    const self_suffix = self.id.split("_")[1];
    const target_suffix = target.value;
    const selfElements = document.querySelectorAll(`#tagsInput_${self_suffix}`);
    const selfElementsDropdown = document.querySelectorAll(`#tagsDropdown_${self_suffix}`);

    const targetElements = document.querySelectorAll(`#tagsInput_${target_suffix}`);
    const targetElementsDropdown = document.querySelectorAll(`#tagsDropdown_${target_suffix}`);

    // Ensure both NodeLists have the same length
    if (selfElements.length !== targetElements.length || selfElementsDropdown.length !== targetElementsDropdown.length) {
        console.error('Mismatch in the number of elements between self and target suffixes.');
        return;
    }

    // Iterate over each pair of elements and copy the value
    selfElements.forEach((selfElement, index) => {
        targetElements[index].checked = selfElement.checked;
    });
    selfElementsDropdown.forEach((selfElementDropdown, index) => {
        targetElementsDropdown[index].value = selfElementDropdown.value;
    });
    if (targetElements.length > 0){
        updateTags(targetElements[0]);
    }
}

const makeCheckbox = {"multiple":["Age","EyesMouth1","EyesMouth2"],"multiple_species":["Species","Age","EyesMouth1","EyesMouth2"]};
const makeRadio = {"single":["Species","Age","EyesMouth1","EyesMouth2"],"multiple":["Species"]};
const appears = {"red_fox":["Muzzle"]};

function checkboxRadio(self){
    const suffix = self.id.split("_")[1]
    if (self.value in makeCheckbox){
        makeCheckbox[self.value].forEach((name) => {
            document.querySelectorAll(`input[name="${name}_${suffix}"]`).forEach((el) =>{
                el.type = "checkbox";
            });
        });
    } if (self.value in makeRadio){
        makeRadio[self.value].forEach((name) => {
            document.querySelectorAll(`input[name="${name}_${suffix}"]`).forEach((el) =>{
                el.type = "radio";
            });
        });
    } if (self.value in appears){
        appears[self.value].forEach((name) => {
            document.querySelectorAll(`input[name="${name}_${suffix}"]`).forEach((el) =>{
                el.disabled = !self.checked;
                el.checked = false;
            });
        });
    }

}

function radio_unsetInit() {
    document.querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach((radio) => {
        radio.previousChecked = radio.checked;
        radio.addEventListener('click', function(event) {
            if (radio.type === "radio"){
                if (radio.previousChecked) {
                    radio.checked = false;
                    radio.previousChecked = false;
                    updateTags(radio);
                    event.preventDefault();
                } else {
                    document.querySelectorAll('input[name="' + radio.name + '"]').forEach((r) => {
                        r.previousChecked = false;
                    });
                    radio.previousChecked = true;
                }
            }
        });
    });
}

// page dynamics
const upload_style = document.createElement('style');
upload_style.innerHTML = `
  DIV.upload-split-view {
  }
`;
document.head.appendChild(upload_style);
const upload_style_rule = upload_style.sheet.cssRules[0];
function sliderInit() {
    const container = document.querySelector('.container');
    const leftColumn = document.querySelector('.left-column');
    const rightColumn = document.querySelector('.right-column');
    const divider = document.querySelector('.divider');
    if (container && leftColumn && rightColumn && divider) {
        let isResizing = false;

        // Set different bounds based on device type
        const minBound = 20;
        const maxBound = 80;

        // Function to start resizing
        function startResizing(e) {
            isResizing = true;
            document.body.style.cursor = 'col-resize';
            e.preventDefault();
        }

        // Function to perform the resizing
        function resize(e) {
            if (!isResizing) return;

            // Determine whether we're dealing with a mouse event or a touch event
            const clientX = e.clientX || e.touches[0].clientX;

            const containerRect = container.getBoundingClientRect();
            let newLeftWidth = ((clientX - containerRect.left) / containerRect.width) * 100;
            let inputleft = ((clientX + 10)/window.innerWidth)*100;
            if (newLeftWidth < minBound || newLeftWidth > maxBound) {
                newLeftWidth = newLeftWidth < minBound ? minBound : maxBound;
                inputleft = newLeftWidth+1; //placeholder for now,works good enough
            }

            const newRightWidth = 99 - newLeftWidth;

            leftColumn.style.width = `${newLeftWidth}%`;
            rightColumn.style.width = `${newRightWidth}%`;
            upload_style_rule.style.left = `${inputleft}%`;
            if (PREVIEW_ENABLED && !SPLIT_VIEW_ENABLED)
                rightColumn.firstChild.style.width = `${(newRightWidth/100)*containerRect.width}px`;
        }

        // Function to stop resizing
        function stopResizing() {
            if (isResizing) {
                isResizing = false;
                document.body.style.cursor = '';
            }
        }

        // Mouse events
        divider.addEventListener('mousedown', startResizing);
        document.addEventListener('mousemove', resize);
        document.addEventListener('mouseup', stopResizing);

        // Touch events for mobile
        divider.addEventListener('touchstart', startResizing);
        document.addEventListener('touchmove', resize);
        document.addEventListener('touchend', stopResizing);


    }
}

function dropZoneInit(){
    const fileInput = document.getElementById("multiFileInput");
    const dropZone = document.getElementById("dropZone")
    if (fileInput) {
        fileInput.addEventListener('input',distributefiles);
        if (dropZone){
            dropZone.addEventListener("dragover", function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (e.dataTransfer.types.includes("Files")) {
                    dropZone.style.borderStyle = "dashed";
                } else {
                    e.dataTransfer.dropEffect = "none"; // Indicate that dropping is not allowed
                }
                // dropZone.style.borderStyle = "dashed";
            });
            dropZone.addEventListener("dragleave", function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.borderStyle = "none";
            });
            dropZone.addEventListener("drop", function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.style.borderStyle = "none";
                const files = e.dataTransfer.files;
                const dt = new DataTransfer();

                ([...files]).forEach(file => dt.items.add(file));
                fileInput.files = dt.files;

                fileInput.dispatchEvent(new Event("input", { bubbles: true }));

            },false);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if(document.getElementById("upload_size_tracker")) {
        document.querySelectorAll("#large_upload_form input[type='file']").forEach((el) => {
            el.addEventListener('change', updateTracker);
        });
        document.querySelectorAll("input.url-input").forEach((el) => {
            el.addEventListener('input', urlInputEvent);
        });
        document.querySelectorAll("textarea.user-input-tags").forEach((el) => {
            el.addEventListener('input', updateUserInput);
        });
        updateTracker();
    }
    dropZoneInit();
    sliderInit();
    radio_unsetInit();
    document.querySelectorAll(".disabledOnStartup").forEach((el) => {
        el.disabled = true;
    });

});
