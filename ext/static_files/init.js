/**
 * Create an element of type, args can be a key value pair to assign to the element, or a string or HTMLElement, which is appended as child
 * @param {String} type 
 * @param  {...(Object|HTMLElement|String)} args 
 * @returns {HTMLElement}
 */
function build(type, ...args) {
    const e = document.createElement(type);
    args.forEach(a => {
        const t = typeof a;
        if (a instanceof HTMLElement || t === "string") {
            e.append(a);
        } else if (t === "object") {
            for (let [k, v] of Object.entries(a)) {
                if (v === undefined) continue;
                if (k === "class") k = "className";
                e[k] = v;
            }
        }
    });
    return e;
}

/**
 * A faster version of build() which only sets a class and appends children
 * @param {String} type 
 * @param {String?} classes 
 * @param {...(HTMLElement)?} children 
 * @returns {HTMLElement}
 */
function qbuild(type, classes, ...children) {
    const e = document.createElement(type);
    if (classes) e.className = classes;
    if (children.length) e.append(...children);
    return e;
}

function shm_cookie_set(name, value) {
    Cookies.set("shm_" + name, value, {
        expires: 365,
        samesite: "lax",
        path: document.body.dataset.baseHref + "/",
    });
}
function shm_cookie_get(name) {
    return Cookies.get("shm_" + name);
}

function ui_cookie_set(name, value) {
    let key = document.body.dataset.baseHref + "/" + name;
    localStorage.setItem(key, value);
}
function ui_cookie_get(name) {
    let key = document.body.dataset.baseHref + "/" + name;
    let val = localStorage.getItem(key);
    if (val == null) {
        val = Cookies.get("ui-" + name);
        if (val) {
            // migrate old cookie to localstorage
            ui_cookie_set(name, val);
            Cookies.remove("ui-" + name, { path: "/" });
        }
    }
    return val;
}
function ui_cookie_remove(name) {
    let key = document.body.dataset.baseHref + "/" + name;
    localStorage.removeItem(key);
}

function shm_make_link(page, query) {
    let base = document.body.getAttribute("data-base-link") ?? "";
    let joiner = base.indexOf("?") === -1 ? "?" : "&";
    let url = base + page;
    if (query) url += joiner + new URLSearchParams(query).toString();
    return url;
}
function shm_blink(target) {
    target.classList.add("blink");
    setTimeout(() => {
        target.classList.remove("blink");
    }, 5000);
}

function shm_log(section, ...message) {
    window.dispatchEvent(
        new CustomEvent("shm_log", { detail: { section, message } }),
    );
}
window.addEventListener("shm_log", function (e) {
    console.log(e.detail.section, ...e.detail.message);
});
window.addEventListener("error", function (e) {
    shm_log("Window error:", e.error);
});
