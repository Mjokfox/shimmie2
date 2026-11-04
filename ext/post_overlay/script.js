class PostOverlay{};
PostOverlay.rating_names = {
    "?": "unknown",
    "s": "safe",
    "q": "questionable",
    "e": "explicit",
}

PostOverlay.init = function() {
    if (!window.post_overlay_enabled) return;
    Object.keys(PostOverlay.render_methods).forEach(key => {
        if (!window.post_overlay_enabled.includes(key)) delete PostOverlay.render_methods[key];
    })
    PostOverlay.active_render_methods = Object.values(PostOverlay.render_methods);

    if (PostOverlay.active_render_methods.length === 0) return;

    if (window.post_overlay_data?.auth_token) {
        PostOverlay.active_render_methods.push(PostOverlay.render_hover_overlay);
    }

    const images = document.querySelectorAll(".shm-image-list .shm-thumb img").forEach(PostOverlay.render_overlay);
}

document.addEventListener("DOMContentLoaded", PostOverlay.init);

PostOverlay.render_overlay = function(img) {
    const a = img.parentElement;
    a.style.position = "relative";

    const overlay = document.createElement("DIV");
    overlay.classList.add("post-overlay", "thumbnail-overlay");

    const post_id = typeof a.dataset.postId === "undefined" ? undefined : Number(a.dataset.postId);

    PostOverlay.active_render_methods.forEach(func => {func(a, overlay, post_id)});

    a.append(overlay);
}

PostOverlay.render_score = function(a, overlay, post_id) {
    const score = Number(a.dataset.numeric_score);
    if (isNaN(score)) return;
    const score_class = NumericScore.get_score_class(score);
    const score_arrow = score > 0 ? "↑\u{FE0E}" : (score < 0 ? "↓\u{FE0E}" : "↕\u{FE0E}");
    const span = document.createElement("SPAN");
    span.classList.add(score_class);
    span.innerHTML = `${score_arrow}\uFE0E ${score}`;
    overlay.append(span);

    if (a.my_score === undefined) {
        if (window.post_overlay_data?.scores && post_id && window.post_overlay_data.scores[post_id]) {
            a.score_without = Number(a.dataset.numeric_score) - window.post_overlay_data.scores[post_id];
            a.my_score = window.post_overlay_data.scores[post_id];
        } else {
            a.score_without = Number(a.dataset.numeric_score);
            a.my_score = 0;
        }
    }
}

PostOverlay.render_favorites = function(a, overlay, post_id) {
    const favorites = Number(a.dataset.favorites);
    if (isNaN(favorites)) return;
    const span = document.createElement("SPAN");
    span.innerHTML = `♥︎\u{FE0E} ${favorites}`;
    span.title = `${favorites} favorite${favorites === 1 ? "" : "s"}`;
    overlay.append(span);

    if (a.favorited === undefined) {
        if (window.post_overlay_data?.favorites?.includes(post_id)) {
            a.favorites_without = favorites - 1;
            a.favorited = true;
        } else {
            a.favorites_without = favorites;
            a.favorited = false;
        }
    }
}

PostOverlay.render_comments = function (a, overlay, post_id) {
    const comment_count = Number(a.dataset.comment_count);
    if (isNaN(comment_count)) return;
    const span = document.createElement("SPAN");
    span.innerHTML = `C ${comment_count}`;
    span.title = `${comment_count} comment${comment_count == 1 ? "" : "s"}`;
    overlay.append(span);
}

PostOverlay.render_rating = function (a, overlay, post_id) {
    const rating = a.dataset.rating;
    if (PostOverlay.rating_names[rating] === undefined) return;

    const span = document.createElement("SPAN");
    span.classList.add(`rating-${PostOverlay.rating_names[rating]}`);
    span.innerHTML = rating.toUpperCase();
    span.title = `Rating: ${PostOverlay.rating_names[rating]}`;
    overlay.append(span);
}

PostOverlay.render_hover_overlay = function(a) {
    const hover_overlay = document.createElement("div");
    hover_overlay.classList.add("post-overlay", "thumbnail-hover-overlay");

    if (window.post_overlay_data.scores) {
        const vote_up = document.createElement("button");
        const vote_down = document.createElement("button");
        vote_up.innerHTML = "⬆︎\u{FE0E}";
        vote_down.innerHTML = "⬇︎\u{FE0E}";
        vote_up.title = "Upvote";
        vote_down.title = "Downvote";
        vote_up.addEventListener("click", PostOverlay.vote_up);
        vote_down.addEventListener("click", PostOverlay.vote_down);

        if (a.my_score) {
            const score_class = NumericScore.get_score_class(a.my_score)
            if (a.my_score > 0) {
                vote_up.classList.add(score_class);
                vote_up.title = "remove my vote";
            } else {
                vote_down.classList.add(score_class);
                vote_down.title = "remove my vote";
            }
        }

        hover_overlay.append(vote_up, vote_down);
    }

    if (window.post_overlay_data.favorites) {
        const favorite = document.createElement("button");
        favorite.innerHTML = "♥︎\u{FE0E}";
        favorite.title = "Favorite";
        favorite.addEventListener("click", PostOverlay.favorite);
        favorite.classList.add("favorite");

        if (a.favorited) {
            favorite.classList.add("fav");
        }

        hover_overlay.append(favorite);
    }

    a.append(hover_overlay);
}

PostOverlay.render_methods = {
    "post_votes": PostOverlay.render_score,
    "favorites": PostOverlay.render_favorites,
    "comments": PostOverlay.render_comments,
    "ratings": PostOverlay.render_rating
}

PostOverlay.active_render_methods = []

PostOverlay.update_overlay = function(a) {
    a.querySelectorAll(".post-overlay").forEach(overlay => overlay.remove());
    PostOverlay.render_overlay(a.querySelector("img"));
}

PostOverlay.vote_up = function(e) {
    e.preventDefault();
    PostOverlay.vote(e.target, 1);
}

PostOverlay.vote_down = function(e) {
    e.preventDefault();
    PostOverlay.vote(e.target, -1);
}

PostOverlay.vote = async function(button, score) {
    if (button.classList.contains("button-loading")) return;
    button.classList.add("button-loading");
    const a = button.parentElement.parentElement;
    const id = a.dataset.postId;
    if (isNaN(Number(id))) return;
    const res = await NumericScore.update_vote(id, score, window.post_overlay_data.auth_token);
    a.dataset.numeric_score = a.score_without + Number(res);
    a.my_score = Number(res);
    PostOverlay.update_overlay(a);
}

PostOverlay.favorite = async function(e) {
    e.preventDefault();
    const button = e.target;
    if (button.classList.contains("button-loading")) return;
    button.classList.add("button-loading");
    const a = button.parentElement.parentElement;
    const id = a.dataset.postId;
    const action = !a.favorited;
    if (isNaN(Number(id))) return;
    await Favorites.update_favorite(id, action, window.post_overlay_data.auth_token);
    a.dataset.favorites = a.favorites_without + action;
    a.favorited = action;
    PostOverlay.update_overlay(a);
}

