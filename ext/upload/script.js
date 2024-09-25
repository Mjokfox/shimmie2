function fileSize(size) {
    var i = Math.floor(Math.log(size) / Math.log(1024));
    return (size / Math.pow(1024, i)).toFixed(2) * 1 + ['B', 'kB', 'MB', 'GB', 'TB'][i];
}

function showpreview(file, background_color="#F002",url="") {
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

function isValidHttpUrl(string) {
    let url;
    
    try {
      url = new URL(string);
    } catch (_) {
      return false;  
    }
  
    return url.protocol === "http:" || url.protocol === "https:";
  }

function urlInputEvent(e){
    urlInput(e.target)
}

function urlInput(input) {
    const suffix = input.name.split("url")[1]
    var showprevbtn = document.getElementById("showpreviewdata"+suffix);
    var showinputbtn = document.getElementById("showinputdata"+suffix);
    var color = "#F002"
    if (input.parentElement.parentElement) {
        color = input.parentElement.parentElement.style["background-color"];
    }
    if (isValidHttpUrl(input.value)) {
        if(showinputbtn){
            showinputbtn.style.visibility = 'visible'
        }
        if(showprevbtn){ 
            showprevbtn.style.visibility = 'visible';
            showpreview_handler(showprevbtn,null,input,color)
        } else {
            showpreview(null,color,input.value);
        }
    } else {
        const fileInput = document.getElementById("data"+suffix);
        if (fileInput.files.length) {
            if(showprevbtn){ 
                showprevbtn.style.visibility = 'visible';
                showpreview_handler(showprevbtn,fileInput.files[0],input,color)
            } else {
                showpreview(fileInput.files[0],color);
            }
        } else{
            if(showinputbtn){
                showinputbtn.style.visibility = 'hidden'
            }
            if(showprevbtn){ 
                showprevbtn.style.visibility = 'hidden';
            }
            showpreview();
        }
    }
}

function showpreview_handler(self,file,url,background_color="#0F02") {
    document.querySelectorAll("DIV.showPreviewButton").forEach((button) => {
        button.style.border = 'none';
    });
    self.style.border = '2px dotted white';
    if (isValidHttpUrl(url.value)){
        showpreview(null,background_color,url.value);
    } else {
        showpreview(file,background_color);
    }
}

function inputdiv(self,preview,div,url,previewId,background_color="#0F02") {
    if (div.style.display === 'none' || div.style.display === '') {
        div.style.display = 'block';
        self.textContent = 'Hide Input';
        showpreview_handler(preview,document.getElementById(previewId).files[0],url,background_color)

        const right_column = document.querySelector(".right-column");
        if (right_column) {
            right_column.style["background-color"] = background_color;
        }
    } else {
        div.style.display = 'none';
        self.textContent = 'Show Input';
    }
    if (split_view_enabled) {
        document.querySelectorAll(".upload-split-view").forEach((input) => {
            if (input != div) {
                input.style.display = 'none'
            }
        });
        document.querySelectorAll("DIV.showInputButton").forEach((button) => {
            if (button != self){
                button.textContent = 'Show Input';
                button.style.border = 'none';
            }
        });
        self.style.border = '2px dotted white';
    }
}

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

    let fileIndex = 0;

    function resetFileInput(fileInput, file) {
        // Create a clone of the input to reset it
        const newFileInput = fileInput.cloneNode();

        // Create a DataTransfer object and add the file to it
        const dt = new DataTransfer();
        dt.items.add(file);
        newFileInput.files = dt.files;

        // Replace the old input with the new one
        fileInput.parentNode.replaceChild(newFileInput, fileInput);
    }

    for (let i = 0; i < fileInputs.length && fileIndex < files.length; i++) {
        const fileInput = fileInputs[i];

        if (override || !fileInput.files.length) {
            // If override is true or the input is empty, add the file to this input
            const dt = new DataTransfer();
            dt.items.add(files[fileIndex]);
            fileInput.files = dt.files;

            fileIndex++; // Move to the next file
        }
    }
    if (files.length){
        self.value='';
    }
    updateTracker();
}

const preset_tags = {
    "red_fox":["red_fur","white_fur","black_nose","orange_eyes","white_tail_tip"],
    "arctic_fox":["white_fur","black_nose","orange_eyes","white_tail_tip"],
    "fennec_fox":["tan_fur","black_nose","black_eyes","black_tail_tip"],
    "gray_fox":["red_fur","white_fur","gray_fur","black_nose","orange_eyes","black_tail_tip"],
    "bat-eared_fox":["black_fur","gray_fur","black_nose","black_eyes","black_tail_tip"],
    "bengal_fox":["tan_fur","gray_fur","black_nose","black_eyes","black_tail_tip"],
    "blanford’s_fox":["tan_fur","gray_fur","black_nose","black_eyes","black_tail_tip"],
    "cape_fox":["tan_fur","gray_fur","black_nose","black_eyes","black_tail_tip"],
    "corsac_fox":["red_fur","white_fur","gray_fur","black_nose","orange_eyes","black_tail_tip"],
    "crab-eating_fox ":["black_fur","gray_fur","black_nose","black_eyes","black_tail_tip"],
    "culpeo_fox":["red_fur","black_fur","gray_fur","black_nose","orange_eyes","black_tail_tip"],
    "darwin’s_fox":["red_fur","black_fur","gray_fur","black_nose","orange_eyes","black_tail_tip"],
    "hoary_fox":["tan_fur","gray_fur","black_nose","orange_eyes","black_tail_tip"],
    "island_fox":["red_fur","white_fur","gray_fur","black_nose","orange_eyes","black_tail_tip"],
    "kit_fox":["tan_fur","white_fur","gray_fur","black_nose","orange_eyes","black_tail_tip"],
    "pale_fox":["tan_fur","black_nose","orange_eyes","black_tail_tip"],
    "pampas_fox":["red_fur","gray_fur","black_nose","orange_eyes","black_tail_tip"],
    "ruppell’s_fox":["tan_fur","black_nose","orange_eyes","white_tail_tip"],
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
    var suffix = split_id[1];
    if (split_id[0] === "tagsDropdown"){
        tag = self.value;
        add = true;
    } else {
        tag = self.value;
        add = self.checked;
    }
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

function updateTracker(e) {
    var size = 0;
    var upbtn = document.getElementById("uploadbutton");
    var tracker = document.getElementById("upload_size_tracker");
    var lockbtn = false;
    var previewed = false;
    
    // check that each individual file is less than the max file size
    document.querySelectorAll("#large_upload_form input[type='file']").forEach((input) => {
        const suffix = input.id.split("data")[1];
        var cancelbtn = document.getElementById("cancel"+input.id);
        var showprevbtn = document.getElementById("showpreview"+input.id);
        var showinputbtn = document.getElementById("showinput"+input.id);
        var inputbutton = document.getElementById("browse"+input.id)
        var TR_color = document.getElementById("row"+input.id)
        const url_input = document.querySelector(`input[name=url${suffix}]`);
        var toobig = false;
        if (input.files.length) {
            if(cancelbtn) cancelbtn.style.visibility = 'visible';
            if(showinputbtn) {
                showinputbtn.style.visibility = 'visible'
                if (url_input){
                    showpreview_handler(showprevbtn,input.files[0],url_input,TR_color.style["background-color"])
                } else{
                showpreview(input.files[0],TR_color.style["background-color"])}
                previewed = true;
            }
            if(showprevbtn) showprevbtn.style.visibility = 'visible';
            for (var i = 0; i < input.files.length; i++) {
                size += input.files[i].size + 1024; // extra buffer for metadata
                if (window.shm_max_size > 0 && input.files[i].size > window.shm_max_size) {
                    toobig = true;
                }

            }
            if (toobig) {
                lockbtn = true;
                inputbutton.style = 'color:red';
            } else {
                inputbutton.style = 'color:inherit';
            }
        } else {
            if(cancelbtn) cancelbtn.style.visibility = 'hidden';
            if (url_input && isValidHttpUrl(url_input.value)) {
                
                if(showinputbtn){
                    showinputbtn.style.visibility = 'visible'
                }
                if(showprevbtn){ 
                    showprevbtn.style.visibility = 'visible';
                    showpreview_handler(showprevbtn,null,url_input,TR_color.style["background-color"])
                } else {
                    showpreview(null,TR_color.style["background-color"],url_input.value);
                }
                previewed = true;
            } else {
                
                if(showinputbtn) showinputbtn.style.visibility = 'hidden';
                if(showprevbtn) showprevbtn.style.visibility = 'hidden';
                inputbutton.style = 'color:inherit';
            }
        }
    });
    if (!previewed) {
        showpreview()
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

function updateTags(self) {
    const split_id = self.id.split("_");
    const suffix = split_id[1];
    const tagsinput = document.getElementById("tags"+suffix);
    const fileInput = document.getElementById("data"+suffix);
    var tags = [];
    if (!(suffix in changed_tags)) changed_tags[suffix] = [];
    if (split_id !== "tagsDropdown"){
        if (!changed_tags[suffix].includes(self.value)) changed_tags[suffix].push(self.value);
    }
    if (fileInput.files[0]){
        const splitType = fileInput.files[0].type.split("/");
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
    if (tagsinput){
        document.querySelectorAll("#tagsInput_" + suffix).forEach((input) =>{
            if (input.checked) {
                tags.push(input.value);
            }
        });
        document.querySelectorAll("#tagsDropdown_" + suffix).forEach((input) =>{
            tags.push(input.value);
        });
    }

    tagsinput.value = tags.join(" ");
    tagsinput.dispatchEvent(new Event('input'));
}

function clearInputs(self) {
    const suffix = self.id.split("_")[1];
    document.querySelectorAll("#tagsInput_" + suffix).forEach((input) =>{
            input.checked = false;
    });
    document.querySelectorAll("#tagsDropdown_" + suffix).forEach((input) =>{
            input.selectedIndex = 0;
    });
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

// when single > lock species, age. Mutiple > unlock age. multiple species unlocks all
// when red_fox > appear muzzle marking

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
            if (preview_enabled && !split_view_enabled)
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
        updateTracker();
    }
    dropZoneInit();
    sliderInit();
    document.querySelectorAll(".disabledOnStartup").forEach((el) => {
        el.disabled = true;
    });
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
});
