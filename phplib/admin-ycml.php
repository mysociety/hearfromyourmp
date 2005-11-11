<?php
/*
 * admin-ycml.php:
 * HearFromYourMP admin pages.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: admin-ycml.php,v 1.15 2005-11-11 13:07:53 sandpit Exp $
 * 
 */

require_once "ycml.php";
require_once "fns.php";
require_once "../../phplib/db.php";
require_once "../../phplib/mapit.php";
require_once "../../phplib/dadem.php";
require_once "../../phplib/utility.php";
require_once "../../phplib/importparams.php";
require_once "../../phplib/person.php";

class ADMIN_PAGE_YCML_LEFT {
    function ADMIN_PAGE_YCML_LEFT() {
        $this->navname = 'Constituencies with no signups';
        $this->id = 'ycmlleft';
    }
    function display() {
        $areas = mapit_get_areas_by_type('WMC');
        $consts = db_getAll('SELECT DISTINCT(constituency) FROM constituent');
        foreach ($consts as $const) {
            $c_id = $const['constituency'];
            $used[$c_id] = true;
        }
        foreach ($areas as $area_id) {
            if (!array_key_exists($area_id, $used)) $empty[] = $area_id;
        }
        $areas_info = mapit_get_voting_areas_info($empty);
        print '<ol>';
        foreach ($areas_info as $area) {
            $out[] = $area['name'];
        }
        sort($out);
        print '<li>' . join('<li>', $out);
        print '</ol>';
    }
}

class ADMIN_PAGE_YCML_SUMMARY {
    function ADMIN_PAGE_YCML_SUMMARY() {
        $this->id = 'summary';
    }
    function display() {
        $signups = db_getOne('SELECT COUNT(*) FROM constituent');
        $consts = db_getOne('SELECT COUNT(DISTINCT(constituency)) FROM constituent');
        $mps = db_getOne('SELECT COUNT(*) FROM constituent WHERE is_mp');
        $people1 = db_getOne('SELECT COUNT(*) FROM person');
        $people2 = db_getOne('SELECT COUNT(DISTINCT(person_id)) FROM constituent');
        $messages = db_getOne('SELECT COUNT(*) FROM message');
        $alerts = db_getOne('SELECT COUNT(*) FROM alert');
        $comments = db_getOne('SELECT COUNT(*) FROM comment');
        print "$signups constituency signups from $people1/$people2 people to $consts constituencies<br>$mps MPs have sent $messages message".($messages!=1?'s':'').", and there have been $comments comments<br>$alerts alerts";
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
        $rows = array();
        while ($r = db_fetch_array($q)) {
            $rows[] = array_map('htmlspecialchars', $r);
            if ($r['constituency'])
                $ids[] = $r['constituency'];
        }

        $areas_info = mapit_get_voting_areas_info($ids);

        foreach ($rows as $k=>$r) {
            $c_id = $r['constituency'] ? $r['constituency'] : -1;
            $c_name = array_key_exists($c_id, $areas_info) ? $areas_info[$c_id]['name'] : '&lt;Unknown / bad postcode&gt;';
            $row = "";
            $row .= '<td>';
            if ($c_id != -1) $row .= '<a href="' . OPTION_BASE_URL . '/view/'.$c_id.'">';
            $row .= $c_name;
            if ($c_id != -1) $row .= '</a>';
            $row .= '<br><a href="'.$this->self_link.'&amp;constituency='.$c_id.'">admin</a> |
                <a href="?page=ycmllatest&amp;constituency='.$c_id.'">timeline</a>';
            $row .= '</td>';
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
        print "<h2>The constituency of $area_info[name]</h2>";
        if ($id>0)
            print "<p>The MP for this constituency is <strong>$rep_info[name]</strong> ($rep_info[party]). Subscribed so far: <strong>$subscribers</strong>.";

        if ($id>0) {
            $is_mp = db_getOne('SELECT is_mp FROM constituent WHERE constituency=? AND is_mp', $id);
            $all = db_getAll('SELECT constituent.id,is_mp,person.name,person.email FROM constituent,person
                              WHERE constituent.person_id=person.id AND constituency=?
                              ORDER BY person.name', array($id));
            $choices = '';
            foreach ($all as $r) {
                $choices .= '<option';
                if ($r['is_mp']=='t') $choices .= ' selected';
                $choices .= ' value="' . $r['id'] . '">' . $r['name'] . ' &lt;' . $r['email'] . '&gt;</option>';
            }
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
            $confirmation_email = db_getOne('select confirmation_email from constituency where id = ?', $id);
            if (is_null($confirmation_email))
                $confirmation_email = '';
            ?>
<form method="post">
<input type="hidden" name="constituency_id" value="<?=htmlspecialchars($id)?>">
<input type="text" name="confirmation_email" value="<?=htmlspecialchars($confirmation_email)?>" size="30">
<input type="submit" name="setConfirmationEmail" value="Set confirmation email address">
</form>
            
<h3>Post a message as this MP</h3>
<?          if ($confirmation_email) { ?>
<form method="post" accept-charset="UTF-8">
<table cellpadding="3" cellspacing="0" border="0">
<tr><th><label for="subject">Subject:</label></th>
<td><input type="text" id="subject" name="subject" value="" size="40"></td>
</tr>
<tr valign="top"><th><label for="message">Message:</label></th>
<td><textarea id="message" name="message" rows="10" cols="70"></textarea></td>
</tr></table>
<input type="submit" value="Post">
</form>
<?          } else { ?>
<p>You cannot post a message until a confirmation email address is set for
this constituency.</p>
<?          }
        }

?>
<h3>Subscribers</h3>
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
            print '<p>Nobody has signed up to this pledge.</p>';
        }
        print '<p>';
        
        // Messages
        if ($id>0) {
            print '<h3>Messages</h3>';
            $q = db_query('select * from message 
                    where constituency = ? order by posted', $id);

            $n = 0;
            while ($r = db_fetch_array($q)) {
                if ($n++)
                    print '<hr>';
                print '<b>' . htmlspecialchars($r['subject']) . '</b><br>' . htmlspecialchars($r['content']);
            }
            if ($n == 0) {
                print "No messages yet.";
            }
        }
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
            print '<div><p>'
                . str_replace('@', '&#64;', make_clickable(preg_replace('#\n{2,}#', "</p>\n<p>", preg_replace('#\r#', '', htmlspecialchars($message)))))
                . '</p></div>';
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

?>
