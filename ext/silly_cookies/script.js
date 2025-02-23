function htmlDecode(input) {
    var doc = new DOMParser().parseFromString(input, "text/html");
    return doc.documentElement.textContent;
}
  
// only do this on the right page
if ((window.location.pathname === "/home" || window.location.pathname === "/") && window.silly_cookies_url && window.silly_cookies_text){
    const front_page = document.getElementById("front-page");
    if (front_page.parentElement){
        function makeFallingCookie(event) {
            const img = document.createElement("img");
            img.src = "/ext/silly_cookies/default/cookie.png";
            img.style.position = "absolute";
            img.style.width = "32px";
            img.style.height = "32px";
            img.style.pointerEvents = "none";
            img.style.left = `${event.pageX - 16}px`;
            img.style.top = `${event.pageY - 16}px`;
            document.body.appendChild(img);

            let posX = event.pageX - 12;
            let posY = event.pageY - 12;
            let velocityX = (Math.random() - 0.5) * 500;
            let velocityY = -500;
            const gravity = 1000;
        
            let time = performance.now();
        
            function animate(timestamp) {
                const deltaTime = (timestamp - time)/1000;
                time = timestamp;

                velocityY += gravity * deltaTime;
                posX += velocityX * deltaTime;
                posY += velocityY * deltaTime;
        
                img.style.left = `${posX}px`;
                img.style.top = `${posY}px`;
    
                if (posY < window.innerHeight + window.scrollY) { // on mobile this is for some reason still not right
                    requestAnimationFrame(animate);
                } else {
                    img.remove();
                }
            }
            requestAnimationFrame(animate);
        }

        // Function to create a draggable and interactive floating image
        function dispenseCookie(clickEvent, targetElement, callback) {
            const img = document.createElement("img");
            img.src = "/ext/silly_cookies/default/cookie.png";
            img.style.position = "absolute";
            img.style.width = "32px";
            img.style.height = "32px";
            img.style.cursor = "grab";
            img.style.left = `${clickEvent.pageX - 16}px`;
            img.style.top = `${clickEvent.pageY - 16}px`;
            document.body.appendChild(img);
            
            let isDragging = false;
            img.addEventListener("mousedown", (e) => {
                const onMouseMove = (e) => {
                    if (isDragging) {
                        img.style.left = `${e.pageX - 16}px`;
                        img.style.top = `${e.pageY - 16}px`;
                    }
                };

                isDragging = !isDragging;
                if (isDragging){
                    img.style.cursor = "grabbing";
                    document.addEventListener("mousemove", onMouseMove)
                } else {
                    img.style.cursor = "grab";
                    const imgRect = img.getBoundingClientRect();
                    const targetRect = targetElement.getBoundingClientRect();
                    const isOverTarget = (
                        imgRect.left < targetRect.right &&
                        imgRect.right > targetRect.left &&
                        imgRect.top < targetRect.bottom &&
                        imgRect.bottom > targetRect.top
                    );
                    if (isOverTarget) {
                        callback(e.pageX,e.pageY);
                        img.remove();
                    }
                    document.removeEventListener("mousemove", onMouseMove);
                } 
            });
        }

        function gibCookie(x,y) {
            const chat_bubble = document.createElement("div");
            chat_bubble.className = "silly-cookie-bubble";
            chat_bubble.textContent = "Thanks for the cookie fren!";
            chat_bubble.style["left"] = `${x + (Math.random() - 0.5)*40}px`
            chat_bubble.style["top"] = `${y + (Math.random() - 0.5)*40}px`
            document.body.appendChild(chat_bubble)
            setTimeout(() => {
                chat_bubble.style["margin-top"] = "0px"
              }, 10)
            setTimeout(() => {
                chat_bubble.remove()
              }, 2500)
        };
        // add the actual stuff
        const container = document.createElement("div");
        const subcontainer = document.createElement("div");
        const image = document.createElement("img");
        const text = document.createElement("span");
        text.classList = "markdown";

        container.className = "silly-cookie-container"

        subcontainer.className = "silly-cookie-subcontainer";

        image.src = window.silly_cookies_url;
        image.addEventListener("click",makeFallingCookie)

        text.innerHTML = htmlDecode(window.silly_cookies_text);
        if (typeof(markdown_format) == "function") {
            text.innerHTML = markdown_format(text.innerHTML);
        }

        if (window.silly_cookies_title){
            const header = document.createElement("span");
            header.classList = "markdown";
            header.innerHTML = window.silly_cookies_title;
            if (typeof(markdown_format) == "function") {
                header.innerHTML = markdown_format(header.innerHTML);
            }
            container.appendChild(header);
        }
        
        subcontainer.appendChild(image);
        subcontainer.appendChild(text);
        
        container.appendChild(subcontainer);
        front_page.parentElement.appendChild(container);

        if (window.silly_cookies_gib){
            const dispenser = document.createElement("button");
            dispenser.className = "silly-cookie-dispenser"
            dispenser.textContent = "Cookie dispenser"
            dispenser.addEventListener("click", (event) => {
                dispenseCookie(event, image, gibCookie);
            });
            subcontainer.appendChild(dispenser);
        }   
    }
}