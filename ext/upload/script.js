const large_upload_form = document.getElementById("large_upload_form")
if (large_upload_form) large_upload_form.style.display = "none"; // quick!!!

// TODO filesize max..

function create_flash(text) {
    const section = document.getElementById("Uploadmain")
    let flash = document.getElementById("flash")
    html = `<b class="blink"><span style="float:left;"onclick=document.getElementById("flash").remove();>x</span>${text}</b>`
    if (flash) {
        const newflash = flash.cloneNode(true);
        newflash.innerHTML = html;
        flash.parentElement.replaceChild(newflash,flash)
    }
    else if (section){
        flash = document.createElement("div");
        flash.id = "flash";
        flash.innerHTML = html
        section.parentNode.insertBefore(flash,section)
    }
}

/** returns a function that when called continuously, it waits for the given delay. 
 * If called with immediate set to true, bypass the delay*/
function debounce(func, delay) {
    let timeout;
    return function (immediate=false, ...args) {
        clearTimeout(timeout);
        if (immediate) {
            func.apply(this, args);
        } else {
            timeout = setTimeout(() => {
                func.apply(this, args);
            }, delay);
        }
    };
}

/**
 * Resize an image object given from a file input
 * @param {File} file 
 * @param {Number} max_width 
 * @param {Number} max_height 
 * @returns {Promise<Blob|null>}
 */
async function resize_file(file, max_width = 192, max_height = 192) {
    const url = URL.createObjectURL(file);
    const blob = await resize_url(url, max_width, max_height);
    URL.revokeObjectURL(url);
    return blob;
}

/**
 * Resize an image object given from a file input
 * @param {File} file 
 * @param {Number} max_width 
 * @param {Number} max_height 
 * @returns {Promise<Blob|null>}
 */
function resize_url(url, max_width = 192, max_height = 192) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = function() {
            const canvas = document.createElement("canvas");
            const context = canvas.getContext("2d");

            if (img.naturalWidth > max_width || img.naturalHeight > max_height) {
                const factor = Math.min(max_width/img.naturalWidth, max_height/img.naturalHeight);

                var width = img.width * factor;
                var height = img.height * factor;
            } else {
                var width = img.naturalWidth;
                var height = img.naturalHeight;
            }

            canvas.width = width;
            canvas.height = height;
            
            context.drawImage(
                img,
                0,
                0,
                width,
                height
            );
            canvas.toBlob((blob) => resolve(blob));
        };
        img.onerror = reject;
        img.src = url;
    });
}


/**
 * Returns if the string given can be interpreted as an url
 * @param {String} s 
 * @returns Boolean
 */
function is_url(s) {
    let url;
    try {
      url = new URL(s);
    } catch (_) {
      return false;  
    }
    return url.protocol === "http:" || url.protocol === "https:";
}

/**
 * Converts a byte size to a human readable format
 * @param {Number} size 
 * @returns 
 */
function fileSize(size) {
    var i = Math.floor(Math.log(size) / Math.log(1024));
    return (
        (size / Math.pow(1024, i)).toFixed(2) * 1 +
        ["B", "kB", "MB", "GB", "TB"][i]
    );
}

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

function arrayBufferToBinaryString(buff) {
    const arr = new Uint8Array(buff);
    let s = '';
    for (let i = 0; i < arr.length; i++) {
        s += String.fromCharCode(arr[i]);
    }
    return s;
}

async function computeMD5(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (event) => {
            try {resolve(CryptoJS.MD5(CryptoJS.enc.Latin1.parse(arrayBufferToBinaryString(event.target.result))).toString(CryptoJS.enc.Hex))} 
            catch (er) {reject(er);}
        };
        reader.onerror = (e) => reject(e);
        reader.readAsArrayBuffer(file); 
    });
}

/**
 * An input row on the upload page, controls events within itself and stores all necessary data
 */
class UploadPanel {
    /** @type {UploadPage} */
    parent;
    index = 0;
    background_color;

    /** @type {HTMLElement[]} */
    main_nodes = [];
    /** @type {HTMLElement} */
    row;
    /** @type {HTMLElement} */
    file_input;
    /** @type {HTMLElement} */
    browse_button;
    /** @type {HTMLElement} */
    url_input;
    /** @type {HTMLElement} */
    tag_input;
    /** @type {HTMLElement} */
    custom_tag_input;
    /** @type {HTMLElement} */
    tag_display;
    /** @type {HTMLElement} */
    selector_panel;
    /** @type {HTMLElement} */
    cancel_button;
    /** @type {HTMLElement} */
    input_button;
    /** @type {HTMLElement} */
    preview_button;
    /** @type {HTMLElement} */
    copy_button;
    /** @type {HTMLElement[]} */
    small_form_inputs;

    previewing_file = false;
    preview_active = false;
    panel_active = false;
    recovered_inputs = false;
    changed_inputs = false;
    is_video = false;

    checkbox_tags = [];
    radio_tags = {};
    select_tags = {};

    /** @type {File|undefined} */
    resized_file;
    /** @type {File|undefined} */
    recovered_resized_file;

    /** @type {CallableFunction[]} */
    panel_listeners = [];

    /**
     * @param {Number} index 
     * @param {String} media_accept 
     * @param {HTMLElement} table 
     * @param {UploadPage} parent 
     */
    constructor(index, media_accept, table, parent) {
        this.parent = parent;
        this.index = Number(index);
        this.background_color = `hsl(${index * 30} 100% 50% / 0.2)`;
        this.row = build("TR", {
            style: `background-color: ${this.background_color};`,
        });

        this.row.addEventListener("pointerenter", e => {
            if (!this.file_active && !this.url_active) return;
            if (e.pointerType == "touch" || e.pointerType == "") return;
            this.parent.hover_preview(this.index);
        });
        this.row.addEventListener("pointerleave", e => {
            if (!this.file_active && !this.url_active) return;
            if (e.pointerType == "touch" || e.pointerType == "") return;
            this.parent.reset_hover_preview(this.index)
        });

        const file_select = build("TD", { class: "upload-file-select" });
        this.cancel_button = build("DIV", {class: "upload-controls"}, "✖");
        this.cancel_button.addEventListener("click", () => this.clear_file());
        // Try to get the already existing file input, to keep browser cached files
        this.file_input = document.getElementById("data" + index);
        if (!this.file_input) {
            this.file_input = build("INPUT", {
                type: "file",
                accept: media_accept,
                name: "data" + index + "[]",
                style: "display:none;",
                multiple: true
            });
        } else {
            this.file_input.value = "";
            this.file_input.style.display = "none";
        }
        this.file_input.addEventListener("change", () => this.file_input_change());
        this.browse_button = build("INPUT", {
            type: "button",
            value: "Browse...",
            class: "browse-button"
        });
        this.browse_button.addEventListener("click", () => this.file_input.click());

        if (!parent.transload) {
            file_select.colSpan = "2";
            this.browse_button.style.width = "unset";
        }
        this.parent.file_storage.append(this.file_input);
        file_select.append(index, this.cancel_button, this.browse_button);

        this.url_input = build("INPUT", {class: "url-input", type: "text", name: "url" + index});
        this.url_input.addEventListener("input", () => this.url_debounce());
        this.url_input.addEventListener("blur", () => this.url_debounce(true));

        this.input_button = build("DIV", {class: "upload-controls"}, "show");
        this.input_button.addEventListener("click", () => this.toggle_panel());
        this.preview_button = build("DIV", {class: "upload-controls preview-hide-wide"}, "Preview");
        this.preview_button.addEventListener("click", () => this.preview());
        this.row.append(file_select);
        if (parent.transload) this.row.append(build("TD", this.url_input), )
        this.row.append(
            build("TD", {class: "upload-controls-container"}, this.input_button), 
            build("TD", {class: "upload-controls-container"}, this.preview_button)
        );

        this.selector_panel = parent.selector_panel.with_suffix(index, true);
        this.selector_panel.querySelector(".upload-tags-grid").addEventListener("click", this.panel_input.bind(this));

        const small_form = this.selector_panel.querySelector(".small-upload-form");
        this.small_form_inputs = Array.from(small_form.querySelectorAll(`INPUT:not([name=tags${index}]), SELECT, TEXTAREA`));
        this.small_form_inputs.push(this.url_input);
        small_form.addEventListener("change", this.form_change.bind(this));
        this.url_input.addEventListener("change", this.form_change.bind(this));

        this.tag_input = this.selector_panel.querySelector(`[name=tags${index}]`);
        this.tag_input.style.display = "none";

        this.custom_tag_input = build("TEXTAREA", {class: "autocomplete_tags", placeholder: "Custom tags"});
        this.custom_tag_input.addEventListener("input", () => this.update_tags())
        this.tag_input.parentElement.append(this.custom_tag_input);
        if (typeof(elementInit) === "function") elementInit(this.custom_tag_input);

        const panel_footer = build("DIV", build("B", "Tags from this panel:"));
        this.tag_display = build("TEXTAREA", {class: "upload-tag-preview", readOnly: true});

        const panel_actions = build("DIV", {class: "upload-panel-actions"});
        this.copy_button = build("INPUT", {class: "copy-button", type: "button", value: "Copy tags to:"});
        this.copy_button.addEventListener("click", () => this.copy_to())
        this.copy_input = build("INPUT", {class: "copy-input", type: "number", value: index, min: 0, max: parent.max_panels});
        this.clear_button = build("INPUT", {class: "clear-button", type: "button", value: "Reset tags"});
        this.clear_button.addEventListener("click", () => this.clear_tags());
        panel_actions.append(this.copy_button, this.copy_input, this.clear_button);
        panel_footer.append(this.tag_display, panel_actions);
        this.selector_panel.append(panel_footer);

        this.main_nodes = [this.row, build("TR", {style: `background-color: ${this.background_color};`}, build("TD", {colSpan: 100}, this.selector_panel))]
        table.append(...this.main_nodes);
    }

    delete() {
        this.file_input.value = "";
        this.main_nodes.forEach(el => el.remove());
    }

    /**
     * @param {{checkbox: String[], radio: Object, select: Object, small_form_data: Object}} data 
     */
    from_data(data) {
        let something_changed = false;
        if (data.checkbox) {
            data.checkbox.forEach(tag => {
                const el = this.selector_panel.querySelector(`[value=${CSS.escape(tag)}]`);
                if (el) {
                    el.checked = true;
                    this.checkbox_tags.push(tag);
                    something_changed = true;
                }
            });
        }
        if (data.radio) {
            for (let [group, tag] of Object.entries(data.radio)) {
                const el = this.selector_panel.querySelector(`[value=${CSS.escape(tag)}]`);
                if (el) {
                    el.checked = true;
                    this.radio_tags[group] = {el: el, tag: tag};
                    something_changed = true;
                };
            };
        }
        
        if (data.select) {
            for (let [group, tag] of Object.entries(data.select)) {
                const el = this.selector_panel.querySelector(`[value=${CSS.escape(tag)}]`);
                if (el) {
                    el.selected = true;
                    this.select_tags[group] = tag;
                    something_changed = true;
                }
            };
        }
        if (data.custom && data.custom.length) {
            this.custom_tag_input.value = data.custom;
            something_changed = true;
        }
        
        if (data.small_form_data) {
            for (let [name, value] of Object.entries(data.small_form_data)) {
                if (!value.length || value === "?") continue;
                if (name.startsWith("url")) {
                    this.url_input.value = value;
                    something_changed = true;
                } else {
                   const el = this.selector_panel.querySelector(`[name=${CSS.escape(name)}]`);
                    if (el) {
                        el.value = value;
                        something_changed = true;
                    } 
                }
            }
        }
        this.update_tags();
        return something_changed;
    }

    /**
     * @param {File} file 
     */
    transfer_file(file) {
        if (file.resized) {
            this.recovered_resized_file = file
        } else {
            const transfer = new DataTransfer();
            transfer.items.add(file);
            this.file_input.files = transfer.files; 
        }
    }

    check_has_input() {
        for (let el of this.small_form_inputs) {
            if (el.defaultValue !== el.value) return true;
        }
        return this.checkbox_tags.length || Object.keys(this.radio_tags).length || Object.keys(this.select_tags).length || this.custom_tag_input.value.length;
    }

    create_data_export() {
        if (!this.check_has_input()){
            return;
        }
        const radio_flattened = {};
        Object.keys(this.radio_tags).forEach(key => {
            radio_flattened[key] = this.radio_tags[key].tag;
        });
        const small_form_data = {};
        this.small_form_inputs.forEach(el => {
            small_form_data[el.name] = el.value;
        });
        return {checkbox: this.checkbox_tags, radio: radio_flattened, select: this.select_tags, custom: this.custom_tag_input.value, small_form_data: small_form_data}
    }

    async create_image_export(resized = false) {
        /** @type {File} */
        const file = resized ? this.resized_file : this.file_input.files[0];
        return {
            fileBits: await this.get_base64_image(file),
            fileName: file.name,
            options: {type: file.type, lastModified: file.lastModified},
            resized: resized | file.resized};
    }

    /**
     * @param {File} file 
     * @returns {Promise<string>}
     */
    get_base64_image(file) {
        return new Promise((resolve, reject) => {
            const fileReader = new FileReader();
            fileReader.onload = () => resolve(fileReader.result);
            fileReader.readAsDataURL(file);
        });
    }

    update_tags() {
        const panel_tags = [
            this.checkbox_tags.join(" "), 
            Object.values(this.radio_tags).map((en) => en.tag).join(" "),
            Object.values(this.select_tags).join(" ")
        ].join(" ");

        this.tag_display.value = panel_tags
        this.tag_input.value = [
            panel_tags,
            this.custom_tag_input.value
        ].join(" ");
    }

    /** @param {Event} e */
    panel_input(e) {
        switch (e.target.nodeName) {
            case "INPUT":
                switch (e.target.type) {
                    case "checkbox":
                        this.checkbox(e.target);
                        break;
                    case "radio":
                        this.radio(e.target);
                        break;
                    default:
                        return;
                }
                break;
            case "OPTION":
                this.option(e.target);
                break;
            default:
                return;
        }
        this.changed_inputs = true;
    }

    /** @param {Event} _e */
    form_change(_e) {
        this.changed_inputs = true;
    }

    /** @param {HTMLElement} el */
    checkbox(el) {
        const tag = el.value;
        if (el.checked && !this.checkbox_tags.includes(tag)) {
            this.checkbox_tags.push(tag);
        } else {
            const index = this.checkbox_tags.indexOf(tag);
            if (index !== -1) {
                this.checkbox_tags.splice(index, 1);
            }
        }
        this.update_tags();
    }

    /** @param {HTMLElement} el */
    radio(el) {
        const tag = el.value;
        const group = el.ariaLabel;
        if (Object.hasOwn(this.radio_tags, group)) {
            const prev_tag = this.radio_tags[group].tag;
            if (prev_tag === tag) { // itself is clicked again
                el.checked = false;
                delete this.radio_tags[group];
            } else {
                const prev_el = this.radio_tags[group].el;
                prev_el.checked = false;
                this.radio_tags[group] = {el: el, tag: tag};
            }
        } else {
            this.radio_tags[group] = {el: el, tag: tag};
        }
        this.update_tags();
    }

    /** @param {HTMLElement} el */
    option(el) {
        const tag = el.attributes.value;
        const group = el.parentElement.ariaLabel;
        if (tag) {
            this.select_tags[group] = tag.value;
        } else {
            delete this.select_tags[group];
        }
        this.update_tags();
    }

    clear_file() {
        this.file_input.value = '';
        this.preview_active = false;
        this.resized_file = undefined;
        this.recovered_resized_file = null;
        if (!this.update(true, false)) {
            this.hide_panel();
            this.parent.show_first_panel();
        }
        this.parent.export_images();
    }

    file_input_change() {
        this.file_change();
        this.parent.export_images();
    }

    url_debounce = debounce(() => this.url_change(), 3000)

    async url_change() {
        if (!this.update(false)) return;

        if (this.is_video) {
            this.parent.switch_panel(this.index, this.background_color);
            return;
        }

        switch (await this.fetch_actions()) {
            case false:
                this.url_input.value = "";
                this.preview_active = false;
                if (!this.update(true, false)) {
                    this.hide_panel();
                    this.parent.show_first_panel();
                }
                break;
            case true:
                this.parent.switch_panel(this.index, this.background_color);
                break;
            default:
                break;
        }
    }

    async file_change(preview=true) {
        this.previewing_file = false;
        this.parent.files_changed = true;
        this.recovered_resized_file = null;
        if (this.file_input.files.length > 1) {
            const transfer = new DataTransfer();
            for (let i = 1; i < this.file_input.files.length; i++) {
                transfer.items.add(this.file_input.files[i]);
            }
            this.parent.distribute_files(transfer.files, this.index);
            const self_transfer = new DataTransfer();
            self_transfer.items.add(this.file_input.files[0]);
            this.file_input.files = self_transfer.files;
        }
        this.update(false);
        if (this.is_video) {
            this.parent.switch_panel(this.index, this.background_color);
            return true;
        }
        await this.set_resized_file();
        if (!await this.fetch_actions()) {
            this.clear_file();
            return false;
        } else if(preview) {
            this.parent.switch_panel(this.index, this.background_color);
            return true;
        }
        
    }

    set_resized_file() {
        if (this.file_input.files.length && !this.file_input.files[0].type.startsWith('video')) {
            return new Promise((resolve, reject) => {
                const original = this.file_input.files[0];
                resize_file(original)
                    .then(blob => {
                        this.resized_file = new File([blob], original.name, 
                                {type: blob.type, lastModified: original.lastModified}
                        );
                        resolve();
                        }
                    );
            })
        }
    }

    copy_to() {
        const index = this.copy_input.value;
        if (index === this.index) return;
        const data = this.create_data_export();
        if (data) this.parent.set_data(index, data);
    }

    clear_tags() {
        this.checkbox_tags = [];
        this.radio_tags = {};
        this.select_tags = {};
        this.selector_panel.querySelectorAll(".upload-tags-grid input:checked").forEach(e => e.checked = false);
        this.selector_panel.querySelectorAll(".upload-tags-grid select").forEach(e => e.selectedIndex = 0);
        this.update_tags();
    }

    async preview(allow_clearing=true) {
        if (this.file_active) {
            if (!this.preview_active || !this.previewing_file) {
                this.previewing_file = true;
                const file = this.file_input.files?.length > 0 ? this.file_input.files[0] : this.recovered_resized_file;
                this.parent.file_preview(this.index, file, this.background_color);
            }
        } else if (this.url_active) {
            if (!this.preview_active || this.previewing_file) {
                this.previewing_file = false;
                this.parent.url_preview(this.index, this.url_input.value, this.background_color);
            }
        } else if (allow_clearing){
            this.parent.clear_preview();
        }
    }

    /** update preview, and show panel when there is a new file input */
    update(preview=true, allow_clearing=true) {
        this.url_active = is_url(this.url_input.value);
        this.file_active = this.file_input.files?.length > 0 || this.recovered_resized_file;
        if (this.file_active && !this.previewing_file && preview) {
            this.parent.switch_panel(this.index, this.background_color);
        }

        if (preview) {
            this.preview(allow_clearing);
        }

        if (this.file_active) {
            this.cancel_button.style.visibility = "visible";
            this.browse_button.value = this.file_input.files[0].name;
            this.url_input.disabled = true;
            this.url_input.placeholder = "<- file present";
            const file = this.file_input.files?.length > 0 ? this.file_input.files[0] : this.recovered_resized_file;
            this.is_video = file.type.startsWith('video');
            this.show_controls();
        } else if (this.url_active) {
            this.cancel_button.style.visibility = "hidden";
            this.browse_button.value = "browse...";
            this.browse_button.disabled = true;
            this.file_input.disabled = true;
            this.is_video = this.url_input.value.match(/\.(mp4|webm)$/i);
            this.show_controls();
        } else {
            this.hide_controls();
            this.is_video = false;
            return false;
        }
        return true;
    }

    show_controls() {
        this.input_button.style.visibility = "visible";
        this.preview_button.style.visibility = "visible";
    }

    hide_controls() {
        this.url_input.placeholder = "";
        this.browse_button.value = "browse...";
        this.browse_button.disabled = false;
        this.url_input.disabled = false;
        this.file_input.disabled = false;
        this.cancel_button.style.visibility = "hidden";
        this.input_button.style.visibility = "hidden";
        this.preview_button.style.visibility = "hidden";
    }

    show_panel() {
        this.panel_active = true;
        this.selector_panel.style.display = null;
        this.input_button.style.border = "2px dotted white";
        this.preview();
        this.panel_listeners.forEach(f => f(this));
    }

    hide_panel() {
        this.panel_active = false;
        this.selector_panel.style.display = "none";
        this.input_button.style.border = null;
    }

    show_previewer() {
        this.preview_active = true;
        this.preview_button.style.border = "2px dotted white";
    }

    hide_previewer() {
        this.preview_active = false;
        this.previewing_file = false;
        this.preview_button.style.border = null;
    }

    toggle_panel() {
        if (this.panel_active) {
            this.parent.hide_panel(this.index);
        } else {
            this.parent.switch_panel(this.index, this.background_color);
        }
    }

    /** returns false on a duplicate, true on go ahead, void on failure */
    async fetch_actions() {
        const data = new FormData()
        
        if (this.file_active) {
            data.append('resized_image_file', this.resized_file);
            data.append('image_hash', await computeMD5(this.file_input.files[0]))
        } else if (this.url_active) {
            data.append('image_url', this.url_input.value);
        } else return;
        
        return await fetch("api/upload_action",{
            method: 'POST',
            body: data
        }).then(res => {
            if (!res.ok) {
                if (res.status === 400) throw "no input";
                if (res.status === 402) throw "invalid input";
                else throw new Error("http error " + res.status);
            }
            return res.json()})
        .then(async json => {
            console.log(json)
            let async_listeners = [];
            if (!window.dispatchEvent(
                new CustomEvent("upload_result", {cancelable: true, detail: 
                    {json: json, panel: this, async_listeners: async_listeners}
                }
            ))) return false;
            for (const f of async_listeners) {
                if (!await f(json, this)) return false;
            }
            return true;
        }).catch((e) =>{
            if (e == "no input") return
            else if (e == "invalid input") return
            console.error(e);
        });
    }

    /** @param {CallableFunction} func */
    add_panel_listener(func) {
        this.panel_listeners.push(func);
    }
}

class SelectorPanel {
    model;

    constructor(data, type_table, tc_dict) {
        this.model = build("DIV",
            {class: (data.splitview ? "upload-split-view" : undefined)}
        );
        const table = build("TABLE", {class: "form upload-form small-upload-form"});

        const specific_headers = build("TR", {class: "header"});
        Object.values(data.specific_headers).forEach((el) => {
            specific_headers.append(build("TH", el));
        });
        table.append(specific_headers);

        const specific_parts = build("TR", {class: "header"});
        table.append(specific_parts);
        Object.values(data.specific_parts).forEach((el) => {
            if (el.startsWith("<tr")) {
                table.innerHTML += el;
            } else {
                specific_parts.insertAdjacentHTML("beforeend", el);
            }
        });

        this.model.append(table);

        this.model.append(this.tag_panel(type_table, tc_dict));
    }

    input_label(tag, type, group) {
        return build("LABEL", build("INPUT", { value: tag, type: type, ariaLabel: group }), tag);
    }

    all_input(tags, type, parent, group) {
        tags.forEach((tag) => {
            parent.append(this.input_label(tag, type, group));
        });
    }

    all_dropdown(tags, group, overflow = false) {
        const select = build("SELECT", {ariaLabel: group });
        select.append(build("OPTION", overflow ? "More..." : "Select..."));
        tags.forEach(tag => {
            select.append(build("OPTION", {value: tag}, tag));
        });
        return select;
    }

    tag_panel(type_table, tc_dict) {
        const grid = build("DIV", {class: "upload-tags-grid"});
        tc_dict.forEach((row) => {
            const type = type_table[row.type];
            const cell = build(
                "DIV",
                { class: type.class },
                build("DIV", { class: "grid-cell-label" }, row.group),
            );
            grid.append(cell);

            if (row.type === 7) return;

            const tags = row.tags.split(",");
            const rows = Math.max(4, Math.ceil(tags.length / type.cols));
            const tworows = Math.ceil(tags.length / 2);
            const inputs = build("DIV", {
                class: "grid-cell-content",
                style: `--rows:${rows};--tworows:${tworows};`,
            });
            switch (row.type) {
                case 5:
                case 6:
                    inputs.classList.add("dir-row");
                    const overflow = row.type === 5 ? 5 : 3;
                    if (tags.length > overflow) {
                        this.all_input(
                            tags.slice(0, overflow),
                            "radio",
                            inputs,
                            row.group
                        );

                        inputs.append(
                            this.all_dropdown(tags.slice(overflow), row.group, true),
                        );
                    } else {
                        this.all_input(tags, "radio", inputs, row.group);
                    }
                    break;
                case 4:
                    inputs.append(this.all_dropdown(tags, row.group));
                    break;
                default:
                    this.all_input(tags, "checkbox", inputs);
                    break;
            }
            cell.append(inputs);
        });
        return grid;
    }

    with_suffix(suffix, hide=false) {
        const panel = this.model.cloneNode(true);
        panel.querySelectorAll("[name]").forEach((el) => {
            el.name += suffix;
        });
        panel.querySelectorAll("[aria-label]").forEach((el) => {
            el.ariaLabel += suffix;
        });
        panel.querySelectorAll(`.small-upload-form SELECT`).forEach(el => {
            el.defaultValue = el.value;
        });

        if (hide) panel.style.display = "none";
        return panel;
    }
}

class UploadPage {
    initialized = false;
    // data from the page
    max_panels = 3;
    preview_enabled = true;
    splitview = true;
    transload = true;
    media_accept = "";
    common_parts = [];
    specific_headers = [];
    specific_parts = [];
    max_file_size = 0;
    max_total_size = 0;

    // elements
    /** @type {HTMLElement} */
    container;
    /** @type {HTMLElement} */
    file_storage;
    /** @type {HTMLElement} */
    right_column;
    /** @type {HTMLElement} */
    left_column;
    /** @type {HTMLElement} */
    divider;
    /** @type {CSSRule} */
    split_view_rule
    /** @type {HTMLElement} */
    media_preview;
    /** @type {HTMLElement} */
    dropzone;
    /** @type {HTMLElement} */
    table;
    /** @type {SelectorPanel} */
    selector_panel;

    // variables
    slider_resizing = false;
    showing_preview = -1; // -1 means not showing, 0 or larger means the panel index currently shown
    showing_input = -1;
    files_changed = false;
    preview_height = undefined
    /** @type {Promise|undefined} */
    preview_promise = undefined
    /** @type {UploadPanel[]} */
    panels = [];
    /** @type {indexedDB|undefined} */
    db = undefined;

    constructor(data, selector_panel) {
        this.max_panels = data.max_panels;
        this.preview_enabled = data.preview_enabled;
        this.splitview = data.splitview;
        this.transload = data.transload;
        this.media_accept = data.media_accept;
        this.common_parts = data.common_parts;
        this.specific_headers = data.specific_headers;
        this.specific_parts = data.specific_parts;
        this.max_file_size = data.max_file_size;
        this.max_total_size = data.max_total_size;

        this.selector_panel = selector_panel;
    }

    init() {
        // The parent of all
        const form = document.getElementById("file_upload");
        if (!form) {
            console.error("Page loading failed, falling back..");
            if (large_upload_form) large_upload_form.style.display = null;
            return;
        };
        form.addEventListener("submit", (e) => this.submit_check(e));
        // build the page structure
        this.container = qbuild("DIV", "container");
        this.file_storage = build("DIV", {id: "file_storage", style: "display:none;"});
        this.left_column = qbuild("DIV", "left-column");
        this.divider = qbuild("DIV", "divider");
        this.right_column = qbuild("DIV", "right-column");
        const split_view_style = document.createElement('style');
        split_view_style.innerHTML = `
          DIV.upload-split-view {
          }
        `;
        document.head.appendChild(split_view_style);
        this.split_view_rule = split_view_style.sheet.cssRules[0];
        this.slider_init();
        this.container.append(this.left_column,this.divider,this.right_column);

        // TODO guidelines
        const reset_button = build("INPUT", {type: "button", value: "reset all input", class: "reset-button", id: "reset_button"});
        reset_button.addEventListener("click", () => {if (window.confirm("Are you sure you want to reset all your input?"))this.reset()});
        this.media_preview = build("DIV", {class: "media-preview", id: "mediaPreview", style: "display: none;"});
        // TODO multiple files input
        this.dropzone = build("DIV", {id: "dropZone"});
        this.left_column.append(reset_button, this.dropzone);
        this.dropzone_init();

        // Table for now, hoping on using gridboxes at some point
        this.table = build("TABLE", {class: "form upload-form", id: "upload_form"});
        this.table.addEventListener("pointerleave", async () => {
            await this.preview_promise;
            this.media_preview.style.maxHeight = '';
            this.media_preview.style.height = '';
            this.media_preview.classList.remove("media-preview-hover");
            const height = this.media_preview.firstChild?.getBoundingClientRect().height;
            if (height > 0) this.preview_height = height;
        })

        // Submit button
        let submit_button = document.getElementById("uploadbutton");
        if (!submit_button) {
            submit_button = build("INPUT", {type: "submit", value: "Post", id: "uploadbutton"})
        }
        submit_button.style.width = "100%";

        if (this.splitview) {
            this.dropzone.append(this.media_preview, this.table, submit_button);
        } else {
            this.dropzone.append(this.table, submit_button);
            this.right_column.append(this.media_preview)
        }
        

        // common source/tags etc
        Object.values(this.common_parts).forEach((el) => {
            this.table.innerHTML += el; // not entirely safe, but safes alot of extra parsing
        });

        const header = qbuild("TR", "header");
        ["Select file", this.transload ? "or Url" : "", "input"].forEach((el) => {
            header.append(qbuild("TH", "", el));
        });
        header.append(build("TH", {class: "preview-hide-wide"}, "preview"))
        this.table.append(header);

        // Plonk it all down in one go for the user
        form.append(this.container, this.file_storage);

        window.addEventListener("keydown", (e) => {if (e.ctrlKey & e.key === "F5") {this.initialized = false; this.reset();}});

        this.recover_popup();
        if (large_upload_form) form.parentElement.append(large_upload_form);
    }

    push_panel() {
        const length = this.panels.length;
        if (length >= this.max_panels) return false;

        this.panels[length] = new UploadPanel(length, this.media_accept, this.table, this);
        return true;
    }

    file_to_db(index, file) {
        const transaction = this.db.transaction(['saved_images'], 'readwrite');
        const store = transaction.objectStore('saved_images');
        store.put({index: index, file: file});
    }

    file_db_remove(indices) {
        const transaction = this.db.transaction(['saved_images'], 'readwrite');
        const store = transaction.objectStore('saved_images');
        indices.forEach(i => store.delete(i));
    }

    dropzone_init() {
        this.dropzone.addEventListener("dragover", (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (e.dataTransfer.types.includes("Files")) {
                this.dropzone.style.borderStyle = "dashed";
            } else {
                e.dataTransfer.dropEffect = "none"; // Indicate that dropping is not allowed
            }
        });
        this.dropzone.addEventListener("dragleave", (e) =>  {
            e.preventDefault();
            e.stopPropagation();
            this.dropzone.style.borderStyle = "none";
        });
        this.dropzone.addEventListener("drop", async e => {
            e.preventDefault();
            e.stopPropagation();
            this.dropzone.style.borderStyle = "none";
            await this.distribute_files(e.dataTransfer.files);
            this.export_images();
        }, false);
    }

    slider_init() {
        // Mouse events
        this.divider.addEventListener('mousedown', e => this.slider_start(e));
        document.addEventListener('mousemove', e => this.slider_resize(e));
        document.addEventListener('mouseup', e => this.slider_stop(e));

        // Touch events for mobile
        this.divider.addEventListener('touchstart', e => this.slider_start(e));
        document.addEventListener('touchmove', e => this.slider_resize(e));
        document.addEventListener('touchend', e => this.slider_stop(e));
    }

    slider_start(e) {
        this.slider_resizing = true;
        document.body.style.cursor = 'col-resize';
        e.preventDefault(); 
    }

    slider_stop() {
        if (this.slider_resizing) {
            this.slider_resizing = false;
            document.body.style.cursor = '';
        }
    }

    slider_resize(e) {
        if (!this.slider_resizing) return;
        const minBound = 20;
        const maxBound = 80;

        // Determine whether we're dealing with a mouse event or a touch event
        const clientX = e.clientX || e.touches[0].clientX;

        const containerRect = this.container.getBoundingClientRect();
        let newLeftWidth = ((clientX - containerRect.left) / containerRect.width) * 100;
        let inputleft;
        if (newLeftWidth < minBound || newLeftWidth > maxBound) {
            newLeftWidth = newLeftWidth < minBound ? minBound : maxBound;
            inputleft = ((((newLeftWidth / 100) * containerRect.width) + containerRect.left + 10)/window.innerWidth)*100;
        } else {
            inputleft = ((clientX + 10)/window.innerWidth)*100;
        }

        const newRightWidth = 99 - newLeftWidth;

        this.left_column.style.width = `${newLeftWidth}%`;
        this.right_column.style.width = `${newRightWidth}%`;
        this.split_view_rule.style.left = `${inputleft}%`;
        if (this.preview_enabled && !this.splitview)
            this.right_column.firstChild.style.width = `${(newRightWidth/100)*containerRect.width}px`;
    }


    export() {
        const output = {};
        this.panels.forEach(p => {
            const data = p.create_data_export();
            if (data) {
                output[p.index] = data;
            }
        });
        ui_cookie_set("upload_page_save", JSON.stringify(output));
    }

    async export_images() {
        let total_size = 0;
        /** @type {UploadPanel[]} */
        const panels_with_file = [];
        /** @type {Number[]} */
        const indices_without_file = [];
        this.panels.forEach(p => {
            if (p.file_input.files.length) {
                total_size += p.file_input.files[0].size;
                panels_with_file.push(p);
            } else {
                indices_without_file.push(p.index);
            }
        });
        if (this.db) { // indexedDB STRONG
            for (const p of panels_with_file){
                this.file_to_db(p.index, await p.create_image_export());
            };
            if (indices_without_file.length > 0) {
                this.file_db_remove(indices_without_file);
            }
        } else { // localStorage fallback
            const storage_limit = 2500000;
            const output = {};
            if (total_size > storage_limit) { // resize required!
                let size = 0;
                for (const p of panels_with_file){
                    size += p.resized_file.size;
                    if (size > storage_limit) {
                        break;
                    }
                    output[p.index] =  await p.create_image_export(true);
                };
            } else {
                for (const p of panels_with_file){
                    output[p.index] = await p.create_image_export();
                };
            }
            ui_cookie_set("upload_page_saved_images", JSON.stringify(output));
        }
        ui_cookie_set("upload_page_save_date", Date.now());
    }

    async recover_popup() {
        const save_date = ui_cookie_get("upload_page_save_date");
        if (save_date && (Date.now() - save_date)/3600000 > 1) {
            const accept = qbuild("BUTTON", "upload-popup-accept", "Yes");
            const reject_button = qbuild("BUTTON", "upload-popup-reject", "No");
            const container = qbuild("DIV", "upload-popup-container",
                qbuild("DIV", "upload-popup-centering",
                    qbuild("H3", "", `Your input from ${Math.ceil((Date.now() - save_date)/3600000)} hours ago has been found, would you like to restore?`),
                    qbuild("DIV", "upload-popup-actions",
                        accept,
                        reject_button
                    )
                )
            );
            document.body.append(container);
            const out = await new Promise((resolve, reject) => {
                accept.addEventListener("click", () => resolve(true));
                reject_button.addEventListener("click", () => resolve(false));
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
            container.remove();
            if (out) {
                this.try_recover();
            } else {
                this.push_panel();
                this.initialized = true;
            }
        } else {
            this.try_recover();
        }
    }

    try_recover() {
        // try to get a save back!
        const save = ui_cookie_get("upload_page_save");
        if (save) {
            const parsed = JSON.parse(save);
            for (const [strindex, data] of Object.entries(parsed)) {
                const index = Number(strindex)
                while(this.panels.length < index) {
                    this.push_panel();
                }
                if (!this.panels[index]) {
                    this.panels[index] = new UploadPanel(index, this.media_accept, this.table, this);
                }
                this.panels[index].recovered_inputs = this.panels[index].from_data(data)
            }
        }

        UploadPage.initDB().then(db => {
            this.db = db;
            return this.recover_images_db();
        }).then(() => {
            this.recover_images_localstorage();
        }).catch(reason => { // localstorage fallback
            console.error("failed to recover from indexedDB:", reason)
            this.recover_images_localstorage();
        }).finally(() => {
            const length = this.panels.length;
            for (let i = length - 1; i >= 0; i--) {
                if (!this.panels[i]) this.panels.splice(i, 1);
                if (!this.panels[i].file_input.files.length && !this.panels[i].recovered_inputs) {
                    this.panels[i].delete();
                    this.panels.splice(i, 1);
                } else {
                    break;
                }
            }
            this.panels.forEach(p => p.set_resized_file());
            this.push_panel();
            this.initialized = true;
            window.dispatchEvent(new CustomEvent("upload_page_initialized", {detail: {panels: this.panels}}));
            this.show_first_panel();
        });
    }

    set_file_from_recovered_data(index, data) {
        if (!this.panels[index]) this.panels[index] = new UploadPanel(index, this.media_accept, this.table, this);
        if (this.panels[index]?.file_input.files.length > 0) return false;
        const arr = data.fileBits.split(',');
        const bstr = atob(arr[arr.length - 1]);
        let n = bstr.length;
        const u8arr = new Uint8Array(n);
        while(n--){
            u8arr[n] = bstr.charCodeAt(n);
        }
        const file = new File([u8arr], data.fileName, data.options);
        if (data.resized) file.resized = data.resized;
        this.panels[index].transfer_file(file);
        return true;
    }

    recover_images_db() {
        if (!this.db) return;
        const objectStore = this.db.transaction(['saved_images'], 'readonly').objectStore("saved_images");
        return new Promise((resolve, reject) => {
            objectStore.openCursor().onsuccess = (e) => {
                const cursor = e.target.result;
                if (cursor) {
                    this.set_file_from_recovered_data(cursor.key, cursor.value.file);
                    cursor.continue();
                } else {
                    resolve();
                }
            };
        });
    }

    recover_images_localstorage() {
        const save = ui_cookie_get("upload_page_saved_images");

        if (!save || save === "undefined") return;
        const parsed = JSON.parse(save);
        
        for (let [index, data] of Object.entries(parsed)) {
            this.set_file_from_recovered_data(index, data);
        }
    }

    reset() {
        this.hide_panel(this.showing_input);
        this.clear_preview(this.showing_preview);
        this.panels.forEach(p => p.delete());
        this.panels = [new UploadPanel(0, this.media_accept, this.table, this)];
        ui_cookie_remove("upload_page_save");
        ui_cookie_remove("upload_page_saved_images");
        ui_cookie_remove("upload_page_save_date");
        this.db?.transaction(['saved_images'], 'readwrite')?.objectStore("saved_images")?.clear();
    }

    submit_check(e) {
        const unresolved = this.panels.filter(p => !p.url_active && p.recovered_resized_file);
        if (unresolved.length) {
            this.dropzone.append(build("B", "One or more files are a resized smaller copy from a save file, please find the original file again!"))
            e.preventDefault();
            unresolved.forEach(p => {
                p.row.style.border = "1px solid var(--text)";
            });
            return;
        }
        
        // shm_cookie_set("upload_success", "true");
    }

    show_first_panel() {
        let previewed = false;
        this.panels.forEach(p => {
            previewed = previewed | p.update(!previewed, false);
        });
        if (!previewed) {
            this.clear_preview();
        }
    }

    show_last_panel() {
        let previewed = false;
        this.panels.reverse().forEach(p => {
            previewed = previewed | p.update(!previewed);
        });
    }

    /** show media file or url in this.media_preview, file takes precedence when supplied */
    do_preview(background_color, url = "", file, force_height=false) {
        this.preview_promise = new Promise((resolve, reject) => {
            let timeout = setTimeout(resolve, 1000); // just in case
            let mediaElement;
            const isVideo = file ? file.type.startsWith('video') : url.match(/\.(mp4|webm)$/i);
            const size = file ? fileSize(file.size) : "unknown size";
            const infob = document.createElement('b');
            if (isVideo) {
                mediaElement = document.createElement('video');
                mediaElement.controls = true;
                mediaElement.oncanplay = () => {
                    infob.textContent = `${mediaElement.videoWidth} x ${mediaElement.videoHeight}, ${size}`;
                    clearTimeout(timeout);
                    resolve();
                };
            } else {
                mediaElement = document.createElement('img');
                mediaElement.onload = () => {
                    infob.textContent = `${mediaElement.naturalWidth} x ${mediaElement.naturalHeight}, ${size}${file?.resized ? ", resized!!" : ""}`;
                    this.media_preview.dataset.width = mediaElement.naturalWidth;
                    this.media_preview.dataset.height = mediaElement.naturalHeight;
                    this.media_preview.dataset.mime = file ? file.type : "image/jpeg";
                    if (typeof(postPeekAddPeeker) !== "undefined" && !this.media_preview.querySelector(".post-peek")) postPeekAddPeeker();
                    clearTimeout(timeout);
                    resolve();
                };
            }
            if (file) {
                mediaElement.src = URL.createObjectURL(file);
            } else {
                mediaElement.src = url;
            }
            while (this.media_preview.firstChild) {
                this.media_preview.removeChild(this.media_preview.lastChild);
            }
            if (force_height) mediaElement.style.maxHeight = `${this.preview_height}px`;
            this.media_preview.append(mediaElement, infob);
            this.media_preview.style.display = 'flex';
            this.media_preview.style.backgroundColor = background_color;
            
        })
    }

    common_preview(index) {
        if (this.showing_preview !== -1 && this.showing_preview !== index) {
            this.panels[this.showing_preview].hide_previewer();
        }
        this.showing_preview = index;
        this.panels[this.showing_preview].show_previewer();
    }

    url_preview(index, url, background_color) {
        this.common_preview(index);
        this.do_preview(background_color, url);
    }

    file_preview(index, file, background_color) {
        this.common_preview(index);
        this.do_preview(background_color, undefined, file);
    }

    hover_preview(index) {
        if (this.showing_preview == index) return;
        const panel = this.panels[index];
        if (!panel) return;
        const height = this.media_preview.getBoundingClientRect().height;
        this.media_preview.style.maxHeight = `${height}px`;
        this.media_preview.style.height = `${height}px`;
        this.media_preview.classList.add("media-preview-hover");
        this.do_preview(panel.background_color, panel.url_input.value, panel.file_input.files[0], true);
    }

    reset_hover_preview(index) {
        if (this.showing_preview == index) return;
        const panel = this.panels[this.showing_preview];
        if (!panel) {
            this.clear_preview(this.showing_preview);
            return;
        }
        
        this.do_preview(panel.background_color, panel.url_input.value, panel.file_input.files[0]);
        panel.panel_listeners.forEach(f => f(panel));
    }

    clear_preview(index) {
        if (this.panels[index]) {
            this.panels[index].hide_previewer();
        }
        this.showing_preview = -1;
        while (this.media_preview.firstChild) {
            this.media_preview.removeChild(this.media_preview.lastChild);
        }
        this.media_preview.style.display = 'none';
    }

    switch_panel(index, background_color) {
        if (index >= this.panels.length - 1) {
            this.push_panel();
        }
        if (this.showing_input !== -1) {
            this.panels[this.showing_input].hide_panel();
        }
        this.showing_input = index;
        
        this.panels[this.showing_input].show_panel();
        this.right_column.style.backgroundColor = background_color;
    }

    hide_panel(index) {
        this.showing_input = -1;
        if (this.panels[index]) {
            this.panels[index].hide_panel();
        }
    }

    /**
     * 
     * @param {FileList} files 
     */
    async distribute_files(files, skip_index=-1) {
        let file_index = 0;
        let panel_index = 0;
        while (panel_index < this.max_panels) {
            if (panel_index === skip_index) {
                panel_index++;
                if (!this.panels[panel_index]) {
                    this.push_panel();
                }
                continue;
            }
            const p = this.panels[panel_index];
            if (!p || p.file_input.files.length === 1 || p.url_active) {
                panel_index++;
                continue;
            }
            const file = files.item(file_index++);
            if (!file) break;

            p.transfer_file(file);
            if (await p.file_change(true)) { // can be rejected by duplicate user input
                panel_index++
            }
        }
    }

    /**
     * set panel data of index
     * @param {Number} index 
     * @param {{checkbox: String[], radio: Object, select: Object, small_form_data: Object}} data 
     */
    set_data(index, data) {
        if (!this.panels[index]) return;
        this.panels[index].from_data(data);
    }
}

UploadPage.initDB = function() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('upload_page_save', 1);
        request.onerror = (e) => {
            reject(e.target.error);
        };
        request.onsuccess = (e) => {
            resolve(e.target.result);
        };
        request.onupgradeneeded = (e) => {
            const db = e.target.result;
            if (!db.objectStoreNames.contains('saved_images')) {
                db.createObjectStore('saved_images', { keyPath: 'index' });
            }
        };
    });
}

UploadPage.hash_duplicate = function(e) {
    if (!e.detail.json?.hash_duplicate) return;
    const panel = e.detail.panel;
    const hash_duplicate = e.detail.json.hash_duplicate;
    if (hash_duplicate.is) {
        create_flash(`The image ${panel.file_input.files[0].name} exactly matches <a href="/post/view/${hash_duplicate.id}">${hash_duplicate.id}</a>`);
        e.preventDefault();
    }
}

/** @type UploadPage */
let upload_page; 

document.addEventListener('DOMContentLoaded', () => {
    const success = shm_cookie_get("upload_success");
    if (success === "true") {
        UploadPage.delete_state();
    }
    const json_element = document.getElementById("upload_page_data");
    if (!json_element) return;
    const json = JSON.parse(json_element.textContent)
    
    const selector = new SelectorPanel(json.upload_page_data, json.type_table, json.tc_dict);
    upload_page = new UploadPage(json.upload_page_data, selector);
    upload_page.init();
    window.addEventListener("unload", UploadPage.save_state);
    window.addEventListener("pagehide", UploadPage.save_state);
    window.addEventListener("beforeunload", UploadPage.save_state);
    window.addEventListener("visibilitychange", UploadPage.save_state);
    setInterval(UploadPage.save_state, 30000);

    window.addEventListener("upload_result", UploadPage.hash_duplicate);
});

let prev_save = Date.now();
UploadPage.save_state = async function (e) {
    if (upload_page && upload_page.initialized && Date.now() - prev_save > 1000) {
        upload_page.export();
        if (upload_page.files_changed) {
            await upload_page.export_images();
        }
        if (e) { // setInterval does not give an event parameter
            prev_save = Date.now();
        }
        ui_cookie_set("upload_page_save_date", Date.now());
    }
}

UploadPage.delete_state = function() {
    shm_cookie_set("upload_success", "");
    ui_cookie_remove("upload_page_save");
    ui_cookie_remove("upload_page_saved_images");
    ui_cookie_remove("upload_page_save_date");
    UploadPage.initDB().then(db => db.transaction(['saved_images'], 'readwrite').objectStore("saved_images").clear());
}