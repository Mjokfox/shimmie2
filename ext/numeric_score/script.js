score_classes = ["score-zero", "score-pos", "score-neg"];
function get_score_class(s) {
    return s == 0 ? "score-zero" : (s > 0 ? "score-pos" : "score-neg")
}

function remove_classes(el, classes) {
    classes.forEach(c => el.classList.remove(c));
}

async function update_vote(imageID,score,score_without,auth_token) {
    if ((Math.abs(score) != 1) || isNaN(imageID) || isNaN(score_without)){
        return;
    }
    const res = await fetch("/numeric_score/votefetch" , 
        {
            credentials: "same-origin",
            headers: {
                "User-Agent": "shimmie-js",
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `auth_token=${auth_token}&image_id=${imageID}&vote=${score}`,
            method: "POST"
        }
    ).then(async res => {
        if (!res.ok) {
            throw new Error("Voting failed");
        }
        return await res.text();
    }).catch(e => {console.error(e)})

    if (Math.abs(res) == 1 || res == "0") {
        const sc = get_score_class(res);
        document.querySelectorAll(".vote-button").forEach(el => remove_classes(el, score_classes));
        document.querySelector(`.vote-button[score="${res}"]`)?.classList.add(sc);

        const new_score = Number(score_without)+Number(res);
        const tsc = get_score_class(new_score);

        const current = document.querySelector(".current-score");
        current.firstElementChild.textContent = new_score;
        remove_classes(current, score_classes);
        current.classList.add(tsc);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    let score_without = 0;
    let my_vote = 0;
    const current = document.body.querySelector(".current-score");
    if (current) {
        my_vote = current.getAttribute("my_vote");
        score_without = Number(current.firstChild.textContent) - Number(my_vote);
    }
    document.body.querySelectorAll(".numeric-score FORM").forEach((el) => {
        const auth_el = el.querySelector("[name=auth_token]");
        const image_id_el = el.querySelector("[name=image_id]");
        const vote_el = el.querySelector("[name=vote]");
        const submit_el = el.querySelector("[type=submit]");
        if (auth_el && image_id_el && vote_el && submit_el) {
            const button = document.createElement("BUTTON");
            button.classList = "vote-button";
            button.textContent = submit_el.value;

            const auth = auth_el.value;
            const image_id = image_id_el.value;
            const vote = vote_el.value;
            if (my_vote == vote) {
                button.classList.add(get_score_class(my_vote))
            }
            button.setAttribute("score", vote);
            button.addEventListener("click", () => {
                update_vote(image_id, vote, score_without, auth);
            })
            
            el.parentElement.replaceChild(button, el);
        }
    })
})