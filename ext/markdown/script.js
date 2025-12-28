function toBinaryStr(str) {
	const encoder = new TextEncoder();
	const charCodes = encoder.encode(str);
	return String.fromCharCode(...charCodes);
}

function fromBinaryStr(binary) {
	const bytes = Uint8Array.from({ length: binary.length }, (_, index) =>
	  binary.charCodeAt(index)
	);
	const decoder = new TextDecoder('utf-8');
	return decoder.decode(bytes);
}

function safe_atob(str) {
	return fromBinaryStr(atob(str))
}

function safe_btoa(str) {
	return btoa(toBinaryStr(str))
}

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
	text = text.replaceAll(/\\(.)/gs, function(m, c) {return "\\"+safe_btoa(c)+"\\";});
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
	text = text.replaceAll(/blockquote>\n/gs, 'blockquote>');
	text = text.replaceAll(/\$&gt;&gt;(\d+)/gs, '<widget type="widget" post-id="$1"></widget>');
	text = text.replaceAll(/\!&gt;&gt;(\d+)/gs, '<widget type="thumb" post-id="$1"></widget>');
	text = text.replaceAll(/&gt;&gt;(\d+)(#c?\d+)?/gs, '<a class="shm-clink" data-clink-sel="$2" href="/post/view/$1$2">&gt;&gt;$1$2</a>');
	text = text.replaceAll(/\[anchor=(.*?)\](.*?)\[\/anchor\]/gs, '<span class="anchor">$2 <a class="alink" href="#bb-$1" name="bb-$1" title="link to this anchor"> ¶ </a></span>');  // add "bb-" to avoid clashing with eg #top
	text = text.replaceAll(/search\((.+?)\)/gs, '<a href="/post/list/$1">$1</a>');
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
	text = text.replaceAll(/\\(.+?)\\/gs, function(m, c) {return safe_atob(c);});
	return text;
}

function encode_links(text) {
	text = text.replaceAll(/\(((?:(?:https?|ftp|irc|site):\/\/|mailto:))([^\)\[\]]+)\)/gm, function(m, c, c1) {return "({url!}"+safe_btoa(c+c1.replaceAll(" ","%20",c1))+"{/url!})";});
	text = text.replaceAll(/((?:(?:https?|ftp|irc|site):\/\/|mailto:)[^\s\)\[\]]+)/gm, function(m, c) {return "{url!}"+safe_btoa(c)+"{/url!}";});
	text = text.replaceAll(/@(\S+)/gm, function(m, c) {return "{usr!}"+safe_btoa(c)+"{/usr!}";});
	text = text.replaceAll(/\[(.+?)\]\(/gm, function(m, c) {return "[{alt!}"+safe_btoa(c)+"{/alt!}](";});
	return text;
}
function insert_links(text) {
	text = text.replaceAll(/\{alt!\}(.+?)\{\/alt!\}/gm,function(m, c) {return  safe_atob(c);});
	text = text.replaceAll(/\{usr!\}(.+?)\{\/usr!\}/gm,function(m, c) {let u = safe_atob(c); return `<a href="/user/${u}">@${u}</a>`;});
	text = text.replaceAll(/\#\{url!\}(.+?)\{\/url!\}/gm,function(m, c) {return  safe_atob(c);});
	text = text.replaceAll(/!\[(.+?)\]\(\{url!\}(.+?)\{\/url!\}\)/gm,function(m, c, c1) {return "<img alt='"+ c +"' src='"+safe_atob(c1)+"'>";}); // image
	text = text.replaceAll(/\[(.+?)\]\(\{url!\}(.+?)\{\/url!\}\)/gm,function(m, c,c1) {return "<a href='"+ safe_atob(c1)+"'>"+c+"</a>";}); // []()
	text = text.replaceAll(/!\{url!\}(.+?)\{\/url!\}/gm,function(m, c) {return "<img alt='user image' src='"+safe_atob(c)+"'>";}); // image
	text = text.replaceAll(/\{url!\}(.+?)\{\/url!\}/gm, function(m, c) {let url =  safe_atob(c);return `<a href='${url}'>${url}</a>`;});
	text = text.replaceAll(/\{url!\}(.+?)\{\/url!\}/gm, function(m, c) {let url =  safe_atob(c);return `<a href='${url}'>${url}</a>`;});
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
	return text.replaceAll(/```(.*?)```/gs, function(m, c) {return "[code!]"+safe_btoa(c.trim())+"[/code!]";});
}

function insert_code(text)
{
	return text.replaceAll(/\[code!\](.*?)\[\/code!\]/gs, function(m, c) {return "<pre><code>"+safe_atob(c)+"</code></pre>";});
}

async function get_widget(type, post_id)
{
	const url = `/post/${type}/${post_id}`
	try {
		const response = await fetch(url);
		if (!response.ok) return null;
		else if (type == "widget") return await response.text();
		return `<img alt='user image' src='${response.url}'>`
	} catch (error) {
		console.error(`Error fetching the HTML page for ${url}: ${error}`);
		return null;
	}
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
			preview_div.querySelectorAll("widget").forEach(async function(el) {
				if (el.hasAttribute("type") && el.hasAttribute("post-id")){
					el.outerHTML = await get_widget(el.getAttribute("type"), el.getAttribute("post-id"));
				}
			})
			el.value = "Edit";
		}
	}
	el.previewing = !el.previewing
}

function urlPaste(event) {
	let t = event.target;
	if (t.nodeName !== "TEXTAREA") return;

	let paste = event.clipboardData.getData("text");
	if (!isUrl(paste)) return;

	let start = t.selectionStart;
	let end = t.selectionEnd;
	if (start != end) {
		event.preventDefault();
		let text = t.value;
		let sel = text.substring(start, end);
		t.value = text.substring(0,start) + `[${sel}](${paste})` + text.substring(end);
	}
}

function isUrl(s="") {
    let url;
    try {
      url = new URL(s);
    } catch (_) {
      return false;  
    }
    return url.protocol === "https:" || url.protocol === "http:" || url.protocol === "site:" || url.protocol === "mailto:";
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

	document.querySelectorAll("TEXTAREA.formattable").forEach(function(e) {
		e.placeholder = "Markdown supported";
		e.addEventListener("paste", urlPaste);
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
		if (e.classList.contains("instant-preview")) preview_markdown(A);
	})

	document.querySelectorAll("widget").forEach(async function(el) {
		if (el.hasAttribute("type") && el.hasAttribute("post-id")){
			el.outerHTML = await get_widget(el.getAttribute("type"), el.getAttribute("post-id"));
		}
	})
});