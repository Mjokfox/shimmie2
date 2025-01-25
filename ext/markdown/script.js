function markdown_spoiler(el) {
	if (el.style["background-color"] === "unset"){
		el.style["background-color"] = "#000";
		el.style["color"] = "#000";
	} else {
		el.style["background-color"] = "unset";
		el.style["color"] = "unset";
	}
}

function markdown_format(text)
{
	text = text.replaceAll(/\\(.)/gs, function(m, c) {return "\\"+window.btoa(c)+"\\";});
	text = extract_code(text);
	text = encode_links(text);
	text = text.replaceAll(/\*\*\*(.*?)\*\*\*/g, "<b><i>$1</b></i>"); // bi
	let types = ["\\*\\*", "\\*", "___", "__", "~~", "\\^"]; // b, i, sub, u, s, sup
	let replacements = ["b","i","sub","u","s","sup"];
	types.forEach((el, i) => {
		let r = replacements[i];
		let regex = new RegExp(`${el}(.*?)${el}`, "g");
		text = text.replaceAll(regex, `<${r}>$1</${r}>`);
	});
	types = ["####","###","##","#",]; // b, i, u, sub, s, sup
	replacements = ["h4","h3","h2","h1"];
	types.forEach((el, i) => {
		let r = replacements[i];
		let regex = new RegExp(`^${el}\\s(.+)`, "gm");
		text = text.replaceAll(regex, `<${r}>$1</${r}>`);
	});
	text = text.replaceAll(/^&gt;\((\S+)\)\s+(.+)/gm, "<blockquote><i><b>$1</b> said:</i><br><small>$2</small></blockquote>");
	text = text.replaceAll(/^&gt;\s+(.+)/gm, '<blockquote><small>$1</small></blockquote>');
	text = text.replaceAll(/&gt;&gt;(\d+)(#c?\d+)?/gs, '<a class="shm-clink" data-clink-sel="$2" href="/post/view/$1$2">&gt;&gt;$1$2</a>');
	text = text.replaceAll(/\[anchor=(.*?)\](.*?)\[\/anchor\]/gs, '<span class="anchor">$2 <a class="alink" href="#bb-$1" name="bb-$1" title="link to this anchor"> ¶ </a></span>');  // add "bb-" to avoid clashing with eg #top
	text = text.replaceAll(/(^|[^\!])wiki:(\S+)/gs, '$1<a href="/wiki/$2">$2</a>');
	text = text.replaceAll(/\!wiki:(\S+)/gs, '<a href="/wiki/$1">wiki:$1</a>');
	text = text.replaceAll(/^(?:\*|-|\+)\s(.*)/gm, "<li>$1</li>");
	text = text.replaceAll(/^(\d+)\.\s(.*)/gm, "<ol start=\"$1\"><li>$2</li></ol>");
	text = text.replaceAll(/\n\s*\n/g, "\n\n");
	text = text.replaceAll("\n", "\n<br>");
	while (/\[list\](.*?)\[\/list\]/gs.test(text)) {
		text = text.replaceAll(/\[list\](.*?)\[\/list\]/gs, "<ul>$1</ul>");
	}
	while (/\[ul\](.*?)\[\/ul\]/gs.test(text)) {
		text = text.replaceAll(/\[ul\](.*?)\[\/ul\]/gs, "<ul>$1</ul>");
	}
	while (/\[ol\](.*?)\[\/ol\]/gs.test(text)) {
		text = text.replaceAll(/\[ol\](.*?)\[\/ol\]/gs, "<ol>$1</ol>");
	}
	text = text.replaceAll("/\[li\](.*?)\[\/li\]/s", "<li>\\1</li>", text);
	text = text.replaceAll(/<br><(li|ul|ol|\/ul|\/ol)/gs, "<$1");
	text = text.replaceAll(/\[align=(left|center|right)\](.*?)\[\/align\]/gs, "<div style='text-align:$1;'>$2</div>");
	text = text.replaceAll(/\|\|(.*?)\|\|/gs, '<span class="spoiler" title="spoilered text" onclick="markdown_spoiler(this);">$1</span>');
	text = insert_links(text);
	text = insert_code(text);
	text = text.replaceAll(/\\(.+?)\\/gs, function(m, c) {return window.atob(c);});
	return text;
}

function encode_links(text) {
	text = text.replaceAll(/\(((?:(?:https?|ftp|irc|site):\/\/|mailto:))([^\)\[\]]+)\)/gm, function(m, c, c1) {return "({url!}"+window.btoa(c+c1.replaceAll(" ","%20",c1))+"{/url!})";});
	text = text.replaceAll(/((?:(?:https?|ftp|irc|site):\/\/|mailto:)[^\s\)\[\]]+)/gm, function(m, c) {return "{url!}"+window.btoa(c)+"{/url!}";});
	text = text.replaceAll(/\[(.+?)\]\(/gm, function(m, c) {return "[{alt!}"+window.btoa(c)+"{/alt!}](";});
	return text;
}
function insert_links(text) {
	text = text.replaceAll(/\{alt!\}(.+?)\{\/alt!\}/gm,function(m, c) {return  window.atob(c);});
	text = text.replaceAll(/\#\{url!\}(.+?)\{\/url!\}/gm,function(m, c) {return  window.atob(c);});
	text = text.replaceAll(/!\[(.+?)\]\(\{url!\}(.+?)\{\/url!\}\)/gm,function(m, c, c1) {return "<img alt='"+ c +"' src='"+window.atob(c1)+"'>";}); // image
	text = text.replaceAll(/\[(.+?)\]\(\{url!\}(.+?)\{\/url!\}\)/gm,function(m, c,c1) {return "<a href='"+ window.atob(c1)+"'>"+c+"</a>";}); // []()
	text = text.replaceAll(/!\{url!\}(.+?)\{\/url!\}/gm,function(m, c) {return "<img alt='user image' src='"+window.atob(c)+"'>";}); // image
	text = text.replaceAll(/\{url!\}(.+?)\{\/url!\}/gm, function(m, c) {url =  window.atob(c);return `<a href='${url}'>${url}</a>`;});
	text = text.replaceAll(/site:\/\/([^\s\)\[\]\'\"\>\<]+)/gm,"/$1");
	return text;
}

function extract_code(text)
{
	// at the end of this function, the only code! blocks should be
	// the ones we've added -- others may contain malicious content,
	// which would only appear after decoding
	text = text.replaceAll("[code!]", "```");
	text = text.replaceAll("[/code!]", "```");
	return text.replaceAll(/```(.*?)```/gs, function(m, c) {return "[code!]"+window.btoa(c.trim())+"[/code!]";});
}

function insert_code(text)
{
	return text.replaceAll(/\[code!\](.*?)\[\/code!\]/gs, function(m, c) {return "<pre><code>"+window.atob(c)+"</code></pre>";});
}

function to_innerHtml(value) {
    var tempElement = document.createElement("div");
    tempElement.textContent = value;
    return tempElement.innerHTML;
}

function preview_markdown(el) {
	const parent = el.parentNode;
	const textarea = parent.querySelector("TEXTAREA");
	if (textarea){
		var preview_div = parent.querySelector("div.md-preview");
		if (!preview_div) {
			preview_div = document.createElement("div");
			preview_div.classList = "md-preview";

			preview_span = document.createElement("span");
			preview_span.classList = "markdown";
			
			preview_div.appendChild(preview_span);
			parent.insertBefore(preview_div,textarea)
		}
		if (el.previewing){
			textarea.style["display"] = "unset";
			preview_div.style["display"] = "none";
			el.value = "Preview";
		} else {
			textarea.style["display"] = "none";
			preview_div.style["display"] = "block";
			preview_div.firstChild.innerHTML = markdown_format(to_innerHtml(textarea.value));
			el.value = "Edit";
		}
	}
	el.previewing = !el.previewing
}

document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll("SPAN.markdown").forEach(function(e) {
		e.original_innerHTML = e.innerHTML;
		e.innerHTML = markdown_format(e.innerHTML);
		e.querySelectorAll(".shm-clink").forEach(function(el) {
			var target_id = el.getAttribute("data-clink-sel");
			if(target_id && document.getElementById(target_id.replace("#",""))) {
				// if the target comment is already on this page, don't bother
				// switching pages
				// el.setAttribute("href", target_id);
	
				// highlight it when clicked
				el.addEventListener("click", function(e) {
					// This needs jQuery UI
					$(target_id).highlight();
				});
	
				// vanilla target name should already be in the URL tag, but this
				// will include the anon ID as displayed on screen
				el.innerHTML = "Replying to: @"+document.querySelector(target_id+" .username").innerHTML;
			}
		});
	})

	document.querySelectorAll("TEXTAREA:not(.autocomplete_tags)").forEach(function(e) {
		const A = document.createElement("input");
		A.type = "button";
		A.value = "Preview";
		A.style["width"] = "5em";
		A.classList = "markdown-preview";
		A.setAttribute("onClick","preview_markdown(this);") // so it transfers over when copying
		A.previewing = false;
		e.parentNode.insertBefore(A,e);
		e.parentNode.insertBefore(document.createElement("br"),e);
		if (e.nextSibling && e.nextElementSibling.nodeName != "BR")
			e.parentNode.insertBefore(document.createElement("br"),e.nextSibling);
	})
});