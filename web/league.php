<?
// league.php:
// League table for HearFromYourMP
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: league.php,v 1.11 2005-11-18 15:30:23 ycml Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/fns.php';
require_once '../../phplib/votingarea.php';

$sort = get_http_var('s');
if (!$sort || preg_match('/[^csmelr]/', $sort)) $sort = 's';
$sort_orders = array('l'=>'latest DESC', 'c'=>'constituency', 'm'=>'messages DESC',
                     'r'=>'comments DESC', 's'=>'count DESC', 'e'=>'emails_to_mp DESC');

if (array_key_exists('csv', $_GET)) {
    header('Content-Type: text/csv');
    csv_league_table($sort);
} else {
    page_header();
    league_table($sort);
    page_footer();
}

function csv_league_table($sort) {
    global $sort_orders;
    $q = db_query("SELECT COUNT(id) AS count,constituency,
    EXTRACT(epoch FROM MAX(creation_time)) AS latest,
    (SELECT COUNT(*) FROM message WHERE state = 'approved' and constituency = constituent.constituency) AS messages,
    (SELECT COUNT(*) FROM comment,message WHERE constituency = constituent.constituency AND message.id=comment.message) AS comments,
    (SELECT COUNT(*) FROM mp_threshold_alert WHERE constituency = constituent.constituency) AS emails_to_mp
    FROM constituent WHERE constituency IS NOT NULL GROUP BY constituency ORDER BY " . 
    $sort_orders[$sort] );

    $cache = db_getAll('SELECT id,name FROM constituency_cache');
    foreach ($cache as $r) {
    	$areas_info[$r['id']] = array('name'=>$r['name']);
    }

    $rows = array(); $ids = array();
    while ($r = db_fetch_array($q)) {
        if ($r['constituency'] && va_is_fictional_area($r['constituency']))
            continue;
        $rows[] = $r;
        if ($r['constituency'] && !$areas_info[$r['constituency']])
            $ids[] = $r['constituency'];
    }

    if (count($ids)) {
        $areas_info2 = mapit_get_voting_areas_info($ids);
        $areas_info = array_merge($areas_info, $areas_info2);
    }

    foreach ($rows as $k=>$r) {
        $c_id = $r['constituency'] ? $r['constituency'] : -1;
        $c_name = $areas_info[$c_id]['name'];
        $rows[$k] = "\"$c_name\",$r[count]\n";
    }
    foreach ($rows as $row) {
        print $row;
    }
}

function league_table($sort) {
    global $sort_orders; ?>
<h2>Current Status</h2>
<?

    $consts = db_getOne('SELECT COUNT(DISTINCT(constituency)) FROM constituent');
    $people = db_getOne('SELECT COUNT(DISTINCT(person_id)) FROM constituent');
    $people_lastday = db_getOne('SELECT COUNT(DISTINCT(person_id)) FROM constituent WHERE creation_time > current_timestamp - interval \'1 day\'');
    $left = 647 - $consts; # 646 normal, 1 ZZ99ZZ
    $morethan = db_getAll('SELECT constituency FROM constituent WHERE constituency IS NOT NULL GROUP BY constituency HAVING count(*)>=25');
    $morethan = count($morethan);
    # This way is far too slow:
    # $morethan = db_getOne('SELECT COUNT(DISTINCT(constituency)) FROM constituent WHERE (SELECT COUNT(*) FROM constituent AS c WHERE c.constituency = constituent.constituency) >= 25');
    $morethan_emailed = db_getOne('SELECT COUNT(DISTINCT(constituency)) FROM mp_threshold_alert');
    print "<ul><li>$people people have signed up in $consts constituencies";
    print "<li>$people_lastday people in the last day";
    print "<li>There are $left constituencies with nobody signed up";
    print "<li>$morethan constituencies have 25 or more subscribers, $morethan_emailed have been sent emails";
    print '</ul>';

    $q = db_query("SELECT COUNT(id) AS count,constituency,
    EXTRACT(epoch FROM MAX(creation_time)) AS latest,
    (SELECT COUNT(*) FROM message WHERE state = 'approved' and constituency = constituent.constituency) AS messages,
    (SELECT COUNT(*) FROM comment,message WHERE constituency = constituent.constituency AND message.id=comment.message) AS comments,
    (SELECT COUNT(*) FROM mp_threshold_alert WHERE constituency = constituent.constituency) AS emails_to_mp,
    (SELECT status FROM mp_nothanks WHERE constituency = constituent.constituency) AS nothanks
    FROM constituent WHERE constituency IS NOT NULL GROUP BY constituency ORDER BY " . 
    $sort_orders[$sort] );

    $cache = db_getAll('SELECT id,name FROM constituency_cache');
    foreach ($cache as $r) {
    	$areas_info[$r['id']] = array('name'=>$r['name']);
    }

    $rows = array(); $ids = array();
    while ($r = db_fetch_array($q)) {
        if ($r['constituency'] && va_is_fictional_area($r['constituency']))
            continue;
        $rows[] = array_map('htmlspecialchars', $r);
        if ($r['constituency'] && !$areas_info[$r['constituency']])
            $ids[] = $r['constituency'];
    }

    if (count($ids)) {
        $areas_info2 = mapit_get_voting_areas_info($ids);
        $areas_info = array_merge($areas_info, $areas_info2);
    }

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
        if ($r['nothanks']) {
            $row .= '<td colspan="3" align="center"><a href="' . OPTION_BASE_URL . '/view/' . $c_id . '">This MP has asked not to use this service</a></td>';
        } else {
            $row .= '<td align="center">' . $r['emails_to_mp'] . '</td>';
            $row .= '<td align="center">' . $r['messages'] . '</td>';
            $row .= '<td align="center">' . $r['comments'] . '</td>';
        }
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
        'm'=>'Messages sent by MP',
        'r'=>'Comments left by constituents',
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

