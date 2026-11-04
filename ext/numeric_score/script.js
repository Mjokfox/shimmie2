class NumericScore{};
NumericScore.score_classes = ["score-zero", "score-pos", "score-neg"];
NumericScore.get_score_class = function(s){
    return s == 0 ? "score-zero" : (s > 0 ? "score-pos" : "score-neg")
}

NumericScore.remove_classes = function(el, classes) {
    classes.forEach(c => el.classList.remove(c));
}

NumericScore.update_vote = function(imageID,score,auth_token) {
    if ((Math.abs(score) != 1) || isNaN(Number(imageID))){
        return;
    }
    return fetch("/numeric_score/votefetch" , 
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
}

NumericScore.update_counter = function(score, score_without) {
    if (isNaN(Number(score_without))){
        return;
    }
    if (Math.abs(score) <= 1) {
        const sc = NumericScore.get_score_class(score);
        document.querySelectorAll(".vote-button").forEach(el => NumericScore.remove_classes(el, NumericScore.score_classes));
        document.querySelector(`.vote-button[score="${score}"]`)?.classList.add(sc);

        const new_score = Number(score_without)+Number(score);
        const tsc = NumericScore.get_score_class(new_score);

        const current = document.querySelector(".current-score");
        current.firstElementChild.textContent = new_score;
        NumericScore.remove_classes(current, NumericScore.score_classes);
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
                button.classList.add(NumericScore.get_score_class(my_vote))
            }
            button.setAttribute("score", vote);
            button.addEventListener("click", async () => {
                const score = await NumericScore.update_vote(image_id, vote, auth);
                NumericScore.update_counter(score, score_without);
            })
            
            el.parentElement.replaceChild(button, el);
        }
    })
})