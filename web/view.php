<?
# view.php:
# The meat of HearFromYourMP
# 
# Things this script has to do:
# * View messages for a particular constituency
# * View a thread for a particular message
# * Deal with posting a comment
# 
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: matthew@mysociety.org. WWW: http://www.mysociety.org
#
# $Id: view.php,v 1.44 2006-05-18 17:08:32 matthew Exp $

require_once '../phplib/alert.php';
require_once '../phplib/ycml.php';
require_once '../phplib/fns.php';
require_once '../phplib/constituent.php';
require_once '../../phplib/person.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/mapit.php';
require_once '../../phplib/votingarea.php';

importparams(
    array('constituency', '/^\d+$/', 'Invalid constituency', null),
    array('message', '/^\d+$/', 'Invalid message ID', null),
    array('mode', '/^post$/', 'Invalid mode', null)
);

if ($q_mode == 'post') {
    page_header('Replying to a message');
    view_post_comment_form();
} elseif ($q_message) {
    # Show thread for particular message.
    view_message($q_message);
} elseif ($q_constituency) {
    # Show list of messages for this particular constituency.
    view_messages($q_constituency);
} else {
    # Main page. Show nothing? Or list of constituencies?
    page_header('List of constituencies');
    view_constituencies();
}
page_footer();

# ---

/* mini_signup_form
 * Small signup form. */
function mini_signup_form() {
    if (!is_null(person_if_signed_on())) return;
    ?>
<form method="post" action="/subscribe" name="mini_subscribe" accept-charset="utf-8">
<div id="miniSubscribeBox">
<strong style="align:left;">Sign up to hear from your MP about local issues, and to
discuss them with other constituents</strong><br>
<input type="hidden" name="subscribe" id="subscribe" value="1">
<label for="name">Name:</label> <input type="text" name="name" id="name" size="20">
<label for="email">Email:</label> <input type="text" name="email" id="email" size="25">
<label for="postcode">UK Postcode:</label> <input type="text" name="postcode" id="postcode" size="10">
<input type="submit" value="Sign up">
</div>
</form>
    <?
}

/* view_constituencies
 * Page listing all constituencies having messages. */
function view_constituencies() {
    mini_signup_form();
    $q = db_query("SELECT DISTINCT constituency FROM message where state = 'approved'");
    $out = array();
    while ($r = db_fetch_array($q)) {
        $out[] = $r['constituency'];
    }
    if (count($out)) {
        $areas_info = mapit_get_voting_areas_info($out);
        $out = array();
        foreach ($areas_info as $c_id => $array) {
            if (va_is_fictional_area($c_id)) continue;
            $out[$array['name']] = "<li><a href=\"/view/$c_id\">$array[name]</a></li>\n";
        }
        ksort($out);
        print '<p>The following constituencies have had postings:</p> <ul>';
        foreach ($out as $line) print $line;
        print '</ul>';
    } else {
        print '<p>There are no messages on HearFromYourMP yet.</p>';
    }
}

/* view_messages CONSTITUENCY
 * Page listing all messages in CONSTITUENCY. */
function view_messages($c_id) {
    $area_info = ycml_get_area_info($c_id);
    $rep_info = ycml_get_mp_info($c_id);
    $signed_up = db_getOne('SELECT count(*) FROM constituent WHERE constituency = ?', $c_id);
    $nothanks = db_getRow('SELECT status,website,gender FROM mp_nothanks WHERE constituency = ?', $c_id);
    $q = db_query("SELECT *, extract(epoch from posted) as posted,
                    (select count(*) from comment where comment.message=message.id
                        AND visible<>0) as numposts
                    FROM message
                    WHERE state = 'approved' and constituency = ? ORDER BY message.posted", $c_id);
    $num_messages = db_num_rows($q);
    $num_comments = db_getOne('SELECT COUNT(*) FROM comment,message WHERE visible<>0 AND comment.message = message.id AND message.constituency = ?', $c_id);
    $emails_sent_to_mp = db_getOne('SELECT COUNT(*) FROM mp_threshold_alert WHERE constituency = ?', $c_id);
    $next_threshold = db_getOne('SELECT mp_threshold(?, +1);', $signed_up);
    $latest_message = db_getOne("SELECT EXTRACT(epoch FROM MAX(posted)) FROM message WHERE state='approved' AND constituency = ?", $c_id);
    $twfy_link = 'http://www.theyworkforyou.com/mp/?c=' . urlencode($area_info['name']);
    
    $title = $area_info['name'];
    if (isset($rep_info['name'])) $title = $rep_info['name'] . ', ' . $title;
    page_header($title);
    mini_signup_form();
?>
<h2><?=$area_info['name'] ?></h2>
<?  if (array_key_exists('id', $rep_info) && $rep_info['id'] == '2000005') {
        print '<img alt="" title="Portrait of Stom Teinberg MP" src="images/zz99zz.jpeg" align="right" hspace="5" border="0">';
    } elseif (array_key_exists('image', $rep_info)) {
        print '<img alt="" title="Portrait of ' . htmlspecialchars($rep_info['name']) . '" src="' . $rep_info['image'] . '" align="right" hspace="5">';
    }

    if (isset($rep_info['name'])) {
?>
<p>The MP for this constituency is <?=$rep_info['name'] ?>, <?=$rep_info['party'] ?>.
<?  } else { ?>
<p>There is currently no MP for this constituency.
<?  } ?>
So far, <?="<strong>$signed_up</strong> " . make_plural($signed_up, 'person has', 'people have') ?> signed up to HearFromYourMP in this constituency.
To discover everything you could possibly want to know about what your MP <?=isset($rep_info['name'])?'gets':'got' ?> up to in Parliament,
see their page on our sister site <a href="<?=$twfy_link ?>">TheyWorkForYou</a>.
</p>
<?
    if ($nothanks['status'] == 't') {
        $mp_gender = $nothanks['gender'];
        if ($mp_gender == 'm') { $nomi = 'he is'; $accu = 'him'; $geni = 'his'; }
        elseif ($mp_gender == 'f') { $nomi = 'she is'; $accu = 'her'; $geni = 'her'; }
        else { $nomi = 'they are'; $accu = 'them'; $geni = 'their'; }
        $mp_website = $nothanks['website']; ?>
<p>Unfortunately, <?=$rep_info['name'] ?> has said <?=$nomi ?> not interested in using this
service<?
        if ($mp_website)
            print ', and asks that we encourage users to visit ' . $geni . ' website at <a href="' . $mp_website . '">' . $mp_website . '</a>';
?>. You can still contact <?=$accu ?> directly via our service
<a href="http://www.writetothem.com/">www.writetothem.com</a>.</p>

<p>In accordance with our site policy we will continue to allow signups for
<?=$area_info['name'] ?>. As our FAQ says &quot;There is one list per
constituency, not per MP, and we will continue to accept subscribers
regardless of whether your current MP chooses to use the site or not.
If your MP changes for any reason, we will hand access to the list
over to their successor.&quot;</p>
<?
        return;
    }
?>

<h3>Statistics</h3>
<ul>
<?  if ($num_messages==0) { ?>
    <li>We have sent this MP <?=$emails_sent_to_mp ?> message<?=$emails_sent_to_mp!=1?'s':'' ?> so far, asking them to send an email to their constituents.
We will automatically email them <?=$emails_sent_to_mp>0?'again ':'' ?>when the list in this constituency reaches <?=$next_threshold ?>.
<?  } else { ?>
    <li>We sent this MP <?=$emails_sent_to_mp ?> message<?=$emails_sent_to_mp!=1?'s':'' ?>, asking them to send an email to their constituents.
    <li>This MP has sent <?=$num_messages ?> message<?=$num_messages!=1?'s':'' ?> through
        HearFromYourMP<?=$num_messages>1?', most recently':'' ?> at <?=prettify($latest_message) ?>.
    <li>Constituents have left <?=$num_comments==0?'no':"a total of $num_comments" ?> comment<?=$num_comments!=1?'s':'' ?>
        on this MP's message<?=$num_messages!=1?'s':'' ?>.
<?  } ?>
</ul>

<?
    $out = '';
    while ($r = db_fetch_array($q)) {
        $out .= '<li>' . prettify($r['posted']) . " : <a href=\"/view/message/$r[id]\">$r[subject]</a>. $r[numposts] " . make_plural($r['numposts'], 'reply' , 'replies') . '</li>';
    }
    if ($out) {
        print "<h3>Messages posted</h3> <ul>$out</ul>";
    } else { ?>
<p><em>This MP has not yet sent any messages through HearFromYourMP.</em></p>
<?
    }
}

/* view_message MESSAGE
 * Page displaying the given MESSAGE and comments. */
function view_message($message) {
    $r = message_get($message);
    $content = comment_prettify($r['content']);
    $content = preg_replace('#((<p>\*.*?</p>\n)+)#e', "'<ul>'.str_replace('<p>*', '<li>', '$1') . \"</ul>\n\"", $content);
    $c_id = $r['constituency'];
    $rep_info = ycml_get_mp_info($c_id);
    $area_info = ycml_get_area_info($c_id);
    page_header($r['subject'] . ' - ' . $rep_info['name'] . ', ' . $area_info['name']);
    mini_signup_form();
    print '<div id="message"><h2>' . $r['subject'] . '</h2> <p>Posted by <strong>' . $rep_info['name']
        . ', MP for ' . $area_info['name'] . ', at ' . prettify($r['epoch']) . '</strong>:</p> <blockquote><p>' . $content . '</p></blockquote>';
    $next = db_getOne("SELECT id FROM message WHERE state = 'approved' and constituency = ? AND posted > ?", array($c_id, $r['posted']) );
    $prev = db_getOne("SELECT id FROM message WHERE state = 'approved' and constituency = ? AND posted < ?", array($c_id, $r['posted']) );
    print '<p align="right">';
    if ($prev) print '<a href="/view/message/' . $prev . '">Previous message</a> | ';
    print '<a href="/view/' . $c_id . '">Messages for this constituency</a>';
    if ($next) print ' | <a href="/view/message/' . $next . '">Next message</a>';
    print '</p>';
    print '</div>';
    $cc = db_getAll('select comment.id, refs, name, email, website, extract(epoch from date) as date, content, posted_by_mp from comment,person where person_id = person.id and message = ? and visible <> 0 order by refs || \',\' || comment.id, date', $message);
    if (count($cc))
        print '<h3>Comments</h3> <ul id="comments">' . comment_show($cc, 0, count($cc) - 1) . '</ul>';

    if (get_http_var('showform')) {
        $r = array();
        $r['reason_web'] = _('Before posting to HearFromYourMP, we need to confirm your email address and that you are subscribed to this constituency.');
        $r['reason_email'] = _("You'll then be able to post to the site, as long as you are subscribed to this constituency.");
        $r['reason_email_subject'] = _("Post to HearFromYourMP");
        $P = person_signon($r);
    } else {
        $P = person_if_signed_on();
    }
    if (!is_null($P)) {
        if (person_allowed_to_reply($P->id(), $c_id, $message)) {
            comment_form($P);
        } else {
            print '<p id="formreplace">You are not subscribed to HearFromYourMP in this constituency, or subscribed after this message was posted.</p>';
        }
    } else { ?>
<p id="formreplace">If you are subscribed to HearFromYourMP in this constituency,
<a href="/view/message/<?=$message ?>/reply">log in</a> to post a reply.
<br>Otherwise, if you live in the UK, 
<a href="/subscribe?r=/view/message/<?=$message ?>">sign up</a> in order to
HearFromYourMP.
</p>
<?
    }
}

function comment_show($cc, $first, $last) {
    global $q_message;
    $html = '';
    for ($i = $first; $i <= $last; ++$i) {
        $r = $cc[$i];
        $r['posted_by_mp'] = ($r['posted_by_mp']=='t') ? true : false;
        $html .= '<li';
        if ($r['posted_by_mp']) $html .= ' class="by_mp"';
        $html .= '>' . comment_show_one($r);
/*      XXX COMMENTED OUT AS NO THREADING TO START
        $html .= '<a href="view?mode=post;article=$q_message;replyid=$id">Reply to this</a>.';
        # Consider whether the following comments are replies to this comment.
        $R = "$refs,$id";
        for ($j = $i + 1; $j <= $last && preg_match("/^$R(,|$)/", $cc[$j][1]); ++$j) {}
        --$j;
        if ($j > $i)
            $html .= "<ul>" . comment_show($cc, $i + 1, $j) . "</ul>";
        $i = $j;
*/
        $html .= "</li>";
    }
    return $html;
}

function message_get($id) {
    $r = db_getRow("SELECT *,extract(epoch from posted) as epoch FROM message WHERE state = 'approved' and id = ?", $id);
    if (!$r)
        err('Unknown message ID');
    return $r;
}

function view_post_comment_form() {
    global $q_text, $q_h_text, $q_emailreplies, $q_replyid, $q_counter, $q_message, $q_Post;
    importparams(
        array('text', '//', '', null),
        array('emailreplies', '/^1$/', '', null),
        array('replyid', '/^\d+$/', '', null),
        array('counter', '/^\d+$/', '', null),
        array('Post', '/^Post$/', '', null)
    );

    $r = array();
    $r['reason_web'] = _('Before posting to HearFromYourMP, we need to confirm your email address and that you are subscribed to this constituency.');
    $r['reason_email'] = _("You'll then be able to post to the site, as long as you are subscribed to this constituency.");
    $r['reason_email_subject'] = _("Post to HearFromYourMP");
    $P = person_signon($r);

    $r = message_get($q_message);
    $constituency = $r['constituency'];
    if (!person_allowed_to_reply($P->id(), $constituency, $q_message)) {
        print '<div class="error">Sorry, but you are not subscribed to this constituency, or you subscribed after this message was posted.</div>';
        return false;
    }

    /* Need to make sure that this person hasn't posted two comments already in
     * the last 24 hours. */
    if (db_getOne("select count(id) from comment where person_id = ? and date > current_timestamp - '24 hours'::interval", $P->id()) >= 2) {
        print '<div class="error">Sorry, but you have already posted two comments in the last 24 hours.</div>';

        if (!is_null($q_text) && strlen($q_text) > 0) {
            /* Show them the text of their comment so that they can save it. */
            print '<p>Here is the text of your comment. You can save this page (using File | Save) or cut-and-paste the text into another program if you would like to keep your comment and post it at a later date:</p>';
            $content = comment_prettify($q_text);
            print "<div><p>$content</p></div>";
        }
        
        return false;
    }

    if (!is_null($q_replyid)) {
        if (db_getOne('select count(*) from comment where id = ? and visible <> 0', $q_replyid) != 1)
            err("Bad reply ID $replyid");
        print '<p><em>This is the comment to which you are replying:</em></p>'
        . '<blockquote>' . comment_show_one(db_getRow('SELECT id, author, email, link, date, content FROM comment WHERE id = ?', $replyid)) . '</blockquote>';
    }

    $preview = '';
    if (!is_null($q_counter)) {
        $website = $P->website_or_blank();
        $preview = '<h3>Previewing your comment</h3> <ul id="comments"><li><p><em>Not yet</em> posted by ';
        if ($website) $preview .= "<a href=\"$website\">";
        $preview .= $P->name();
        if ($website) $preview .= '</a>';
        $preview .= ':</p> <div>' . comment_prettify($q_text) . '</div></li></ul>';
    }

    if (!preg_match('#[^\s]#', $q_text))
        $q_counter = null;

    if (!$q_Post || is_null($q_counter)) {
        print $preview;
        comment_form($P);
    } else {
        $refs = '';
        if ($q_replyid != '') {
            $refs = db_getOne('select refs from comment where id = ?', $q_replyid);
            $refs .= ",$q_replyid";
        }

        $posted_by_mp = constituent_is_mp($P->id(), $constituency);
        db_query('insert into comment (id, message, refs, person_id, ipaddr, content, visible, posted_by_mp)
            values (comment_next_id(), ?, ?, ?, ?, ?, ?, ?)', array($q_message, $refs, $P->id(),
            $_SERVER['REMOTE_ADDR'], $q_text, 1, $posted_by_mp));
        if ($q_emailreplies)
            alert_signup($P->id(), $q_message);
        db_commit();

        print '<p>Thank you for your comment. You can <a href="/view/message/' . $q_message . '">view it here</a>.</p>';
    }
}

function comment_form($P) {
    global $q_message, $q_counter, $q_h_text, $q_emailreplies;
    if (is_null($q_counter))
        $counter = 0;
    else
        $counter = $q_counter + 1;
?>
<form id="commentform" action="/view" method="post" accept-charset="utf-8">
<input type="hidden" name="mode" value="post">
<input type="hidden" name="counter" value="<?=$counter ?>">
<input type="hidden" name="message" value="<?=$q_message ?>">
<? /* NO THREADING <input type="hidden" name="replyid" value=""> */ ?>
<h2>Post a reply</h2>
<p><em>Note that you may post at most two replies in any given 24-hour period</em></p>
<p><label for="text">Message:</label><textarea name="text" id="text" rows="10" cols="50"><?=$q_h_text ?></textarea></p>
<p><input<? if ($q_emailreplies) print ' checked'; ?> type="checkbox" id="emailreplies" name="emailreplies" value="1"> <label for="emailreplies">Email me future comments to this message</label></p>
<input type="submit" name="Preview" value="Preview">
<? if ($counter>0) print '<input type="submit" name="Post" value="Post">'; ?>
</form>
<?
}

function person_allowed_to_reply($person_id, $constituency, $message) {
    $signed_up = db_getOne('SELECT constituent.id FROM constituent,message
                            WHERE person_id = ? AND constituent.constituency = ? AND message.id = ?
                            AND creation_time<=posted',
                            array($person_id, $constituency, $message));
    if ($signed_up) return true;
    return false;
}

?>
