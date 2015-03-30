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

require_once '../phplib/ycml.php';
require_once '../phplib/alert.php';
require_once '../phplib/constituent.php';
require_once '../phplib/reps.php';
require_once '../phplib/comment.php';
require_once '../commonlib/phplib/person.php';
require_once '../commonlib/phplib/utility.php';
require_once '../commonlib/phplib/importparams.php';
require_once '../commonlib/phplib/mapit.php';

importparams(
    array('area_id', '/^\d+$/', 'Invalid area ID', null),
    array('message', '/^\d+$/', 'Invalid message ID', null),
    array('mode', '/^post$/', 'Invalid mode', null)
);

if ($q_message) {
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
    $q = db_query("SELECT DISTINCT area_id FROM message where state in ('approved','closed')");
    $out = array();
    while ($r = db_fetch_array($q)) {
        $out[] = $r['area_id'];
    }
    if (count($out)) {
        $areas_info = mapit_call('areas', $out);
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
    $signed_up = db_getOne("SELECT count(*) FROM constituent WHERE is_rep='f' and area_id = ?", $area_id);
    $nothanks = db_getRow('SELECT status,website,gender FROM rep_nothanks WHERE area_id = ?', $area_id);
    $num_comments = db_getOne('SELECT COUNT(*) FROM comment,message
        WHERE visible<>0 AND comment.message = message.id AND message.area_id = ?
            AND extract(epoch from message.posted) > ?', $area_id, $max_created);
    $emails_sent_to_rep = db_getOne('SELECT COUNT(*) FROM rep_threshold_alert
        WHERE area_id = ? and extract(epoch from whensent) > ?', $area_id, $max_created);
    $next_threshold = db_getOne('SELECT rep_threshold(?, +1, '.OPTION_THRESHOLD_STEP.');', $signed_up);
    $latest_message = db_getOne("SELECT EXTRACT(epoch FROM MAX(posted)) FROM message
        WHERE state in ('approved','closed') AND area_id = ?", $area_id);
    
    $messages = '';
    $num_messages = 0;
    $q = db_query("SELECT *, extract(epoch from posted) as posted,
                    (select count(*) from comment where comment.message=message.id
                        AND visible<>0) as numposts
                    FROM message
                    WHERE state in ('approved','closed') and area_id = ?
                    ORDER BY message.posted DESC", $area_id);
    while ($r = db_fetch_array($q)) {
        $messages .= '<li>' . prettify($r['posted']) . " : <a href=\"/view/message/$r[id]\">$r[subject]</a>";
        if (count($reps_info)>1) {
            $rep_name = $r['rep_name'];
            if (!$rep_name && isset($reps_info[$r['rep_id']]))
                $rep_name = $reps_info[$r['rep_id']]['name'];
            if ($rep_name)
                $messages .= ', by ' . $rep_name;
        }
        $messages .= ". $r[numposts] " . make_plural($r['numposts'], 'reply' , 'replies') . '</li>';
        if ($r['posted'] > $max_created)
            $num_messages++;
    }

    $title = $area_info['name'];
    if (count($reps_info)==1 && isset($reps_info_arr[0]['name']))
        $title = $reps_info_arr[0]['name'] . ', ' . $title;
    page_header($title);

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

    if (count($reps_info)) {
        $reps = (count($reps) > 1 ? join(', ', array_slice($reps, 0, count($reps)-1)) . ' and ' : '') . $reps[count($reps)-1];
        echo '<p>The ', make_plural(count($reps_info), rep_type('single'), rep_type('plural')),
            ' for this ', area_type(), ' ' , make_plural(count($reps_info), 'is', 'are'),
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
            isset($reps_info_arr[0]['name']) ? 'gets' : 'got',
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
    $this_or_these = count($reps_info) > 1 ? 'these ' . rep_type('plural') : 'this ' . rep_type('single');
    $this_or_these_possessive = count($reps_info)>1 ? $this_or_these . '&rsquo;' : $this_or_these . '&rsquo;s';
    if ($num_messages==0) {
        echo '<li>';
        if (!count($reps_info)) {
            echo 'We sent ';
        } else {
            echo 'We have sent ';
        }
        echo $this_or_these, ' ', $emails_sent_to_rep,
            ' ', make_plural($emails_sent_to_rep, 'message');
        if (count($reps_info)) {
            echo ' so far';
        }
        echo ' in this Parliament, asking them to send an email to their constituents.';
        if (count($reps_info)) {
            echo ' We will automatically email them ', $emails_sent_to_rep>0 ? 'again ' : '',
            ' when the list in this ' . area_type() . ' reaches ', $next_threshold, '.';
        }
    } else { ?>
    <li>We sent <?=$this_or_these ?> <?=$emails_sent_to_rep ?> <?=make_plural($emails_sent_to_rep, 'message') ?> in this Parliament, asking them to send an email to their constituents.
    <li><?=ucfirst($this_or_these) ?> <?=(count($reps_info)>1 ? 'have' : 'has') ?> sent <?=$num_messages ?> <?=make_plural($num_messages, 'message') ?> through
        <?=$_SERVER['site_name']?><?=$num_messages>1?', most recently':'' ?> at <?=prettify($latest_message) ?>.
    <li>Constituents have left <?=$num_comments==0?'no':"a total of $num_comments" ?> comment<?=$num_comments!=1?'s':'' ?>
        on <?=$this_or_these_possessive ?> <?=make_plural($num_messages, 'message') ?>.
<?  } ?>
</ul>

<?
    if ($messages) {
        print "<h3>Messages posted in this constituency</h3> <ul>$messages</ul>";
    } else { ?>
<p><em><?=ucfirst($this_or_these) ?> <?=(count($reps_info)>1 ? 'have': 'has') ?> not yet sent any messages through <?=$_SERVER['site_name']?> this Parliament.</em></p>
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
    $rep_name = $r['rep_name'];
    if (!$rep_name) {
        $rep_info = ycml_get_rep_info($rep_id);
        if (!dadem_get_error($rep_info))
            $rep_name = $rep_info['name'];
    }
    $area_info = ycml_get_area_info($area_id);
    page_header($r['subject'] . ' - ' . $rep_name . ', ' . $area_info['name']);
    $next = db_getOne("SELECT id FROM message
        WHERE state in ('approved','closed') and area_id = ? AND posted > ?
        ORDER BY posted LIMIT 1",
        array($area_id, $r['posted']) );
    $prev = db_getOne("SELECT id FROM message
        WHERE state in ('approved','closed') and area_id = ? AND posted < ?
        ORDER BY posted DESC LIMIT 1",
        array($area_id, $r['posted']) );
    print '<div id="dispmessage">';
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
    echo $rep_name;
    if (OPTION_AREA_TYPE == 'WMC') echo '</a>';
    echo '</strong>, ', rep_type('single'), ' for <strong>' . $area_info['name'] . '</strong>, at <strong>' . prettify($r['epoch']) . '</strong>:</p> <blockquote><p>' . $content . '</p></blockquote>';
    print '</div>';

    $cc = db_getAll('select comment.id, refs, name, email, website, extract(epoch from date) as date, content, posted_by_rep, visible from comment,person where person_id = person.id and message = ? order by refs || \',\' || comment.id, date', $message);
    if ($cc && count($cc))
        print '<h3>Comments</h3> <ul id="comments">' . comment_show($cc, 0, count($cc) - 1) . '</ul>';

}

function message_get($id) {
    $r = db_getRow("SELECT *,extract(epoch from posted) as epoch FROM message WHERE state in ('approved','closed') and id = ?", $id);
    if (!$r)
        err('Unknown message ID', E_USER_NOTICE);
    return $r;
}

