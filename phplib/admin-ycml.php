<?php
/*
 * admin-ycml.php:
 * YCML admin pages.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: admin-ycml.php,v 1.5 2005-08-26 15:35:32 matthew Exp $
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

class ADMIN_PAGE_YCML_MAIN {
    function ADMIN_PAGE_YCML_MAIN () {
        $this->id = "ycml";
        $this->navname = _("YCML Summary");
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
            $ids[] = $r['constituency'];
        }

        $areas_info = mapit_get_voting_areas_info($ids);

        foreach ($rows as $k=>$r) {
            $c_id = $r['constituency'];
            $c_name = $areas_info[$c_id]['name'];
            $row = "";
            $row .= '<td><a href="' . OPTION_BASE_URL . '/view/'.$c_id.'">' . $c_name . '</a><br><a href="'.$this->self_link.'&amp;constituency='.$c_id.'">admin</a> |
                <a href="?page=ycmllatest&amp;constituency='.$c_id.'">timeline</a>';
            $row .= '</td>';
            $row .= '<td align="center">' . $r['count'] . '</td>';
            $row .= '<td>' . prettify($r['latest']) . '</td>';
            $rows[$k] = $row;
        }
        if (count($rows)) {
            print '<p>Here\'s the current state of YCML:</p>';
            $this->table_header($sort);
            $a = 0;
            foreach ($rows as $row) {
                print '<tr'.($a++%2==0?' class="v"':'').'>';
                print $row;
                print '</tr>'."\n";
            }
            print '</table>';
        } else {
            print '<p>No-one has signed up to YCML at all, anywhere, ever.</p>';
        }
    }

    function show_constituency($id) {
        print '<p><a href="'.$this->self_link.'">' . _('Main page') . '</a></p>';

        $sort = get_http_var('s');
        if (!$sort || preg_match('/[^etn]/', $sort)) $sort = 'e';

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
        $subscribers = db_num_rows($q);

        $out = array();
        print "<h2>The constituency of $area_info[name]</h2>";
        print "<p>The MP for this constituency is <strong>$rep_info[name]</strong> ($rep_info[party]). Subscribed so far: <strong>$subscribers</strong>.";

        $all = db_getAll('SELECT constituent.id,name,email   FROM constituent,person
                          WHERE constituent.person_id=person.id AND constituency=?
                          ORDER BY name', array($id));
        $choices = '';
        foreach ($all as $r) {
            $choices .= '<option value="' . $r['id'] . '">' . $r['name'] . ' &lt;' . $r['email'] . '&gt;</option>';
        }
?>
<h3>Create or set login for this MP</h3>
<form method="post">
<em>Either:</em> email:<input type="text" name="MPemail" value="" size="30"> <input type="submit" name="createMP" value="Create a brand spanking new account for this MP">
</form>
<form method="post">
<em>Or:</em> pick an existing account subscribed to this constituency:
<?      if ($choices) {
            print '<select name="selectMP">' . $choices . '</select> <input';
        } else {
            print '<select name="selectMP" disabled><option>No matches</select> <input disabled';
        }
?>
 type="submit" value="Use this account">
</form>
<h3>Post a message as this MP</h3>
<?      if (db_getOne('SELECT is_mp FROM constituent WHERE constituency=? AND is_mp', $id)) { ?>
<form method="post">
<table cellpadding="3" cellspacing="0" border="0">
<tr><th><label for="subject">Subject:</label></th>
<td><input type="text" id="subject" name="subject" value="" size="40"></td>
</tr>
<tr valign="top"><th><label for="message">Message:</label></th>
<td><textarea id="message" name="message" rows="10" cols="70"></textarea></td>
</tr></table>
<input type="submit" value="Post">
</form>
<?      } else { ?>
<p>You cannot post a message until this constituency has an MP account. Please create or choose one.</p>
<?      } ?>
<h3>Subscribers</h3>
<?      while ($r = db_fetch_array($q)) {
            $is_mp = ($r['is_mp'] == 't') ? true : false;
            $r = array_map('htmlspecialchars', $r);
            $e = array();
            if ($r['name']) array_push($e, $r['name']);
            if ($r['email']) array_push($e, $r['email']);
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
        print '<h3>Messages</h3>';
        $q = db_query('select * from message 
                where constituency = ? order by posted', $id);

        $n = 0;
        while ($r = db_fetch_array($q)) {
            if ($n++)
                print '<hr>';
            print "<b>$r[subject]</b><br>$r[content]";
        }
        if ($n == 0) {
            print "No messages yet.";
        }
    }

    function post_message($constituency, $subject, $message) {
        db_query('INSERT INTO message (constituency, subject, content)
                    VALUES (?, ?, ?)',
                    array($constituency, $subject, $message));
        db_commit();
        print '<p><em>Message posted!</em></p>';
    }

    function display($self_link) {
        $constituency = get_http_var('constituency');

        // Perform actions
        $subject = get_http_var('subject');
        $message = get_http_var('message');
        if ($subject && $message) {
            $this->post_message($constituency, $subject, $message);
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

class ADMIN_PAGE_PB_ABUSEREPORTS {
    function ADMIN_PAGE_PB_ABUSEREPORTS() {
        $this->id = 'pbabusereports';
        $this->navname = _('Abuse reports');
    }

    function display($self_link) {
        db_connect();

        if (array_key_exists('prev_url', $_POST)) {
            $do_discard = false;
            if (get_http_var('discardReports'))
                $do_discard = true;
            foreach ($_POST as $k => $v) {
                if ($do_discard && preg_match('/^ar_([1-9]\d*)$/', $k, $a))
                    db_query('delete from abusereport where id = ?', $a[1]);
                // Don't think delete pledge is safe as a button here
                # if (preg_match('/^delete_(comment|pledge|signer)_([1-9]\d*)$/', $k, $a)) {
                if (preg_match('/^delete_(comment)_([1-9]\d*)$/', $k, $a)) {
                    if ($a[1] == 'comment') {
                        pledge_delete_comment($a[2]);
                    } else if ($a[1] == 'pledge') {
                        // pledge_delete_pledge($a[2]);
                    } else {
                        // pledge_delete_signer($a[2]);
                    }
                    print "<em>Deleted "
                            . htmlspecialchars($a[1])
                            . " #" . htmlspecialchars($a[2]) . "</em><br>";
                }
            }

            db_commit();

        }

        $this->showlist($self_link);
    }

    function showlist($self_link) {
        global $q_what;
        importparams(
                array('what',       '/^(comment|pledge|signer)$/',      '',     'comment')
            );

        print "<p><strong>See reports on:</strong> ";

        $ww = array('comment', 'signer', 'pledge');
        $i = 0;
        foreach ($ww as $w) {
            if ($w != $q_what)
                print "<a href=\"$self_link&amp;what=$w\">";
            print "${w}s ("
                    . db_getOne('select count(id) from abusereport where what = ?', $w)
                    . ")";
            if ($w != $q_what)
                print "</a>";
            if ($i < sizeof($ww) - 1)
                print " | ";
            ++$i;
        }

        $this->do_one_list($self_link, $q_what);
    }

    function do_one_list($self_link, $what) {

        $old_id = null;
        $q = db_query('select id, what_id, reason, ipaddr, extract(epoch from whenreported) as epoch from abusereport where what = ? order by what_id, whenreported desc', $what);

        if (db_num_rows($q) > 0) {

            print '<form name="discardreportsform" method="POST" action="'.$this->self_link.'"><input type="hidden" name="prev_url" value="'
                        . htmlspecialchars($self_link) . '">';
            print '
    <p><input type="submit" name="discardReports" value="Discard selected abuse reports"></p>
    <table class="abusereporttable">
    ';
            while (list($id, $what_id, $reason, $ipaddr, $t) = db_fetch_row($q)) {
                if ($what_id !== $old_id) {
                
                    /* XXX should group by pledge and then by signer/comment, but
                     * can't be arsed... */
                    print '<tr style="background-color: #eee;"><td colspan="4">';

                    if ($what == 'pledge')
                        $pledge_id = $what_id;
                    elseif ($what == 'signer')
                        $pledge_id = db_getRow('select pledge_id from signers where id = ?', $what_id);
                    elseif ($what == 'comment')
                        $pledge_id = db_getOne('select pledge_id from comment where id = ?', $what_id);
                    
                    $pledge = db_getRow('
                                    select *,
                                        extract(epoch from creationtime) as createdepoch,
                                        extract(epoch from date) as deadlineepoch
                                    from pledges
                                    where id = ?', $pledge_id);
                        
                    /* Info on the pledge. Print for all categories. */
                    print '<table>';
                    print '<tr><td><b>Pledge:</b> ';
                    $pledge_obj = new Pledge($pledge);
                    print $pledge_obj->h_sentence(array());
                    print ' <a href="'.$pledge_obj->url_main().'">'.$pledge_obj->ref().'</a> ';
                    print '<a href="?page=pb&amp;pledge='.$pledge_obj->ref().'">(admin)</a> ';
                            
                    /* Print signer/comment details under pledge. */
                    if ($what == 'signer') {
                        $signer = db_getRow('
                                        select signers.*, person.email,
                                            extract(epoch from signtime) as epoch
                                        from signers
                                        left join person on signers.person_id = person.id
                                        where signers.id = ?', $what_id);

                        print '</td></tr>';
                        print '<tr class="break"><td><b>Signer:</b> '
                                . (is_null($signer['name'])
                                        ? "<em>not known</em>"
                                        : htmlspecialchars($signer['name']))
                                . ' ';

                        if (!is_null($signer['email']))
                            print '<a href="mailto:'
                                    . htmlspecialchars($signer['email'])
                                    . '">'
                                    . htmlspecialchars($signer['email'])
                                    . '</a> ';

                        if (!is_null($signer['mobile']))
                            print htmlspecialchars($signer['mobile']);

                        print '<b>Signed at:</b> ' . date('Y-m-d H:m', $signer['epoch']);
                    } elseif ($what == 'comment') {
                        $comment = db_getRow('
                                        select id,
                                            extract(epoch from whenposted)
                                                as whenposted,
                                            text, name, website
                                        from comment
                                        where id = ?', $what_id);

                        print '</td></tr>';
                        print '<tr class="break">';
                        print '<td><b>Comment:</b> ';
                        comments_show_one($comment, true);
                    }

                    if ($what == "comment") {
                        print " <input type=\"submit\" name=\"delete_${what}_${what_id}\" value=\"Delete this $what\">";
                    }
                    print '</td></tr>';
                    print '</table>';
                    $old_id = $what_id;
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
            print '<p><input type="submit" name="discardReports" value="' . _('Discard selected abuse reports') . '"></form>';
        } else {
            print '<p>No abuse reports of this type.</p>';
        }
    }
}

?>
