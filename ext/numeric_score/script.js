score_classes = "score-zero score-pos score-neg";
function get_score_class(s) {
    return s == 0 ? "score-zero" : (s > 0 ? "score-pos" : "score-neg")
}

async function update_vote(imageID,score,score_without,auth_token) {
    if ((Math.abs(score) != 1) || isNaN(imageID) || isNaN(score_without)){
        return;
    }
    const res = await fetch("/numeric_score/vote" , 
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
        const sc = get_score_class(res)
        $(".vote-button").removeClass(score_classes);
        $(`.vote-button[score=${res}]`).addClass(sc);

        const new_score = Number(score_without)+Number(res);
        const tsc = get_score_class(new_score)
        $display = $(".current-score b").text(new_score);
        $display = $(".current-score");
        $display.removeClass(score_classes);
        $display.addClass(tsc);
    }
}
