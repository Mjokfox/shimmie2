<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\rawHTML;

class PrivMsgTheme extends Themelet
{
    /**
     * @param PM[] $pms
     */
    public function display_pms(Page $page, array $pms, bool $inbox=true, bool $edit=false): void
    {
        global $user;

        $user_cache = [];
        $tof = $inbox ? "From" : "To";
        $html = "
			<table id='pms' class='zebra'>
				<thead><tr><th>R?</th><th>Subject</th><th>$tof</th><th>Date</th><th>Action</th></tr></thead>
				<tbody>";
        foreach ($pms as $pm) {
            $h_subject = html_escape($pm->subject);
            if (strlen(trim($h_subject)) == 0) {
                $h_subject = "(No subject)";
            }
            $uid = $inbox ? $pm->from_id : $pm->to_id;
            if (!array_key_exists($uid, $user_cache)) {
                $p_user = User::by_id($uid);
                $user_cache[$uid] = $p_user;
            } else {
                $p_user = $user_cache[$uid];
            }

            $user_name = $p_user->name;
            $h_user = html_escape($user_name);
            $user_url = make_link("user/".url_escape($user_name));
            
            $pm_url = make_link("pm/read/".$pm->id);
            $del_url = make_link("pm/delete");
            
            $h_date = substr(html_escape($pm->sent_date), 0, 16);
            $readYN = "Y";
            if (!$pm->is_read) {
                $h_subject = "<b>$h_subject</b>";
                $readYN = "N";
            }
            $action_h = make_form($del_url)."
                <input type='hidden' name='pm_id' value='{$pm->id}'>
				<input type='submit' value='Delete'>
			    </form>";
            if ($edit) {
                $edit_url = make_link("pm/edit/".$pm->id);
                $edit_h = "<button class='pm-edit' onclick=\"location.href='$edit_url'\" type='button'>Edit</button>";
                $action_h = "<div style='display:flex;'>$action_h$edit_h</div>";
            } 
            $hb = $p_user->can(Permissions::HELLBANNED) ? "hb" : "";
            $html .= "<tr class='$hb'>
			<td>$readYN</td>
			<td><a href='$pm_url'>$h_subject</a></td>
			<td><a href='$user_url'>$h_user</a></td>
			<td>$h_date</td>
			<td>$action_h</td>
			</tr>";
        }
        $html .= "
				</tbody>
			</table>
		";
        $page->add_block(new Block($inbox ? "Inbox" : "Sent messages", rawHTML($html), "main", 40, $inbox ? "private-messages" : "sent-private-messages"));
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

    public function display_editor(Page $page, int $pm_id, string $subject = "", string $message = ""): void
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
        $page->add_block(new Block("Editing PM", rawHTML($html), "main", 50));
    }

    public function display_message(Page $page, User $from, User $to, PM $pm): void
    {
        $page->set_title("Private Message");
        $page->set_heading($pm->subject);
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Message from {$from->name}", rawHTML(format_text($pm->message)), "main", 10));
    }
}
