<?
# view.php:
# The meat of HearFromYourMP
# 
# Things this script has to do:
# * View messages for a particular area
# * View a thread for a particular message
# * Deal with posting a comment
# 
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: matthew@mysociety.org. WWW: http://www.mysociety.org
#
# $Id: view.php,v 1.57 2007-10-31 17:15:52 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/alert.php';
require_once '../phplib/constituent.php';
require_once '../phplib/reps.php';
require_once '../phplib/comment.php';
require_once '../../phplib/person.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/mapit.php';

importparams(
    array('area_id', '/^\d+$/', 'Invalid area ID', null),
    array('message', '/^\d+$/', 'Invalid message ID', null),
    array('mode', '/^post$/', 'Invalid mode', null)
);

if ($q_mode == 'post') {
    page_header('Replying to a message');
    view_post_comment_form();
} elseif ($q_message) {
    # Show thread for particular message.
    view_message($q_message);
} elseif ($q_area_id) {
    # Show list of messages for this particular area.
    view_messages($q_area_id);
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
<strong style="align:left;">Sign up to hear from your <?=rep_type() ?> about local issues, and to
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
    $q = db_query("SELECT DISTINCT area_id FROM message where state = 'approved'");
    $out = array();
    while ($r = db_fetch_array($q)) {
        $out[] = $r['area_id'];
    }
    if (count($out)) {
        $areas_info = mapit_get_voting_areas_info($out);
        $out = array();
        foreach ($areas_info as $area_id => $array) {
            if (va_is_fictional_area($area_id)) continue;
            $out[$array['name']] = "<li><a href=\"/view/$area_id\">$array[name]</a></li>\n";
        }
        ksort($out);
        print '<p>The following constituencies have had postings:</p> <ul>';
        foreach ($out as $line) print $line;
        print '</ul>';
    } else {
        echo "<p>There are no messages on $_SERVER[site_name] yet.</p>";
    }
}

/* view_messages CONSTITUENCY
 * Page listing all messages in CONSTITUENCY. */
function view_messages($area_id) {
    $area_info = ycml_get_area_info($area_id);
    $reps_info = ycml_get_reps_for_area($area_id);
    $reps_info_arr = array_values($reps_info);
    $max_created = 0;
    foreach ($reps_info as $rep) {
        if ($rep['whencreated'] > $max_created) $max_created = $rep['whencreated'];
    }
    $signed_up = db_getOne('SELECT count(*) FROM constituent WHERE area_id = ?', $area_id);
    $nothanks = db_getRow('SELECT status,website,gender FROM rep_nothanks WHERE area_id = ?', $area_id);
    $q = db_query("SELECT *, extract(epoch from posted) as posted,
                    (select count(*) from comment where comment.message=message.id
                        AND visible<>0) as numposts
                    FROM message
                    WHERE state = 'approved' and area_id = ? ORDER BY message.posted", $area_id);
    $num_messages = db_num_rows($q);
    $num_comments = db_getOne('SELECT COUNT(*) FROM comment,message WHERE visible<>0 AND comment.message = message.id AND message.area_id = ?', $area_id);
    $emails_sent_to_rep = db_getOne('SELECT COUNT(*) FROM rep_threshold_alert WHERE area_id = ?
        and extract(epoch from whensent) > ?', $area_id, $max_created);
    $emails_sent_to_rep /= count($reps_info);
    $next_threshold = db_getOne('SELECT rep_threshold(?, +1, '.OPTION_THRESHOLD_STEP.');', $signed_up);
    $latest_message = db_getOne("SELECT EXTRACT(epoch FROM MAX(posted)) FROM message WHERE state='approved' AND area_id = ?", $area_id);
    
    $title = $area_info['name'];
    if (count($reps_info)==1 && isset($reps_info_arr[0]['name']))
        $title = $reps_info_arr[0]['name'] . ', ' . $title;
    page_header($title);
    mini_signup_form();

    echo '<h2>', $area_info['name'], '</h2>';
    $reps = array();
    foreach ($reps_info as $rep_info) {
        if (isset($rep_info['id']) && $rep_info['id'] == '2000005') {
            print '<img alt="" title="Portrait of Stom Teinberg MP" src="images/zz99zz.jpeg" align="right" hspace="5" border="0">';
        } elseif (array_key_exists('image', $rep_info)) {
            print '<img alt="" title="Portrait of ' . htmlspecialchars($rep_info['name']) . '" src="' . $rep_info['image'] . '" align="right" hspace="5">';
        }
        $reps[] = $rep_info['name'] . ' (' . $rep_info['party'] . ')';
    }
    $reps = (count($reps) > 1 ? join(', ', array_slice($reps, 0, count($reps)-1)) . ' and ' : '') . $reps[count($reps)-1];

    if (count($reps_info)) {
        echo '<p>The ', make_plural(count($reps_info), rep_type('single'), rep_type('plural')),
            ' for this ', $area_info['type_name'], ' ' , make_plural(count($reps_info), 'is', 'are'),
            ' ', $reps, '.';
    } else {
        echo '<p>There is currently no ', rep_type('single'), ' for this ' . area_type() . '.';
    }
?>

So far, <?="<strong>$signed_up</strong> " . make_plural($signed_up, 'person has', 'people have') ?> signed up to <?=$_SERVER['site_name']?> in this <?=area_type() ?>.
<?
    if (OPTION_AREA_TYPE == 'WMC') {
        $twfy_link = 'http://www.theyworkforyou.com/mp/?c=' . urlencode($area_info['name']);
        echo 'To discover everything you could possibly want to know about what your MP ', 
            isset($rep_infos[0]['name']) ? 'gets' : 'got',
            ' up to in Parliament, see their page on our sister site <a href="',
            $twfy_link, '">TheyWorkForYou</a>.';
    }
    echo '</p>';

    if ($nothanks['status'] == 't') {
        $rep_gender = $nothanks['gender'];
        if ($rep_gender == 'm') { $nomi = 'he is'; $accu = 'him'; $geni = 'his'; }
        elseif ($rep_gender == 'f') { $nomi = 'she is'; $accu = 'her'; $geni = 'her'; }
        else { $nomi = 'they are'; $accu = 'them'; $geni = 'their'; }
        $rep_website = $nothanks['website']; ?>
<p>Unfortunately, <?=$rep_info['name'] ?> has said <?=$nomi ?> not interested in using this
service<?
        if ($rep_website)
            print ', and asks that we encourage users to visit ' . $geni . ' website at <a href="' . $rep_website . '">' . $rep_website . '</a>';
?>. You can still contact <?=$accu ?> directly via our other service
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
<?
    $this_or_these = make_plural(count($reps_info), 'this ' . rep_type('single'), 'these ' . rep_type('plural'));
    $this_or_these_possessive = count($reps_info)>1 ? $this_or_these . '&rsquo;' : $this_or_these . '&rsquo;s';
    if ($num_messages==0) {
        echo '<li>We have sent ', make_plural(count($reps_info), 'this ' . rep_type('single'),
            'these ' . rep_type('plural')), ' ',
            $emails_sent_to_rep, ' ', make_plural($emails_sent_to_rep, 'message'),
            ' so far, asking them to send an email to their constituents.
We will automatically email them ', $emails_sent_to_rep>0 ? 'again ' : '',
            ' when the list in this ' . area_type() . ' reaches ', $next_threshold, '.';
    } else { ?>
    <li>We sent <?=$this_or_these ?> <?=$emails_sent_to_rep ?> <?=make_plural($emails_sent_to_rep, 'message') ?>, asking them to send an email to their constituents.
    <li><?=ucfirst($this_or_these) ?> <?=make_plural(count($reps_info), 'has', 'have') ?> sent <?=$num_messages ?> <?=make_plural($num_messages, 'message') ?> through
        <?=$_SERVER['site_name']?><?=$num_messages>1?', most recently':'' ?> at <?=prettify($latest_message) ?>.
    <li>Constituents have left <?=$num_comments==0?'no':"a total of $num_comments" ?> comment<?=$num_comments!=1?'s':'' ?>
        on <?=$this_or_these_possessive ?> <?=make_plural($num_messages, 'message') ?>.
<?  } ?>
</ul>

<?
    $out = '';
    while ($r = db_fetch_array($q)) {
        $out .= '<li>' . prettify($r['posted']) . " : <a href=\"/view/message/$r[id]\">$r[subject]</a>";
        if (count($reps_info)>1) {
            $out .= ', by ' . $reps_info[$r['rep_id']]['name'];
        }
        $out .= ". $r[numposts] " . make_plural($r['numposts'], 'reply' , 'replies') . '</li>';
    }
    if ($out) {
        print "<h3>Messages posted</h3> <ul>$out</ul>";
    } else { ?>
<p><em><?=ucfirst($this_or_these) ?> <?=make_plural(count($reps_info), 'has', 'have') ?> not yet sent any messages through <?=$_SERVER['site_name']?>.</em></p>
<?
    }
}

/* view_message MESSAGE
 * Page displaying the given MESSAGE and comments. */
function view_message($message) {
    $r = message_get($message);
    $content = comment_prettify($r['content']);
    $content = preg_replace('#<p>\*(.*?)\*</p>#', "<h3>$1</h3>", $content);
    $content = preg_replace('#((<p>\*.*?</p>\n)+)#e', "'<ul>'.str_replace('<p>*', '<li>', '$1') . \"</ul>\n\"", $content);
    $area_id = $r['area_id'];
    $rep_id = $r['rep_id'];
    $rep_info = ycml_get_rep_info($rep_id);
    $area_info = ycml_get_area_info($area_id);
    page_header($r['subject'] . ' - ' . $rep_info['name'] . ', ' . $area_info['name']);
    mini_signup_form();
    $next = db_getOne("SELECT id FROM message
        WHERE state = 'approved' and area_id = ? AND posted > ?
        ORDER BY posted LIMIT 1",
        array($area_id, $r['posted']) );
    $prev = db_getOne("SELECT id FROM message
        WHERE state = 'approved' and area_id = ? AND posted < ?
        ORDER BY posted DESC LIMIT 1",
        array($area_id, $r['posted']) );
    print '<div id="message">';
    print '<p id="nav">';
    if ($prev) print '<a href="/view/message/' . $prev . '">Previous message</a> | ';
    print '<a href="/view/' . $area_id . '">Messages for this ' . area_type() . '</a>';
    if ($next) print ' | <a href="/view/message/' . $next . '">Next message</a>';
    print '</p>';
    print '<h2>' . $r['subject'] . '</h2> <p>Posted by <strong>';
    if (OPTION_AREA_TYPE == 'WMC') {
        $twfy_link = 'http://www.theyworkforyou.com/mp/?c=' . urlencode($area_info['name']);
        echo '<a href="', $twfy_link, '">';
    }
    echo $rep_info['name'];
    if (OPTION_AREA_TYPE == 'WMC') echo '</a>';
    echo '</strong>, ', rep_type('single'), ' for <strong>' . $area_info['name'] . '</strong>, at <strong>' . prettify($r['epoch']) . '</strong>:</p> <blockquote><p>' . $content . '</p></blockquote>';
    print '</div>';

    $cc = db_getAll('select comment.id, refs, name, email, website, extract(epoch from date) as date, content, posted_by_rep from comment,person where person_id = person.id and message = ? and visible <> 0 order by refs || \',\' || comment.id, date', $message);
    if ($cc && count($cc))
        print '<h3>Comments</h3> <ul id="comments">' . comment_show($cc, 0, count($cc) - 1) . '</ul>';

    if (get_http_var('showform')) {
        $r = array();
        $r['reason_web'] = _('Before posting to ' . $_SERVER['site_name'] . ', we need to confirm your email address and that you are subscribed to this ' . area_type() . '.');
        $r['reason_email'] = _("You'll then be able to post to the site, as long as you are subscribed to this " . area_type() . '.');
        $r['reason_email_subject'] = "Post to $_SERVER[site_name]";
        $P = person_signon($r);
    } else {
        $P = person_if_signed_on();
    }
    if (!is_null($P)) {
        if (person_allowed_to_reply($P->id(), $area_id, $message)) {
            comment_form($P);
        } else {
            print '<p id="formreplace">You are not subscribed to ' . $_SERVER['site_name'] . ' in this ' . $area_info['type_name'] . ', or subscribed after this message was posted.</p>';
        }
    } else { ?>
<p id="formreplace">If you are subscribed to <?=$_SERVER['site_name']?> in this <?=area_type() ?>,
<a href="/view/message/<?=$message ?>/reply">log in</a> to post a reply.
<br>Otherwise, if you live in the UK, 
<a href="/subscribe?r=/view/message/<?=$message ?>">sign up</a> in order to
<?=$_SERVER['site_name']?>.
</p>
<?
    }
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
    $r['reason_web'] = _('Before posting to '.$_SERVER['site_name'].', we need to confirm your email address and that you are subscribed to this constituency.');
    $r['reason_email'] = _("You'll then be able to post to the site, as long as you are subscribed to this constituency.");
    $r['reason_email_subject'] = _("Post to $_SERVER[site_name]");
    $P = person_signon($r);

    $r = message_get($q_message);
    $area_id = $r['area_id'];
    if (!person_allowed_to_reply($P->id(), $area_id, $q_message)) {
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

        $posted_by_rep = constituent_is_rep($P->id(), $area_id);
        db_query('insert into comment (id, message, refs, person_id, ipaddr, content, visible, posted_by_rep)
            values (comment_next_id(), ?, ?, ?, ?, ?, ?, ?)', array($q_message, $refs, $P->id(),
            $_SERVER['REMOTE_ADDR'], $q_text, 1, $posted_by_rep));
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
<p><label for="text">Public message:</label><textarea name="text" id="text" rows="10" cols="50"><?=$q_h_text ?></textarea></p>
<p><input<? if ($q_emailreplies) print ' checked'; ?> type="checkbox" id="emailreplies" name="emailreplies" value="1"> <label for="emailreplies" class="inline_label">Email me future comments to this message</label></p>
<input type="submit" name="Preview" value="Preview">
<? if ($counter>0) print '<input type="submit" name="Post" value="Post">'; ?>
</form>
<?
}

function person_allowed_to_reply($person_id, $area_id, $message) {
    $signed_up = db_getOne('SELECT constituent.id FROM constituent,message
                            WHERE person_id = ? AND constituent.area_id = ? AND message.id = ?
                            AND creation_time<=posted',
                            array($person_id, $area_id, $message));
    if ($signed_up) return true;
    return false;
}

?>
