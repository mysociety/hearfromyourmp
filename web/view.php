<?
# view.php:
# The meat of YCML
# 
# Things this script has to do:
# * View messages for a particular constituency
# * View a thread for a particular message
# * Deal with posting a comment
# 
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: matthew@mysociety.org. WWW: http://www.mysociety.org
#
# $Id: view.php,v 1.7 2005-08-19 17:58:01 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/fns.php';
require_once '../../phplib/person.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/mapit.php';

importparams(
    array('constituency', '/^\d+$/', 'Invalid constituency', null),
    array('message', '/^\d+$/', 'Invalid message ID', null),
    array('mode', '/^post$/', 'Invalid mode', null)
);

if ($q_mode == 'post') {
    page_header('Replying to a message');
    post_comment_form();
} elseif ($q_message) {
    # Show thread for particular message.
    page_header('Viewing particular message');
    show_message($q_message);
} elseif ($q_constituency) {
    # Show list of messages for this particular constituency.
    page_header('Viewing constituency page');
    show_messages($q_constituency);
} else {
    # Main page. Show nothing? Or list of constituencies?
    page_header('List of constituencies');
    show_constituencies();
}
page_footer();

# ---

function show_constituencies() {
    $q = db_query('SELECT DISTINCT constituency FROM message');
    $out = array();
    while ($r = db_fetch_array($q)) {
        $out[] = $r['constituency'];
    }
    if (count($out)) {
        $areas_info = mapit_get_voting_areas_info($out);
        $out = array();
        foreach ($areas_info as $c_id => $array) {
            $out[$array['name']] = "<li><a href=\"/view/$c_id\">$array[name]</a></li>\n";
        }
        ksort($out);
        print '<p>The following constituencies have had postings:</p> <ul>';
        foreach ($out as $line) print $line;
        print '</ul>';
    } else {
        print '<p>There are no messages on YCML yet.</p>';
    }
}

function show_messages($c_id) {
    $area_info = ycml_get_area_info($c_id);
    $rep_info = ycml_get_mp_info($c_id);
    $signed_up = db_getOne('SELECT count(*) FROM constituent WHERE constituency = ?', $c_id);
    $q = db_query('SELECT *, extract(epoch from posted) as posted,
                    (select count(*) from comment where comment.message=message.id
                        AND visible<>0) as numposts
                    FROM message
                    WHERE constituency = ? ORDER BY message.posted', $c_id);
?>
<h2><?=$area_info['name'] ?></h2>
<p>The MP for this constituency is <?=$rep_info['name'] ?>, <?=$rep_info['party'] ?>.
So far, <?=$signed_up . ' ' . make_plural($signed_up, 'person has', 'people have') ?> signed up to this YCML.</p>
<?
    $out = '';
    while ($r = db_fetch_array($q)) {
        $out .= '<li>' . prettify($r['posted']) . " : <a href=\"/view/message/$r[id]\">$r[subject]</a>. $r[numposts] " . make_plural($r['numposts'], 'reply' , 'replies') . '</li>';
    }
    if ($out) {
        print "<h3>Messages posted</h3> <ul>$out</ul>";
    } else {
        print "<p><em>This MP has not yet sent any messages through YCML.</em></p>";
    }
}

function show_message($message) {
    $r = get_message($message);
    $c_id = $r['constituency'];
    $rep_info = ycml_get_mp_info($c_id);
    print '<div id="message"><h2>' . $r['subject'] . '</h2> <p>Posted by <strong>' . $rep_info['name']
        . ' at ' . prettify($r['epoch']) . '</strong>:</p> <blockquote>' . $r['content'] . '</blockquote>';
    $next = db_getOne('SELECT id FROM message WHERE constituency = ? AND posted > ?', array($c_id, $r['posted']) );
    $prev = db_getOne('SELECT id FROM message WHERE constituency = ? AND posted < ?', array($c_id, $r['posted']) );
    print '<p align="right">';
    if ($prev) print '<a href="/view/message/' . $prev . '">Previous message</a> | ';
    print '<a href="/view/' . $c_id . '">View all</a>';
    if ($next) print ' | <a href="/view/message/' . $next . '">Next message</a>';
    print '</p>';
    print '</div>';
    $cc = db_getAll('select comment.id, refs, name, email, website, extract(epoch from date) as date, content, posted_by_mp from comment,person where person_id = person.id and message = ? and visible <> 0 order by refs || \',\' || comment.id, date', $message);
    if (count($cc))
        print '<h3>Comments</h3> <ul id="comments">' . do_format_comments($cc, 0, count($cc) - 1) . '</ul>';

    if (get_http_var('showform')) {
        $r = array();
        $r['reason_web'] = _('Before posting to YCML, we need to confirm your email address and that you are subscribed to this constituency.');
        $r['reason_email'] = _("You'll then be able to post to the site, as long as you are subscribed to this constituency.");
        $r['reason_email_subject'] = _("Post to YCML");
        $P = person_signon($r);
    } else {
        $P = person_if_signed_on();
    }
    if (!is_null($P)) {
        if (person_allowed_to_reply($P->id(), $c_id, $message)) {
            comment_form($P);
        } else {
            print '<p id="formreplace">You are not subscribed to this YCML, or subscribed after this message was posted.</p>';
        }
    } else {
        print '<p id="formreplace">If you are subscribed to this YCML, <a href="/view/message/'.$message.'/reply">log in</a> to post a reply. If you are a member of this constituency, <a href="/subscribe?r=/view/message/' . $message . '">sign up</a> in order to post your own comments.</p>';
    }
}

function do_format_comments($cc, $first, $last) {
    global $q_message;
    $html = '';
    for ($i = $first; $i <= $last; ++$i) {
        $r = $cc[$i];
        $r['posted_by_mp'] = ($r['posted_by_mp']=='t') ? true : false;
        $html .= '<li';
        if ($r['posted_by_mp']) $html .= ' class="by_mp"';
        $html .= '>' . format_one_comment($r);
/*      XXX COMMENTED OUT AS NO THREADING TO START
        $html .= '<a href="view?mode=post;article=$q_message;replyid=$id">Reply to this</a>.';
        # Consider whether the following comments are replies to this comment.
        $R = "$refs,$id";
        for ($j = $i + 1; $j <= $last && preg_match("/^$R(,|$)/", $cc[$j][1]); ++$j) {}
        --$j;
        if ($j > $i)
            $html .= "<ul>" . do_format_comments($cc, $i + 1, $j) . "</ul>";
        $i = $j;
*/
        $html .= "</li>";
    }
    return $html;
}

function format_one_comment($r) {
    $ds = prettify($r['date']);
    $comment = '<p><a name="comment' . $r['id'] .'">Posted by</a> ';
    if ($r['posted_by_mp']) $comment .= '<strong>';
    if ($r['website'])
        $comment .= '<a href="' . $r['website'] . '">';
    $comment .= $r['name'];
    if ($r['website'])
        $comment .= '</a>';
    $comment .= ', ';
    if ($r['posted_by_mp']) $comment .= 'MP, ';
    $comment .= $ds;
    if ($r['posted_by_mp']) $comment .= '</strong>';
    $comment .= ":</p>\n<div>" . htmlspecialchars($r['content']) . '</div>';
    return $comment;
}

function get_message($id) {
    $r = db_getRow('SELECT *,extract(epoch from posted) as epoch FROM message WHERE id = ?', $id);
    if (!$r)
        err('Unknown message ID');
    return $r;
}

function post_comment_form() {
    global $q_text, $q_h_text, $q_replyid, $q_counter, $q_message, $q_Post;
    importparams(
        array('text', '//', '', null),
        array('replyid', '/^\d+$/', '', null),
        array('counter', '/^\d+$/', '', null),
        array('Post', '/^Post$/', '', null)
    );

    $r = array();
    $r['reason_web'] = _('Before posting to YCML, we need to confirm your email address and that you are subscribed to this constituency.');
    $r['reason_email'] = _("You'll then be able to post to the site, as long as you are subscribed to this constituency.");
    $r['reason_email_subject'] = _("Post to YCML");
    $P = person_signon($r);

    $r = get_message($q_message);
    $constituency = $r['constituency'];
    if (!person_allowed_to_reply($P->id(), $constituency, $q_message)) {
        print '<div class="error">Sorry, but you are not subscribed to this constituency, or you subscribed after this message was posted.</div>';
        return false;
    }

    if (!is_null($q_replyid)) {
        if (db_getOne('select count(*) from comment where id = ? and visible <> 0', $q_replyid) != 1)
            err("Bad reply ID $replyid");
        print '<p><em>This is the comment to which you are replying:</em></p>'
        . '<blockquote>' . format_one_comment(db_getRow('SELECT id, author, email, link, date, content FROM comment WHERE id = ?', $replyid)) . '</blockquote>';
    }

    $preview = '';
    if (!is_null($q_counter)) {
        $website = $P->website_or_blank();
        $preview = '<h3>Previewing your comment</h3> <p><em>Not yet</em> posted by <strong>';
        if ($website) $preview .= "<a href=\"$website\">";
        $preview .= $P->name();
        if ($website) $preview .= '</a>';
        $preview .= '</strong>:</p> <div>' . $q_h_text . '</div>';
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
        $id = sprintf('%08x', db_getOne('select count(*) from comment'));
        db_query('insert into comment (id, message, refs, person_id, ipaddr, content, visible, posted_by_mp)
            values (?, ?, ?, ?, ?, ?, ?, ?)', array($id, $q_message, $refs, $P->id(),
            $_SERVER['REMOTE_ADDR'], $q_text, 1, $posted_by_mp));
        db_commit();

        print '<p>Thank you for your comment. You can <a href="/view/message/' . $q_message . '#comment' . $id . '">view it here</a>.</p>';
    }
}

function comment_form($P) {
    global $q_message, $q_counter, $q_h_text;
    if (is_null($q_counter))
        $counter = 0;
    else
        $counter = $q_counter + 1;
?>
<form action="/view" method="post">
<input type="hidden" name="mode" value="post">
<input type="hidden" name="counter" value="<?=$counter ?>">
<input type="hidden" name="message" value="<?=$q_message ?>">
<? /* NO THREADING <input type="hidden" name="replyid" value=""> */ ?>
<h2>Post a reply</h2>
<p><label for="text">Message:</label><textarea name="text" id="text" rows="10" cols="50"><?=$q_h_text ?></textarea></p>
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

function constituent_is_mp($person_id, $constituency) {
    return db_getOne('SELECT is_mp FROM constituent
                        WHERE person_id = ? AND constituency = ?', array($person_id, $constituency) );
}
?>
