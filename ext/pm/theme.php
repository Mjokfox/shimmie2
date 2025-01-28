<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\rawHTML;

class PrivMsgTheme extends Themelet
{
    /**
     * @param PM[] $pms
     */
    public function display_pms(Page $page, array $pms, string $header = "Inbox", bool $to=false, bool $from=false, bool $edit=false, bool $archive=true, bool $delete=true, int $more=0,int $archived=0): void
    {
        global $user;

        $user_cache = [];
        $html = "
			<table id='pms' class='zebra'>
				<thead><tr><th>R?</th><th>Subject</th>" . ($from ? "<th>from</th>" : "") . ($to ? "<th>to</th>" : "") . "<th>Date</th><th>Action</th></tr></thead>
				<tbody>";
        foreach ($pms as $pm) {
            $h_subject = html_escape($pm->subject);
            if (strlen(trim($h_subject)) == 0) {
                $h_subject = "(No subject)";
            }

            $user_html = "";
            if ($from) {
                $uid = $pm->from_id;
                if (!array_key_exists($uid, $user_cache)) {
                    $f_user = User::by_id($uid);
                    $user_cache[$uid] = $f_user;
                } else {
                    $f_user = $user_cache[$uid];
                }
    
                $user_name = $f_user->name;
                $h_user = html_escape($user_name);
                $user_url = make_link("user/".url_escape($user_name));
                
                $user_html .= "<td><a href='$user_url'>$h_user</a></td>";
                $html .= $f_user->can(Permissions::HELLBANNED) ? "<tr class='hb'>" : "<tr>";
            } else $html .= "<tr>";

            if ($to) {
                $uid = $pm->to_id;
                if (!array_key_exists($uid, $user_cache)) {
                    $p_user = User::by_id($uid);
                    $user_cache[$uid] = $p_user;
                } else {
                    $p_user = $user_cache[$uid];
                }
    
                $user_name = $p_user->name;
                $h_user = html_escape($user_name);
                $user_url = make_link("user/".url_escape($user_name));
                
                $user_html .= "<td><a href='$user_url'>$h_user</a></td>";
            }

            $pm_url = make_link("pm/read/".$pm->id);
            $arc_url = make_link("pm/archive");
            $del_url = make_link("pm/delete");
            
            $h_date = substr(html_escape($pm->sent_date), 0, 16);
            
            if (!$pm->is_read) {
                $h_subject = "<b>$h_subject</b>";
                $readYN = "N";
            } else $readYN = "Y";
            $actions = [];
            if ($archive){
                $actions[] = "<div>".make_form($arc_url)."
                    <input type='hidden' name='pm_id' value='{$pm->id}'>
                    <input type='submit' value='Archive'>
                    </form></div>";
            }
            if ($delete){
                $actions[] = "<div class='pm-edit'>".make_form($del_url)."
                    <input type='hidden' name='pm_id' value='{$pm->id}'>
                    <input id='del-{$pm->id}' onclick=\"$('#del-{$pm->id}').hide();$('#con-{$pm->id}').show();\" type='button' value='Delete'>
                    <input id='con-{$pm->id}' style='display:none;' type='submit' value='This will also delete it for the other user, confirm?'>
                    </form></div>";
            }

            if ($edit) {
                $edit_url = make_link("pm/edit/".$pm->id);
                $actions[] = "<button class='pm-edit' onclick=\"location.href='$edit_url'\" type='button'>Edit</button>";
            } 
            $action_h = "<div style='display:flex;'>".implode($actions)."</div>";
            $html .= "
			<td>$readYN</td>
			<td><a href='$pm_url'>$h_subject</a></td>
			$user_html
			<td>$h_date</td>
			<td>$action_h</td>
			</tr>";
        }
        $html .= "
				</tbody>
			</table>
		";
        if ($more != 0) $html .= "<a href= '/pm/list/$more'>See all</a>";
        if ($archived != 0) $html .= "<a style='margin-left:1em' href= '/pm/archived/$archived'>See archived messages</a>";
        $page->add_block(new Block($header, rawHTML($html), "main", 40, $header, hidable:true));
    }

    public function display_composer(Page $page, User $from, User $to, string $subject = ""): void
    {
        global $user;
        $post_url = make_link("pm/send");
        $h_subject = html_escape($subject);
        $to_id = $to->id;
        $form = make_form($post_url);
        $html = <<<EOD
$form
<input type="hidden" name="to_id" value="$to_id">
<table style="width: 400px;" class="form">
<tr><th>Subject:</th><td><input type="text" name="subject" value="$h_subject"></td></tr>
<tr><td colspan="2"><textarea style="width: 100%" rows="6" name="message"></textarea></td></tr>
<tr><td colspan="2"><input type="submit" value="Send"></td></tr>
</table>
</form>
EOD;
        $page->add_block(new Block("Write a PM", rawHTML($html), "main", 50));
    }

    public function display_editor(Page $page, int $pm_id, string $subject = "", string $message = "", int $to_id = null): void
    {
        global $user;
        $post_url = make_link("pm/edit");
        $h_subject = html_escape($subject);
        $form = make_form($post_url);
        $html = <<<EOD
$form
<input type="hidden" name="pm_id" value="$pm_id">
<table style="width: 400px;" class="form">
<tr><th>Subject:</th><td><input type="text" name="subject" value="$h_subject"></td></tr>
<tr><td colspan="2"><textarea style="width: 100%" rows="6" name="message">$message</textarea></td></tr>
<tr><td colspan="2"><input type="submit" value="Send"></td></tr>
</table>
</form>
EOD;
        $page->add_block(new Block("Editing PM" . ($to_id ? " to ".User::by_id($to_id)->name : ""), rawHTML($html), "main", 50));
    }

    public function display_edit_button(Page $page, int $pm_id): void
    {
        global $user;
        $url = make_link("pm/edit/$pm_id");
        $html = "<a href='$url'>Edit</a>";
        $page->add_block(new Block("", rawHTML($html), "main", 49));
    }

    public function display_message(Page $page, User $from, User $to, PM $pm): void
    {
        $page->set_title("Private Message");
        $page->set_heading($pm->subject);
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Message from {$from->name}:", rawHTML("<h2>{$pm->subject}</h2><hr><br>".format_text($pm->message)), "main", 10));
    }
}
