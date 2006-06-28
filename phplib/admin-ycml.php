<?php
/*
 * admin-ycml.php:
 * HearFromYourMP admin pages.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: admin-ycml.php,v 1.27 2006-06-28 15:42:49 matthew Exp $
 * 
 */

require_once "ycml.php";
require_once "fns.php";
require_once "../../phplib/db.php";
require_once "../../phplib/mapit.php";
require_once "../../phplib/dadem.php";
require_once '../../phplib/votingarea.php';
require_once "../../phplib/utility.php";
require_once "../../phplib/importparams.php";
require_once "../../phplib/person.php";

class ADMIN_PAGE_YCML_SUMMARY {
    function ADMIN_PAGE_YCML_SUMMARY() {
        $this->id = 'summary';
    }
    function display() {
        $signups = db_getOne('SELECT COUNT(*) FROM constituent');
        $consts = db_getOne('SELECT COUNT(DISTINCT(constituency)) FROM constituent') - 1;
    	$consts_posted = db_getOne("select count(distinct constituency) from message where state='approved'");
        $people1 = db_getOne('SELECT COUNT(*) FROM person');
        $people2 = db_getOne('SELECT COUNT(DISTINCT(person_id)) FROM constituent');
        $messages_approved = db_getOne('SELECT COUNT(*) FROM message WHERE state=\'approved\'');
        $messages_ready = db_getOne('SELECT COUNT(*) FROM message WHERE state=\'ready\'');
        $messages_notresponded = db_getAll("SELECT * FROM message WHERE state='ready' AND posted < now()-interval '1 day'");
        $notresponded_details = '';
        if (count($messages_notresponded)) {
            $notresponded_details = ':<ul>';
            foreach ($messages_notresponded as $row) {
                $area_info = ycml_get_area_info($row['constituency']);
                $rep_info = ycml_get_mp_info($row['constituency']);
                $notresponded_details .= "<li>$rep_info[name], $area_info[name], $row[posted], subject '$row[subject]'";
            }
            $notresponded_details .= '</ul>';
        }
        $messages_notresponded = count($messages_notresponded);
        $messages_new = db_getOne('SELECT COUNT(*) FROM message WHERE state=\'new\'');
        $alerts = db_getOne('SELECT COUNT(*) FROM alert');
        $comments = db_getOne('SELECT COUNT(*) FROM comment');

        print "$signups constituency signups from $people2 people
            (though $people1 person entries) to $consts constituencies<br>
            $consts_posted constituencies have had $messages_approved message".
            ($messages_approved!=1?'s':'').", and there have been 
            $comments comments<br>
            $messages_new message" . ($messages_new!=1?'s are':' is') .
            " awaiting mailing out to MPs for confirmation,
            $messages_ready message" . ($messages_ready!=1?'s are':' is') .
            " waiting for approval by MPs, $messages_notresponded of those " .
            ($messages_notresponded!=1?'were':'was') . " sent more than a day ago$notresponded_details
            <br>$alerts alerts";
    }
}

class ADMIN_PAGE_YCML_MAIN {
    function ADMIN_PAGE_YCML_MAIN () {
        $this->id = "ycml";
        $this->navname = _("HearFromYourMP Summary");
    }

    function table_header($sort) {
        print '<table border="1" cellpadding="5" cellspacing="0"><tr>';
        $cols = array(
            'c'=>'Constituency', 
            'r'=>'Representative',
            's'=>'Signups',
            'l'=>'Latest signup'
        );
        foreach ($cols as $s => $col) {
            print '<th>';
            if ($sort != $s) print '<a href="'.$this->self_link.'&amp;s='.$s.'">';
            print $col;
            if ($sort != $s) print '</a>';
            print '</th>';
        }
        print '</tr>';
        print "\n";
    }

    function list_all() {
        global $open;
        $sort = get_http_var('s');
        if (!$sort || preg_match('/[^csl]/', $sort)) $sort = 's';
        $order = '';
        if ($sort=='l') $order = 'latest DESC';
        elseif ($sort=='c') $order = 'constituency';
        elseif ($sort=='s') $order = 'count DESC';

        $q = db_query('SELECT COUNT(id) AS count,constituency,EXTRACT(epoch FROM MAX(creation_time)) AS latest FROM constituent GROUP BY constituency' . 
            ($order ? ' ORDER BY ' . $order : '') );
        list($areas_info, $rows) = ycml_get_all_areas_info($q, false);
        $reps_info = ycml_get_all_reps_info(array_keys($areas_info));

        foreach ($rows as $k=>$r) {
            $c_id = $r['constituency'] ? $r['constituency'] : -1;
            $c_name = array_key_exists($c_id, $areas_info) ? $areas_info[$c_id]['name'] : '&lt;Unknown / bad postcode&gt;';
            $r_name = array_key_exists($c_id, $reps_info) ? $reps_info[$c_id]['name'] : 'Unknown';
            $row = "";
            $row .= '<td>';
            if ($c_id != -1) $row .= '<a href="' . OPTION_BASE_URL . '/view/'.$c_id.'">';
            $row .= $c_name;
            if ($c_id != -1) $row .= '</a>';
            $row .= '<br><a href="'.$this->self_link.'&amp;constituency='.$c_id.'">admin</a> |
                <a href="?page=ycmllatest&amp;constituency='.$c_id.'">timeline</a>';
            $row .= '</td>';
            $row .= "<td>$r_name</td>";
            $row .= '<td align="center">' . $r['count'] . '</td>';
            $row .= '<td>' . prettify($r['latest']) . '</td>';
            $rows[$k] = $row;
        }
        if (count($rows)) {
            print '<p>Here\'s the current state of HearFromYourMP:</p>';
            $this->table_header($sort);
            $a = 0;
            foreach ($rows as $row) {
                print '<tr'.($a++%2==0?' class="v"':'').'>';
                print $row;
                print '</tr>'."\n";
            }
            print '</table>';
        } else {
            print '<p>No-one has signed up to HearFromYourMP at all, anywhere, ever.</p>';
        }
    }

    function show_constituency($id) {
        print '<p><a href="'.$this->self_link.'">' . _('Main page') . '</a></p>';

        $sort = get_http_var('s');
        if (!$sort || preg_match('/[^etn]/', $sort)) $sort = 'e';

        if ($id > 0) {
            $area_info = mapit_get_voting_area_info($id);
            $reps = dadem_get_representatives($id);
            $rep_info = dadem_get_representative_info($reps[0]);
            $query = 'SELECT constituent.*, person.*, extract(epoch from creation_time) as creation_time
                       FROM constituent
                       LEFT JOIN person ON person.id = constituent.person_id
                       WHERE constituency=?';
            if ($sort=='t') $query .= ' ORDER BY constituent.creation_time DESC';
            elseif ($sort=='n') $query .= ' ORDER BY showname DESC';
            $q = db_query($query, $id);
        } else {
            $area_info['name'] = 'Unknown / bad postcode';
            $query = 'SELECT *, extract(epoch from creation_time) as creation_time
                       FROM constituent
                       WHERE constituency IS NULL';
            if ($sort=='t') $query .= ' ORDER BY constituent.creation_time DESC';
            elseif ($sort=='n') $query .= ' ORDER BY showname DESC';
            $q = db_query($query);
        }
        $subscribers = db_num_rows($q);

        $out = array();
        print "<div style='float:left; width:47%;'><h2 style='margin:0'>$area_info[name]";
        if ($id>0)
            print ", $rep_info[name] ($rep_info[party]),<br>$subscribers subscribed";
        print '</h2>';

        if ($id>0) {
            $is_mp = db_getOne('SELECT is_mp FROM constituent WHERE constituency=? AND is_mp', $id);
            $no_thanks = db_getOne('SELECT status FROM mp_nothanks WHERE constituency=?', $id)=='t' ? true : false;
            $all = db_getAll('SELECT constituent.id,is_mp,person.name,person.email FROM constituent,person
                              WHERE constituent.person_id=person.id AND constituency=?
                              ORDER BY person.name', array($id));
            $choices = '';
            foreach ($all as $r) {
                $choices .= '<option';
                if ($r['is_mp']=='t') $choices .= ' selected';
                $choices .= ' value="' . $r['id'] . '">' . $r['name'] . ' &lt;' . $r['email'] . '&gt;</option>';
            }
            $confirmation_email = db_getOne('select confirmation_email from constituency where id = ?', $id);
            if (is_null($confirmation_email))
                $confirmation_email = '';
            $sent_messages = db_getOne("select count(*) from message where constituency=? and state='approved'", $id);
?>
<form method="post">
<p>Alert status: <strong>
<?          if ($no_thanks) { ?>
<input type="hidden" name="change_alert_state" value="interested">
Not interested - <input type="submit" value="Change">
<?          } else {
                if ($sent_messages) {
                    print 'Previous user';
                } else {
                    print 'New user';
                } ?>
<input type="hidden" name="change_alert_state" value="not_interested">
 - <input type="submit" value="Not interested">
 <input type="checkbox" name="female" value="1"> Female?
<?          }
?></strong></p></form>
<h3>Post a message as this MP</h3>
<?          if ($confirmation_email && !$no_thanks) { ?>
<form method="post" accept-charset="UTF-8">
<table cellpadding="3" cellspacing="0" border="0">
<tr><th><label for="subject">Subject:</label></th>
<td><input type="text" id="subject" name="subject" value="" size="40"></td>
</tr>
<tr valign="top"><th><label for="message">Message:</label></th>
<td><textarea id="message" name="message" rows="10" cols="58"></textarea></td>
</tr></table>
<input type="submit" value="Post">
</form>
<?          } elseif (!$confirmation_email) { ?>
<p>You cannot post a message until a confirmation email address is set for
this constituency.</p>
<?          } elseif ($no_thanks) { ?>
<p>This MP has asked not to use our service!</p>
<?          }
?>
<h3>Create or set login for this MP</h3>
<form method="post">
<em>Either:</em> email:<input type="text" name="MPemail" value="" size="30"> <input type="submit" name="createMP" value="Create a brand spanking new account for this MP">
</form>
<form method="post">
<em>Or:</em> pick an existing account subscribed to this constituency:
<?          if ($choices) {
                print '<select name="selectMP"><option value="">None selected</option>' . $choices . '</select> <input';
            } else {
                print '<select name="selectMP" disabled><option>No matches</select> <input disabled';
            }
?>
 type="submit" value="Use this account">
</form>
<h3>Set confirmation email address for this MP</h3>
<p>This is the email address to which a confirmation request will be sent for
each message posted. This is independent of the address for the MP's own login,
if any. This must be set before messages can be posted:</p>
<?
            ?>
<form method="post">
<input type="hidden" name="constituency_id" value="<?=htmlspecialchars($id)?>">
<input type="text" name="confirmation_email" value="<?=htmlspecialchars($confirmation_email)?>" size="30">
<input type="submit" name="setConfirmationEmail" value="Set confirmation email address">
</form>
<?    
        }

?>
</div>
<div style="float:left; width:47%">
<?      if ($id>0) {
            $query = db_getAll('select * from mp_threshold_alert where constituency=?', $id);
            if (count($query)) {
                print '<h3>Alerts sent</h3> <table><tr><th>Subscribers</th><th>When</th></tr>';
                foreach ($query as $r) {
                    print "<tr><td>$r[num_subscribers]</td><td>$r[whensent]</td></tr>";
                }
                print '</table>';
            }
            $query = db_getAll('select id,state,subject from message 
                    where constituency = ? order by posted', $id);
            if (count($query)) {
                print '<h3>Messages</h3> <table cellpadding="3" cellspacing="0" border="0"><tr><th>State</th><th>Subject</th><th></th></tr>';
                foreach ($query as $r) {
                    print "<tr><td>$r[state]</td><td>";
                    if ($r['state']=='approved') print '<a href="http://www.hearfromyourmp.com/view/message/'.$r['id'].'">';
                    print $r['subject'];
                    if ($r['state']=='approved') print '</a>';
                    print '</td><td><form method="post"><input type="hidden" name="resend_confirmation" value="'.$r['id'].'"><input type="submit" value="Resend confirmation email"';
                    if ($r['state'] != 'ready') print ' disabled';
                    print '></form></tr>';
                }
                print '</table>';
            }
        }
?>
<h3 style="clear:both">Subscribers</h3>
<?      while ($r = db_fetch_array($q)) {
            $is_mp = ($r['is_mp'] == 't') ? true : false;
            $r = array_map('htmlspecialchars', $r);
            $e = array();
            if ($r['name']) array_push($e, $r['name']);
            if ($r['email']) array_push($e, $r['email']);
            if ($r['postcode']) array_push($e, $r['postcode']);
            $e = join("<br>", $e);
            if ($is_mp) $e = "<strong>$e</strong>";
            $out[$e] = '<td>'.$e.'</td>';
            $out[$e] .= '<td>'.prettify($r['creation_time']).'</td>';

#            $out[$e] .= '<td><form name="shownameform" method="post" action="'.$this->self_link.'"><input type="hidden" name="showname_signer_id" value="' . $r['signid'] . '">';
#            $out[$e] .= '<select name="showname">';
#            $out[$e] .=  '<option value="1"' . ($r['showname'] == 't'?' selected':'') . '>Yes</option>';
#            $out[$e] .=  '<option value="0"' . ($r['showname'] == 'f'?' selected':'') . '>No</option>';
#            $out[$e] .=  '</select>';
#            $out[$e] .= '<input type="submit" name="showname_signer" value="update">';
#            $out[$e] .= '</form></td>';

#            $out[$e] .= '<td>';
#            $out[$e] .= '<form name="removesignerform" method="post" action="'.$this->self_link.'"><input type="hidden" name="remove_signer_id" value="' . $r['signid'] . '"><input type="submit" name="remove_signer" value="Remove signer permanently"></form>';
#            $out[$e] .= '</td>';
        }
        if ($sort == 'e') {
            function sort_by_domain($a, $b) {
                $aa = stristr($a, '@');
                $bb = stristr($b, '@');
                if ($aa==$bb) return 0;
                return ($aa>$bb) ? 1 : -1;
            }
            uksort($out, 'sort_by_domain');
        }
        if (count($out)) {
            print '<table border="1" cellpadding="3" cellspacing="0"><tr>';
            $cols = array('e'=>'Signer', 't'=>'Time');
            foreach ($cols as $s => $col) {
                print '<th>';
                if ($sort != $s) print '<a href="'.$this->self_link.'&amp;constituency='.$id.'&amp;s='.$s.'">';
                print $col;
                if ($sort != $s) print '</a>';
                print '</th>';
            }
            print '</tr>';
            $a = 0;
            foreach ($out as $row) {
                print '<tr'.($a++%2==0?' class="v"':'').'>';
                print $row;
                print '</tr>';
            }
            print '</table>';
        } else {
            print '<p>Nobody has signed up to this constituency.</p>';
        }
        print '</div>';
    }

    function post_message($constituency, $subject, $message) {

        if (get_http_var('confirm')) {
            $message = str_replace("\r", '', $message);
            db_query("INSERT INTO message (constituency, subject, content, state)
                        VALUES (?, ?, ?, 'new')",
                        array($constituency, $subject, $message));
            db_commit();
            print '<p><em>Message posted!</em></p>';
            return 1;
        } else {
            /* Show preview of message. */
            print '<h2>Preview of message</h2>';
            print '<p><strong>Subject:</strong> ' . htmlspecialchars($subject) . '</p>';
            print '<h3>Web page</h3>';
            $content = comment_prettify($message);
            $content = preg_replace('#((<p>\*.*?</p>\n)+)#e', "'<ul>'.str_replace('<p>*', '<li>', '$1') . \"</ul>\n\"", $content);
            print '<blockquote><p>' . $content . '</p></blockquote>';
            print '<h3>Email</h3>';
            $preview = preg_replace('#\r#', '', htmlspecialchars($message));
	    print '<pre>';
            $paras = preg_split('/\n{2,}/', $preview);
            foreach ($paras as $para) {
                $para = "     $para";
                print wordwrap($para, 64, "\n     ");
                print "\n\n";
            }
	    print '</pre>';
            print '<form method="POST" accept-charset="UTF-8"><input type="hidden" name="subject" value="' . htmlspecialchars($subject) . '"><input type="hidden" name="message" value="' . htmlspecialchars($message) . '"><input type="submit" name="confirm" value="Confirm message"></form>';
            return 0;
        }
    }

    function display($self_link) {
        $constituency = get_http_var('constituency');

        // Perform actions
        $subject = get_http_var('subject');
        $message = get_http_var('message');
        if ($subject && $message) {
            if (!$this->post_message($constituency, $subject, $message))
                return;
        } elseif (get_http_var('createMP')) {
            $reps = dadem_get_representatives($constituency);
            $rep_info = dadem_get_representative_info($reps[0]);
            $email = get_http_var('MPemail');
            $P = person_get_or_create($email, $rep_info['name']);
            db_query('UPDATE constituent SET is_mp = false WHERE constituency = ?', $constituency);
            db_query("INSERT INTO constituent (person_id, constituency, postcode,
                    creation_ipaddr, is_mp) VALUES (?, ?, ?, ?, true)",
                    array($P->id(), $constituency, '', $_SERVER['REMOTE_ADDR']));
            db_commit();
            print '<p><em>MP created</em></p>';
        } elseif (get_http_var('setConfirmationEmail')) {
            $id = get_http_var('constituency_id');
            $email = get_http_var('confirmation_email');
            if (!is_null($id) && !is_null($email)) {
                if (validate_email($email)) {
                    $q = db_query('update constituency set confirmation_email = ? where id = ?', array($email, $id));
                    if (0 == db_affected_rows())
                        db_query('insert into constituency (id, confirmation_email) values (?, ?)', array($id, $email));
                    db_commit();
                    print '<p><em>Confirmation email address set to '
                            . htmlspecialchars($email)
                            . '</em></p>';
                } else {
                    print '<p><em>"'
                            . htmlspecialchars($email)
                            . '" is not a valid email address</em></p>';
                }
            }
        } elseif (get_http_var('selectMP')) {
            $c_id = get_http_var('selectMP');
            if (ctype_digit($c_id)) {
                db_query('UPDATE constituent SET is_mp = false WHERE constituency = ?', $constituency);
                db_query('UPDATE constituent SET is_mp = true WHERE id = ?', $c_id);
                db_commit();
                print '<p><em>MP selected</em></p>';
            }
        } elseif ($change_state = get_http_var('change_alert_state')) {
            if ($change_state == 'interested') {
                db_query('UPDATE mp_nothanks SET status=? WHERE constituency = ?', array(false, $constituency));
                print '<p><em>Removed from the not interested list</p>';
            } else {
                if (db_getOne('SELECT status FROM mp_nothanks WHERE constituency = ?', $constituency)=='f') {
                    db_query('UPDATE mp_nothanks SET status=? WHERE constituency=?', array(true, $constituency));
                } else {
                    $gender = get_http_var('female') ? 'f' : 'm';
                    db_query('INSERT INTO mp_nothanks (constituency, status, website, gender) VALUES (?,?,?,?)', array($constituency, true, null, $gender));
                }
                print '<p><em>Added to the not interested list</em></p>';
            }
            db_commit();
        } elseif ($m_id = get_http_var('resend_confirmation')) {
            db_query("UPDATE message SET state='new' WHERE id=?", $m_id);
	    db_commit();
            print '<p><em>Message set for reconfirmation</em></p>';
        }

        // Display page
        if ($constituency) {
            $this->show_constituency($constituency);
        } else {
            $this->list_all();
        }
    }
}

class ADMIN_PAGE_YCML_LATEST {
    function ADMIN_PAGE_YCML_LATEST() {
        $this->id = 'ycmllatest';
        $this->navname = 'Timeline';

        if (get_http_var('linelimit')) {
            $this->linelimit = get_http_var('linelimit');
        } else {
            $this->linelimit = 250;
        }

        $this->constituency = null;
        if ($constituency = get_http_var('constituency')) {
            $this->constituency = $constituency;
        }
    }

    function show_latest_changes() {
        $q = db_query('SELECT *,extract(epoch from posted) as epoch FROM message
                     ORDER BY posted DESC');
        $this->area_info = array();
        $time = array();
        while ($r = db_fetch_array($q)) {
            $c_id = $r['constituency'];
            if (!array_key_exists($c_id, $this->area_info)) {
                $this->area_info[$c_id] = mapit_get_voting_area_info($c_id);
            }
            if (!$this->constituency || $this->constituency==$c_id) {
                $time[$r['epoch']][] = $r;
            }
        }
        $q = db_query('SELECT comment.*,constituency,subject,name,email,extract(epoch from date) as epoch
                        FROM comment, message, person
                        WHERE comment.message = message.id AND person_id = person.id
                        ORDER BY date DESC');
        while ($r = db_fetch_array($q)) {
            if (!$this->constituency || $this->constituency==$r['constituency']) {
                $time[$r['epoch']][] = $r;
            }
        }
        if (!$this->constituency || $this->constituency>0) {
            $q = db_query('SELECT *,extract(epoch from creation_time) as epoch FROM constituent,person
                            WHERE person_id = person.id
                            ORDER BY creation_time DESC');
            while ($r = db_fetch_array($q)) {
                $c_id = $r['constituency'];
                if (!array_key_exists($c_id, $this->area_info)) {
                    $this->area_info[$c_id] = mapit_get_voting_area_info($c_id);
                }
                if (!$this->constituency || $this->constituency==$c_id) {
                    $time[$r['epoch']][] = $r;
                }
            }
        } elseif ($this->constituency<0) {
            $this->area_info[-1]['name'] = 'Unknown / bad postcode';
            $q = db_query('SELECT *,extract(epoch from creation_time) as epoch FROM constituent
                            WHERE constituency IS NULL
                            ORDER BY creation_time DESC');
            while ($r = db_fetch_array($q)) {
                $time[$r['epoch']][] = $r;
            }
        }
        krsort($time);

        print '<a href="'.$this->self_link.'">Full log</a>';
        if ($this->constituency) {
            print ' | <em>Viewing constituency "'.$this->area_info[$this->constituency]['name'].'"</em> (<a href="?page=ycml&amp;constituency='.$this->constituency.'">admin</a>)';
        }
        $date = ''; 
        $linecount = 0;
        print "<div class=\"timeline\">";
        foreach ($time as $epoch => $datas) {
            $linecount++;
            if ($linecount > $this->linelimit) {
                print '<dt><br><a href="'.$this->self_link.
                        '&linelimit='.htmlspecialchars($this->linelimit + 250).'">Expand timeline...</a></dt>';
                break;
            }
            $curdate = date('l, jS F Y', $epoch);
            if ($date != $curdate) {
                if ($date <> "")
                    print '</dl>';
                print '<h2>'. $curdate . '</h2> <dl>';
                $date = $curdate;
            }
            print '<dt><b>' . date('H:i:s', $epoch) . ':</b></dt> <dd>';
            foreach ($datas as $data) {
            if (array_key_exists('posted', $data)) {
                if (!$this->constituency) print $this->area_info[$data['constituency']]['name'] . ' ';
                print "MP posted message <a href=\"" . OPTION_BASE_URL . "/view/message/$data[id]\">$data[subject]</a> : $data[content]";
            } elseif (array_key_exists('date', $data)) {
                print "$data[name] &lt;$data[email]&gt; commented on <a href=\"" . OPTION_BASE_URL . "/view/message/$data[message]\">$data[subject]</a> saying '";
                print htmlspecialchars($data['content']) . "'";
            } elseif (array_key_exists('creation_time', $data)) {
                print "$data[name] &lt;$data[email]&gt; (postcode $data[postcode]) signed up";
                if (!$this->constituency) print " to " . $this->area_info[$data['constituency']]['name'];
            } else {
                print_r($data);
            }
            print '<br>';
            }
            print "</dd>\n";
        }
        print '</dl>';
        print "</div>";
    }

    function display($self_link) {
        $this->show_latest_changes();
    }
}

class ADMIN_PAGE_YCML_ABUSEREPORTS {
    function ADMIN_PAGE_YCML_ABUSEREPORTS() {
        $this->id = 'ycmlabusereports';
        $this->navname = _('Abuse reports');
    }

    function display($self_link) {
        db_connect();
        $do_discard = false;
        if (get_http_var('discardReports'))
            $do_discard = true;
        foreach ($_POST as $k => $v) {
            if ($do_discard && preg_match('/^ar_([1-9]\d*)$/', $k, $a))
                db_query('delete from abusereport where id = ?', $a[1]);
            if (preg_match('/^delete_comment_([0-9a-f]{8})$/', $k, $a)) {
                db_query('select delete_comment(?)', $a[1]);
                print "<em>Deleted comment"
                        . " #" . htmlspecialchars($a[1]) . "</em><br>";
            }
        }
        db_commit();
        $this->showlist($self_link);
    }

    function showlist($self_link) {
        $old_id = null;
        $q = db_query('select id, comment_id, reason, ipaddr, extract(epoch from whenreported) as epoch from abusereport order by whenreported desc');

        if (db_num_rows($q) > 0) {

            print '<form name="discardreportsform" method="POST" action="'.$this->self_link.'">';
            print '
    <p><input type="submit" name="discardReports" value="Discard selected abuse reports"></p>
    <table class="abusereporttable">
    ';
            while (list($id, $comment_id, $reason, $ipaddr, $t) = db_fetch_row($q)) {
                if ($comment_id !== $old_id) {
                
                    print '<tr style="background-color: #eee;"><td colspan="4">';
                    print '<table>';
                    $comment = db_getRow('
                                        select comment.id, extract(epoch from date) as date,
                                            content, posted_by_mp, name, email, website
                                        from comment, person
                                        where comment.person_id = person.id
                                            and comment.id = ?', $comment_id);

                    print '<tr class="break">';
                    print '<td>' . comment_show_one($comment, true);
                    print " <input type=\"submit\" name=\"delete_comment_${comment_id}\" value=\"Delete this comment\">";
                    print '</td></tr>';
                    print '</table>';
                    $old_id = $comment_id;
                }

                print '<tr><td>'
                        . '<input type="checkbox" name="ar_' . $id . '" value="1">'
                        . '</td><td><b>Abuse report:</b> '
                        . date('Y-m-d H:i', $t)
                        . ' from '
                        . $ipaddr
                        . '</td><td><b>Reason: </b>'
                        . htmlspecialchars($reason)
                        . '</td></tr>';
            }

            print '</table>';
            print '<p><input type="submit" name="discardReports" value="Discard selected abuse reports"></form>';
        } else {
            print '<p>No abuse reports.</p>';
        }
    }
}

class ADMIN_PAGE_YCML_SUBSCRIBERS {
    function ADMIN_PAGE_YCML_SUBSCRIBERS() {
        $this->id = 'subscribers';
        $this->navname = 'HearFromYourMP Subscribers';
    }
    function display() {
        $search = get_http_var('search');
        $h_search = htmlspecialchars($search);
?>
<form method="post">
Search for a subscriber: <input type="text" name="search" value="<?=$h_search ?>">
<input type="submit" value="Go">
</form>
<?
        if ($search) {
            $q = db_getAll("select constituent.postcode, extract(epoch from constituent.creation_time) as creation_time,
                                constituent.creation_ipaddr, constituent.constituency, person.name, person.email
                            from constituent, person
                            where constituent.person_id = person.id and
                            (person.name ilike '%' || ? || '%' or
                             person.email ilike '%' || ? || '%')
                             order by email", array($search, $search));
?>
<h2>Results for search</h2>
<table cellpadding="3" cellspacing="0" border="0">
<tr><th>Name</th><th>Email</th><th>Postcode</th><th>Constituency</th><th>Signup</th><th>IP address</th></tr>
<?
            $constituencies = array();
            foreach ($q as $r)
                $constituencies[] = $r['constituency'];
            $areas_info = mapit_get_voting_areas_info($constituencies);
            
            $c = 0;
            foreach ($q as $r) {
                $creation_time = prettify($r['creation_time']);
                $constituency = $areas_info[$r['constituency']]['name'];
                print '<tr';
                if ($c=1-$c) print ' class="v"';
                print "><td>$r[name]</td><td>$r[email]</td><td>$r[postcode]</td><td>$constituency</td><td>$creation_time</td><td>$r[creation_ipaddr]</td><tr>\n";
            }
            print '</table>';
        }
    }
}

class ADMIN_PAGE_YCML_MULTIPLE {
    function ADMIN_PAGE_YCML_MULTIPLE() {
        $this->id = 'multiple';
        $this->navname = 'HearFromYourMP Multiple Signups';
    }
    function display() {
        $q = db_getAll('select count(c.id) as count, person_id, p.name, p.email
                        from constituent as c,person as p
                        where person_id=p.id
                        group by person_id, p.name, p.email
                        having count(c.id)>1
                        order by count(c.id) desc');
        print '<table><tr><th>Count</th><th>Name</th><th>Email</th></tr>';
        foreach ($q as $r) {
            print "<tr><td>$r[count]</td><td>$r[name]</td><td>$r[email]</td></tr>\n";
        }
        print '</table>';
    }
}

?>
