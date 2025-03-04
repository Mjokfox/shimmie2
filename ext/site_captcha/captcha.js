function shm_cookie_set(name, value) {
	Cookies.set(name, value, {expires: 365, samesite: "lax", path: "/"});
}

async function captcha() {
    const token = await fetch('/captcha/token', 
        {
            method: 'GET'
        }).then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text()
        }).catch(error => {
            console.error('There was a problem with the fetch operation:', error);
            return;
        });
    
    const d = new Date();
    d.setTime(d.getTime() + (7*24*60*60*1000));
    let expires = "expires="+ d.toUTCString();
    document.cookie = `shm_captcha_verified=${token}; expires=${expires}; path=/`;
    window.location.reload();
}
document.addEventListener('DOMContentLoaded', function () {
    const h = document.createElement("H1");
    h.innerHTML = "Automatically verifying, please wait a moment...";
    document.body.appendChild(h);
    captcha();
});
