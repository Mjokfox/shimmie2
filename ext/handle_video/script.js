/*jshint bitwise:true, curly:true, forin:false, noarg:true, noempty:true, nonew:true, undef:true, strict:false, browser:true, jquery:true */

function handleVideoAddTiny() {
    const mimeRegex = /^video\/.+/g;

    var image_elements = document.querySelectorAll(".shm-image-list a img");
    image_elements.forEach(function(item) {

        var parent = item.parentElement;
        parent.style.position = "relative";

        var mime =parent.dataset["mime"];
        if(mime.match(mimeRegex)) {
            var linkElement = document.createElement("DIV");
            // linkElement.innerHTML = "&#x1f39e;";
            linkElement.innerHTML = mime.split("/")[1];
            linkElement.style.position = "absolute";
            linkElement.style.top = "4px";
            linkElement.style.left = "4px";
            linkElement.style.padding = "4px";
            linkElement.style.paddingBottom = "1px";
            // linkElement.style.width = "10px";
            // linkElement.style.height = "10px";
            linkElement.style.fontSize = "14px";
            linkElement.style.color = "white";
            linkElement.style.background = "#2e2e4c";
            linkElement.style.borderRadius = "4px";
            linkElement.style.border = "1px solid white";

            parent.appendChild(linkElement);
        }
    });
}


document.addEventListener('DOMContentLoaded', () => {
    handleVideoAddTiny();
});
