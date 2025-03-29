// only do this on the right page
if (window.location.pathname === "/upload"){

// md5 thing because we dont want to rely on external services
var CryptoJS=CryptoJS||function(s,p){var m={},l=m.lib={},n=function(){},r=l.Base={extend:function(b){n.prototype=this;var h=new n;b&&h.mixIn(b);h.hasOwnProperty("init")||(h.init=function(){h.$super.init.apply(this,arguments)});h.init.prototype=h;h.$super=this;return h},create:function(){var b=this.extend();b.init.apply(b,arguments);return b},init:function(){},mixIn:function(b){for(var h in b)b.hasOwnProperty(h)&&(this[h]=b[h]);b.hasOwnProperty("toString")&&(this.toString=b.toString)},clone:function(){return this.init.prototype.extend(this)}},
q=l.WordArray=r.extend({init:function(b,h){b=this.words=b||[];this.sigBytes=h!=p?h:4*b.length},toString:function(b){return(b||t).stringify(this)},concat:function(b){var h=this.words,a=b.words,j=this.sigBytes;b=b.sigBytes;this.clamp();if(j%4)for(var g=0;g<b;g++)h[j+g>>>2]|=(a[g>>>2]>>>24-8*(g%4)&255)<<24-8*((j+g)%4);else if(65535<a.length)for(g=0;g<b;g+=4)h[j+g>>>2]=a[g>>>2];else h.push.apply(h,a);this.sigBytes+=b;return this},clamp:function(){var b=this.words,h=this.sigBytes;b[h>>>2]&=4294967295<<
32-8*(h%4);b.length=s.ceil(h/4)},clone:function(){var b=r.clone.call(this);b.words=this.words.slice(0);return b},random:function(b){for(var h=[],a=0;a<b;a+=4)h.push(4294967296*s.random()|0);return new q.init(h,b)}}),v=m.enc={},t=v.Hex={stringify:function(b){var a=b.words;b=b.sigBytes;for(var g=[],j=0;j<b;j++){var k=a[j>>>2]>>>24-8*(j%4)&255;g.push((k>>>4).toString(16));g.push((k&15).toString(16))}return g.join("")},parse:function(b){for(var a=b.length,g=[],j=0;j<a;j+=2)g[j>>>3]|=parseInt(b.substr(j,
2),16)<<24-4*(j%8);return new q.init(g,a/2)}},a=v.Latin1={stringify:function(b){var a=b.words;b=b.sigBytes;for(var g=[],j=0;j<b;j++)g.push(String.fromCharCode(a[j>>>2]>>>24-8*(j%4)&255));return g.join("")},parse:function(b){for(var a=b.length,g=[],j=0;j<a;j++)g[j>>>2]|=(b.charCodeAt(j)&255)<<24-8*(j%4);return new q.init(g,a)}},u=v.Utf8={stringify:function(b){try{return decodeURIComponent(escape(a.stringify(b)))}catch(g){throw Error("Malformed UTF-8 data");}},parse:function(b){return a.parse(unescape(encodeURIComponent(b)))}},
g=l.BufferedBlockAlgorithm=r.extend({reset:function(){this._data=new q.init;this._nDataBytes=0},_append:function(b){"string"==typeof b&&(b=u.parse(b));this._data.concat(b);this._nDataBytes+=b.sigBytes},_process:function(b){var a=this._data,g=a.words,j=a.sigBytes,k=this.blockSize,m=j/(4*k),m=b?s.ceil(m):s.max((m|0)-this._minBufferSize,0);b=m*k;j=s.min(4*b,j);if(b){for(var l=0;l<b;l+=k)this._doProcessBlock(g,l);l=g.splice(0,b);a.sigBytes-=j}return new q.init(l,j)},clone:function(){var b=r.clone.call(this);
b._data=this._data.clone();return b},_minBufferSize:0});l.Hasher=g.extend({cfg:r.extend(),init:function(b){this.cfg=this.cfg.extend(b);this.reset()},reset:function(){g.reset.call(this);this._doReset()},update:function(b){this._append(b);this._process();return this},finalize:function(b){b&&this._append(b);return this._doFinalize()},blockSize:16,_createHelper:function(b){return function(a,g){return(new b.init(g)).finalize(a)}},_createHmacHelper:function(b){return function(a,g){return(new k.HMAC.init(b,
g)).finalize(a)}}});var k=m.algo={};return m}(Math);
(function(s){function p(a,k,b,h,l,j,m){a=a+(k&b|~k&h)+l+m;return(a<<j|a>>>32-j)+k}function m(a,k,b,h,l,j,m){a=a+(k&h|b&~h)+l+m;return(a<<j|a>>>32-j)+k}function l(a,k,b,h,l,j,m){a=a+(k^b^h)+l+m;return(a<<j|a>>>32-j)+k}function n(a,k,b,h,l,j,m){a=a+(b^(k|~h))+l+m;return(a<<j|a>>>32-j)+k}for(var r=CryptoJS,q=r.lib,v=q.WordArray,t=q.Hasher,q=r.algo,a=[],u=0;64>u;u++)a[u]=4294967296*s.abs(s.sin(u+1))|0;q=q.MD5=t.extend({_doReset:function(){this._hash=new v.init([1732584193,4023233417,2562383102,271733878])},
_doProcessBlock:function(g,k){for(var b=0;16>b;b++){var h=k+b,w=g[h];g[h]=(w<<8|w>>>24)&16711935|(w<<24|w>>>8)&4278255360}var b=this._hash.words,h=g[k+0],w=g[k+1],j=g[k+2],q=g[k+3],r=g[k+4],s=g[k+5],t=g[k+6],u=g[k+7],v=g[k+8],x=g[k+9],y=g[k+10],z=g[k+11],A=g[k+12],B=g[k+13],C=g[k+14],D=g[k+15],c=b[0],d=b[1],e=b[2],f=b[3],c=p(c,d,e,f,h,7,a[0]),f=p(f,c,d,e,w,12,a[1]),e=p(e,f,c,d,j,17,a[2]),d=p(d,e,f,c,q,22,a[3]),c=p(c,d,e,f,r,7,a[4]),f=p(f,c,d,e,s,12,a[5]),e=p(e,f,c,d,t,17,a[6]),d=p(d,e,f,c,u,22,a[7]),
c=p(c,d,e,f,v,7,a[8]),f=p(f,c,d,e,x,12,a[9]),e=p(e,f,c,d,y,17,a[10]),d=p(d,e,f,c,z,22,a[11]),c=p(c,d,e,f,A,7,a[12]),f=p(f,c,d,e,B,12,a[13]),e=p(e,f,c,d,C,17,a[14]),d=p(d,e,f,c,D,22,a[15]),c=m(c,d,e,f,w,5,a[16]),f=m(f,c,d,e,t,9,a[17]),e=m(e,f,c,d,z,14,a[18]),d=m(d,e,f,c,h,20,a[19]),c=m(c,d,e,f,s,5,a[20]),f=m(f,c,d,e,y,9,a[21]),e=m(e,f,c,d,D,14,a[22]),d=m(d,e,f,c,r,20,a[23]),c=m(c,d,e,f,x,5,a[24]),f=m(f,c,d,e,C,9,a[25]),e=m(e,f,c,d,q,14,a[26]),d=m(d,e,f,c,v,20,a[27]),c=m(c,d,e,f,B,5,a[28]),f=m(f,c,
d,e,j,9,a[29]),e=m(e,f,c,d,u,14,a[30]),d=m(d,e,f,c,A,20,a[31]),c=l(c,d,e,f,s,4,a[32]),f=l(f,c,d,e,v,11,a[33]),e=l(e,f,c,d,z,16,a[34]),d=l(d,e,f,c,C,23,a[35]),c=l(c,d,e,f,w,4,a[36]),f=l(f,c,d,e,r,11,a[37]),e=l(e,f,c,d,u,16,a[38]),d=l(d,e,f,c,y,23,a[39]),c=l(c,d,e,f,B,4,a[40]),f=l(f,c,d,e,h,11,a[41]),e=l(e,f,c,d,q,16,a[42]),d=l(d,e,f,c,t,23,a[43]),c=l(c,d,e,f,x,4,a[44]),f=l(f,c,d,e,A,11,a[45]),e=l(e,f,c,d,D,16,a[46]),d=l(d,e,f,c,j,23,a[47]),c=n(c,d,e,f,h,6,a[48]),f=n(f,c,d,e,u,10,a[49]),e=n(e,f,c,d,
C,15,a[50]),d=n(d,e,f,c,s,21,a[51]),c=n(c,d,e,f,A,6,a[52]),f=n(f,c,d,e,q,10,a[53]),e=n(e,f,c,d,y,15,a[54]),d=n(d,e,f,c,w,21,a[55]),c=n(c,d,e,f,v,6,a[56]),f=n(f,c,d,e,D,10,a[57]),e=n(e,f,c,d,t,15,a[58]),d=n(d,e,f,c,B,21,a[59]),c=n(c,d,e,f,r,6,a[60]),f=n(f,c,d,e,z,10,a[61]),e=n(e,f,c,d,j,15,a[62]),d=n(d,e,f,c,x,21,a[63]);b[0]=b[0]+c|0;b[1]=b[1]+d|0;b[2]=b[2]+e|0;b[3]=b[3]+f|0},_doFinalize:function(){var a=this._data,k=a.words,b=8*this._nDataBytes,h=8*a.sigBytes;k[h>>>5]|=128<<24-h%32;var l=s.floor(b/
4294967296);k[(h+64>>>9<<4)+15]=(l<<8|l>>>24)&16711935|(l<<24|l>>>8)&4278255360;k[(h+64>>>9<<4)+14]=(b<<8|b>>>24)&16711935|(b<<24|b>>>8)&4278255360;a.sigBytes=4*(k.length+1);this._process();a=this._hash;k=a.words;for(b=0;4>b;b++)h=k[b],k[b]=(h<<8|h>>>24)&16711935|(h<<24|h>>>8)&4278255360;return a},clone:function(){var a=t.clone.call(this);a._hash=this._hash.clone();return a}});r.MD5=t._createHelper(q);r.HmacMD5=t._createHmacHelper(q)})(Math);

function arrayBufferToBinaryString(arrayBuffer) {
    const byteArray = new Uint8Array(arrayBuffer);
    let binaryString = '';
    for (let i = 0; i < byteArray.length; i++) {
        binaryString += String.fromCharCode(byteArray[i]);
    }
    return binaryString;
}

// Calculate MD5 hash using CryptoJS
async function computeMD5(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();

        reader.onload = (event) => {
            try {
                const arrayBuffer = event.target.result;
                const binaryString = arrayBufferToBinaryString(arrayBuffer);

                const hash = CryptoJS.MD5(CryptoJS.enc.Latin1.parse(binaryString));
                resolve(hash.toString(CryptoJS.enc.Hex));
            } catch (err) {
                reject(err);
            }
        };

        reader.onerror = (error) => {
            reject(error);
        };

        reader.readAsArrayBuffer(file); 
    });
}

async function checkDuplicate(file) {
    const md5 = await computeMD5(file);
    const data = new FormData();
    data.append('md5', md5)
    const result = await fetch('/upload_duplicate', 
        {
            method: 'POST',
            body: data
        }).then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        }).catch(error => {
            return {"dup":"0"};
        });
    return result;
}

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

function showPreview(file, url = "", background_color = "#0000") {
    let mediaPreview = document.getElementById('mediaPreview');
    if (mediaPreview) {

        while (mediaPreview.firstChild) {
            mediaPreview.removeChild(mediaPreview.lastChild);
        }

        if (file || url) {
            let mediaElement;
            const isVideo = file ? file.type.startsWith('video') : url.match(/\.(mp4|webm)$/i);
            const size = file ? fileSize(file.size) : "unknown size";
            const infob = document.createElement('b');
            if (isVideo) {
                mediaElement = document.createElement('video');
                mediaElement.controls = true;
                mediaElement.oncanplay = function() {
                    infob.textContent = `${mediaElement.videoWidth} x ${mediaElement.videoHeight}, ${size}`
                };
            } else {
                mediaElement = document.createElement('img');
                mediaElement.onload = function() {
                    infob.textContent = `${mediaElement.naturalWidth} x ${mediaElement.naturalHeight}, ${size}`
                };
            }
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    mediaElement.src = e.target.result;
                }
                reader.readAsDataURL(file);
            } else {
                mediaElement.src = url;
            }
            mediaPreview.appendChild(mediaElement);
            mediaPreview.appendChild(infob);
            mediaPreview.style.display = 'flex';
            mediaPreview.style["background-color"] = background_color;
        } else {
            mediaPreview.style.display = 'none';
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
    return (
        (size / Math.pow(1024, i)).toFixed(2) * 1 +
        ["B", "kB", "MB", "GB", "TB"][i]
    );
}

async function onFileChange(e) {
    if (e){
        if (e.target.files.length){
            const res = await checkDuplicate(e.target.files[0]);
            if (res["dup"] == 1){
                create_flash(`The image ${e.target.files[0].name} exactly matches <a href="/post/view/${res["id"]}">${res["id"]}</a>`)
                e.target.value = "";
                return;
            }
        }
    }
    updateTracker(e);
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
            tracker.style = "color:red";
        } else {
            tracker.style = 'color:inherit';
        }
    } else {
        tracker.innerText = "0MB";
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
function create_flash(text) {
    const section = document.getElementById("Uploadmain")
    let flash = document.getElementById("flash")
    text = `<b class="blink"><span style="float:left;"onclick=document.getElementById("flash").remove();>x</span>${text}</b>`
    if (flash) {
        const newflash = flash.cloneNode(true);
        newflash.innerHTML = text;
        flash.parentElement.replaceChild(newflash,flash)
    }
    else if (section){
        flash = document.createElement("div");
        flash.id = "flash";
        flash.innerHTML = text
        section.parentNode.insertBefore(flash,section)
    }
}

async function distributefiles(){
    const self = document.getElementById("multiFileInput");
    const files = self.files;
    const override = document.getElementById('multiFileOverride').checked;
    const fileInputs = document.querySelectorAll('#large_upload_form input[type="file"]');

    let lastFileInput = null;
    let fileIndex = 0;
    let duplicates = [];

    for (let i = 0; i < fileInputs.length && fileIndex < files.length;) {
        const fileInput = fileInputs[i];

        if (override || !fileInput.files.length) {
            const res = await checkDuplicate(files[fileIndex])
            if (res["dup"] == 1){
                duplicates.push([files[fileIndex].name,res["id"]]);
                fileIndex++;
                continue;
            }
            // If override is true or the input is empty, add the file to this input
            const dt = new DataTransfer();
            dt.items.add(files[fileIndex]);
            fileInput.files = dt.files;
            lastFileInput = fileInput;
            fileIndex++;
        }
        i++;
    }
    if (files.length){
        self.value='';
    }
    if (lastFileInput){
        updateTracker({"target":lastFileInput})
    } else{
        updateTracker();
    }
    if (duplicates.length){
        let text = "";
        if (duplicates.length == 1) {
            const dup = duplicates[0];
            text = `Your image, ${dup[0]}, exactly matches <a href=/post/view/${dup[1]}>${dup[1]}</a>`
        }
        else {
            text = "These images already exist on the site:"
            duplicates.forEach(dup => {
                text = `${text} ${dup[0]} => <a href=/post/view/${dup[1]}>${dup[1]}</a>;`
            })
        }
        create_flash(text);
    }
}

// tag handling
const preset_tags = {
    "red_fox":["red_fur","white_fur","black_nose","orange_eyes"],
    "arctic_fox":["white_fur","black_nose","orange_eyes"],
    "fennec_fox":["tan_fur","black_nose","black_eyes"],
    "gray_fox":["red_fur","white_fur","gray_fur","black_nose","orange_eyes"],
    "bat-eared_fox":["black_fur","gray_fur","black_nose","black_eyes"],
    "bengal_fox":["tan_fur","gray_fur","black_nose","black_eyes"],
    "blanford's_fox":["tan_fur","gray_fur","black_nose","black_eyes"],
    "cape_fox":["tan_fur","gray_fur","black_nose","black_eyes"],
    "corsac_fox":["red_fur","white_fur","gray_fur","black_nose","orange_eyes"],
    "crab-eating_fox ":["black_fur","gray_fur","black_nose","black_eyes"],
    "culpeo_fox":["red_fur","black_fur","gray_fur","black_nose","orange_eyes"],
    "darwin's_fox":["red_fur","black_fur","gray_fur","black_nose","orange_eyes"],
    "hoary_fox":["tan_fur","gray_fur","black_nose","orange_eyes"],
    "island_fox":["red_fur","white_fur","gray_fur","black_nose","orange_eyes"],
    "kit_fox":["tan_fur","white_fur","gray_fur","black_nose","orange_eyes"],
    "pale_fox":["tan_fur","black_nose","orange_eyes"],
    "pampas_fox":["red_fur","gray_fur","black_nose","orange_eyes"],
    "ruppell's_fox":["tan_fur","black_nose","orange_eyes"],
    "sechuran_fox":["white_fur","gray_fur","black_nose","orange_eyes"],
    "south_american_gray_fox":["red_fur","white_fur","gray_fur","black_nose","orange_eyes"],
    "swift_fox":["tan_fur","white_fur","black_nose","orange_eyes"],
    "tibetan_fox":["tan_fur","white_fur","black_fur","gray_fur","black_nose","orange_eyes"],
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
            input.previousChecked = false;
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
        targetElements[index].type = selfElement.type;
        targetElements[index].checked = selfElement.checked;
        targetElements[index].previousChecked = selfElement.previousChecked;
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
// const appears = {"red_fox":["Muzzle"]};

function checkboxRadio(self){
    const suffix = self.id.split("_")[1]
    if (self.value in makeCheckbox){
        makeCheckbox[self.value].forEach((name) => {
            document.querySelectorAll(`input[var="${name}_${suffix}"]`).forEach((el) =>{
                el.type = "checkbox";
            });
        });
    } if (self.value in makeRadio){
        makeRadio[self.value].forEach((name) => {
            document.querySelectorAll(`input[var="${name}_${suffix}"]`).forEach((el) =>{
                el.type = "radio";
            });
        });
    } 
    // if (self.value in appears){
    //     appears[self.value].forEach((name) => {
    //         document.querySelectorAll(`input[name="${name}_${suffix}"]`).forEach((el) =>{
    //             el.disabled = !self.checked;
    //             el.checked = false;
    //         });
    //     });
    // }

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
                    document.querySelectorAll('input[var="' + radio.getAttribute("var") + '"]').forEach((r) => {
                        r.checked = false;
                        r.previousChecked = false;
                    });
                    radio.checked = true;
                    radio.previousChecked = true;
                }
            } else{
                radio.previousChecked = radio.checked;
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

function parse_params() {
    const params = new URLSearchParams(window.location.href);
    if (params.has("media")) {
        const media_url = params.get("media");
        var source = ""
        if (params.has("sourcejs")) {
            source = params.get("sourcejs");
        }
        const url_input = document.getElementById("urldata0")
        if (urlInput) {
            url_input.value = media_url;
            const source_input = document.querySelector("INPUT[name='source0']")
            if (source_input){
                source_input.value = source;
            }
            url_input.dispatchEvent(new Event("input", { bubbles: true }))
            document.getElementById("showinputdata0").click();
            if (typeof get_predictions === 'function'){
                get_predictions("0");
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if(document.getElementById("upload_size_tracker")) {
        document.querySelectorAll("#large_upload_form input[type='file']").forEach((el) => {
            el.addEventListener('change', onFileChange);
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
    parse_params();
    // document.querySelectorAll(".disabledOnStartup").forEach((el) => {
    //     el.disabled = true;
    // });
});
}