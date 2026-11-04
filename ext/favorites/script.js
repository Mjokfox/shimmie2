class Favorites{};

Favorites.update_favorite = async function(imageID,action,auth_token) {
    if (typeof action === "boolean"){
        action = action ? "favorite" : "unfavorite";
    }
    try {
        const res = await fetch("/favourite/votefetch",
            {
                credentials: "same-origin",
                headers: {
                    "User-Agent": "shimmie-js",
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: `auth_token=${auth_token}&image_id=${imageID}&action=${action}`,
                method: "POST"
            }
        );
        if (!res.ok) {
            throw new Error("Favoriting failed");
        }
        return await res.text();
    } catch (e) {
        console.error(e);
    }
}
