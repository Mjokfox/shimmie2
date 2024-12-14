// only do this on the right page
if (window.location.pathname === "/reverse_image_search"){
    function dropZoneInit(){
        const fileInput = document.getElementById("file_input");
        const dropZone = document.getElementById("dropZone")
        if (fileInput && dropZone) {
            dropZone.addEventListener("dragover", function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (e.dataTransfer.types.includes("Files")) {
                    dropZone.style.borderStyle = "dashed";
                } else {
                    e.dataTransfer.dropEffect = "dotted"; // Indicate that dropping is not allowed
                }
                // dropZone.style.borderStyle = "dashed";
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

    function submit_button(){
        document.getElementById('submit_button').click();
    }

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById("file_input").addEventListener('input', submit_button);
    dropZoneInit();
});

}

// tag prediction
if (window.location.pathname === "/upload"){
    var used_array = [];
    async function get_predictions(id) {
        if (used_array.includes(id)){
            return;
        }
        used_array.push(id);
        const url_input =  document.getElementById(`urldata${id}`);
        const file_input = document.getElementById(`data${id}`);
        const data = new FormData()
        data.append('file', file_input.files[0])
        data.append('url_input', url_input.value)

        const tag_n = await fetch('/reverse_image_search_fromupload', 
            {
                method: 'POST',
                body: data
            }).then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();  // Parse the JSON response
            }).catch(error => {
                console.error('There was a problem with the fetch operation:', error);
                return;
            });

        if (tag_n) {
            const inputdiv = document.getElementById(`inputdivdata${id}`);
            if (ENABLE_AUTO_TAG){
                inputdiv.querySelectorAll("input[type=radio], input[type=checkbox]").forEach((input) => {
                    input.checked = false;
                    if (input.parentElement){
                        input.parentElement.style["background-color"] = "rgba(127,0,0,0.25)";
                    }
                });
            } else {
                inputdiv.querySelectorAll("input[type=radio], input[type=checkbox]").forEach((input) => {
                    if (input.parentElement){
                        input.parentElement.style["background-color"] = "rgba(127,0,0,0.25)";
                    }
                });
            }
            const sim_max = Object.values(tag_n)[0];
            const threshold = 2.55*AUTO_TAG_THRESHOLD;
            for (const [tag, similarity] of Object.entries(tag_n)) {
                const el = inputdiv.querySelector(`input[value=${CSS.escape(tag)}]`)
                if (el && el.parentElement) {
                    const r = 127*(1- (similarity/sim_max));
                    const g = 255*(similarity/sim_max);
                    el.parentElement.style["background-color"] = `rgba(${r},${g},0,0.5)`;
                    if (g > threshold) {
                        el.parentElement.style["font-weight"] = "bold";
                        if (ENABLE_AUTO_TAG){
                            el.checked = true;
                        }
                    }
                }
            };
            const el = document.getElementById(`usertags_${id}`)
            if (el){
                el.dispatchEvent(new Event('input'));
            }    
            
        }
    }
    function make_predict_button(id){
        const input = document.createElement("input");
        input.id = `predict-button_${id}`;
        input.type = "button";
        input.value = "Predict tags";
        input.style["width"] = "auto";
        input.style["padding"] = "0px 10px";
        input.setAttribute("onclick","");
        input.addEventListener('click', function() {
            get_predictions(id);
        });
        return input;
    }
    
    function input_button_predict(el){
        if (el.style["visibility"] == "visible"){
            id = el.id.split("data")[1];
            get_predictions(id);
        }
    }

    function tags_predict_init(){
        document.querySelectorAll('[id^="CopyNumber_"]').forEach((el) => {
            el.after(make_predict_button(el.id.split("_")[1]));
        });
    }
document.addEventListener('DOMContentLoaded', () => {
    tags_predict_init();
    if(ENABLE_AUTO_PREDICT){
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutationRecord) {
                input_button_predict(mutationRecord.target);
            });    
        });
        
        document.querySelectorAll('.showInputButton').forEach((el) => {
            observer.observe(el, { attributes : true, attributeFilter : ['style'] });
        });
    }
    
});

}