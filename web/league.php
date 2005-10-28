<?
// league.php:
// League table for HearFromYourMP
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: league.php,v 1.3 2005-10-28 10:42:30 ycml Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/fns.php';
if (array_key_exists('csv', $_GET)) {
    header('Content-Type: text/csv');
    csv_league_table();
} else {
    page_header();
    league_table();
    page_footer();
}

function csv_league_table() {
    $sort = get_http_var('s');
    if (!$sort || preg_match('/[^csmlr]/', $sort)) $sort = 's';
    $order = '';
    if ($sort=='l') $order = 'latest DESC';
    elseif ($sort=='c') $order = 'constituency';
    elseif ($sort=='m') $order = 'messages DESC';
    elseif ($sort=='r') $order = 'comments DESC';
    elseif ($sort=='s') $order = 'count DESC';

    $q = db_query('SELECT COUNT(id) AS count,constituency,
    EXTRACT(epoch FROM MAX(creation_time)) AS latest,
    (SELECT COUNT(*) FROM message WHERE constituency = constituent.constituency) AS messages,
    (SELECT COUNT(*) FROM comment,message WHERE constituency = constituent.constituency AND message.id=comment.message) AS comments,
    (SELECT COUNT(*) FROM mp_threshold_alert WHERE constituency = constituent.constituency) AS emails_to_mp
    FROM constituent WHERE constituency IS NOT NULL GROUP BY constituency' . 
    ($order ? ' ORDER BY ' . $order : '') );
    $rows = array();
    while ($r = db_fetch_array($q)) {
        $rows[] = $r;
        if ($r['constituency'])
            $ids[] = $r['constituency'];
    }

    $areas_info = mapit_get_voting_areas_info($ids);

    foreach ($rows as $k=>$r) {
        $c_id = $r['constituency'] ? $r['constituency'] : -1;
        $c_name = $areas_info[$c_id]['name'];
        $rows[$k] = "\"$c_name\",$r[count]\n";
    }
    foreach ($rows as $row) {
        print $row;
    }
}

function league_table() { ?>
<h2>Current Status</h2>
<p><strong>Site in testing &mdash; most MPs, even those with more than 25 people signed up, have not yet been emailed requests to post</strong></p>
<?

    $consts = db_getOne('SELECT COUNT(DISTINCT(constituency)) FROM constituent');
    $people = db_getOne('SELECT COUNT(DISTINCT(person_id)) FROM constituent');
    $people_lastday = db_getOne('SELECT COUNT(DISTINCT(person_id)) FROM constituent WHERE creation_time > current_timestamp - interval \'1 day\'');
    $left = 646 - $consts;
    print "<ul><li>$people people have signed up in $consts constituencies";
    print "<li>$people_lastday people in the last day";
    print "<li>There are $left constituencies with nobody signed up";
    print '</ul>';

    $sort = get_http_var('s');
    if (!$sort || preg_match('/[^csmlr]/', $sort)) $sort = 's';
    $order = '';
    if ($sort=='l') $order = 'latest DESC';
    elseif ($sort=='c') $order = 'constituency';
    elseif ($sort=='m') $order = 'messages DESC';
    elseif ($sort=='r') $order = 'comments DESC';
    elseif ($sort=='s') $order = 'count DESC';

    $q = db_query('SELECT COUNT(id) AS count,constituency,
    EXTRACT(epoch FROM MAX(creation_time)) AS latest,
    (SELECT COUNT(*) FROM message WHERE constituency = constituent.constituency) AS messages,
    (SELECT COUNT(*) FROM comment,message WHERE constituency = constituent.constituency AND message.id=comment.message) AS comments,
    (SELECT COUNT(*) FROM mp_threshold_alert WHERE constituency = constituent.constituency) AS emails_to_mp
    FROM constituent WHERE constituency IS NOT NULL GROUP BY constituency' . 
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
        $c_name = $areas_info[$c_id]['name'];
        $row = "";
        $row .= '<td>';
        if ($c_id != -1) $row .= '<a href="' . OPTION_BASE_URL . '/view/'.$c_id.'">';
        $row .= $c_name;
        if ($c_id != -1) $row .= '</a>';
        $row .= '</td>';
        $row .= '<td align="center">' . $r['count'] . '</td>';
	$row .= '<td align="center">' . $r['emails_to_mp'] . '</td>';
        $row .= '<td align="center">' . $r['messages'] . '</td>';
        $row .= '<td align="center">' . $r['comments'] . '</td>';
        $row .= '<td>' . prettify($r['latest']) . '</td>';
        $rows[$k] = $row;
    }
    if (count($rows)) {
        table_header($sort);
        $a = 0;
        foreach ($rows as $row) {
            print '<tr'.($a++%2==0?' class="alt"':'').'>';
            print $row;
            print '</tr>'."\n";
        }
        print '</table>';
    } else {
        print '<p>No-one has signed up to HearFromYourMP at all, anywhere, ever.</p>';
    }
}

function table_header($sort) {
    print '<table border="0" cellpadding="4" cellspacing="0"><tr>';
    $cols = array(
        'c'=>'Constituency', 
        's'=>'Signups',
	'e'=>'Emails sent asking MP to post',
        'm'=>'Messages sent',
        'r'=>'Comments',
        'l'=>'Latest signup'
    );
    foreach ($cols as $s => $col) {
        print '<th>';
        if ($sort != $s) print '<a href="/league?s='.$s.'">';
        print $col;
        if ($sort != $s) print '</a>';
        print '</th>';
    }
    print '</tr>';
    print "\n";
}

