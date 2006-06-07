<?
// league.php:
// League table for HearFromYourMP
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: league.php,v 1.17 2006-06-07 15:59:14 chris Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/fns.php';

$sort = get_http_var('s');
if (!$sort || preg_match('/[^csmelrp]/', $sort)) $sort = 's';
$sort_orders = array('l'=>'latest DESC', 'c'=>'constituency', 'm'=>'messages DESC',
                     'r'=>'comments DESC', 's'=>'count DESC', 'e'=>'emails_to_mp DESC',
                     'p'=>'constituency');

if (array_key_exists('csv', $_GET)) {
    header('Content-Type: text/csv');
    csv_league_table($sort);
} else {
    header('Cache-Control: max-age=60');
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

    list($areas_info, $rows) = ycml_get_all_areas_info($q);

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
    global $reps_info, $sort_orders; ?>
<h2>Current Status</h2>
<?

    # -1 to account for test constituency
    $consts = db_getOne('SELECT COUNT(DISTINCT(constituency)) FROM constituent') - 1;
    $people = db_getOne('SELECT COUNT(DISTINCT(person_id)) FROM constituent');
    $people_lastday = db_getOne('SELECT COUNT(DISTINCT(person_id)) FROM constituent WHERE creation_time > current_timestamp - interval \'1 day\'');
    $left = 646 - $consts;
    $morethan = db_getAll('SELECT constituency FROM constituent WHERE constituency IS NOT NULL GROUP BY constituency HAVING count(*)>=25');
    $morethan = count($morethan);
    # This way is far too slow:
    # $morethan = db_getOne('SELECT COUNT(DISTINCT(constituency)) FROM constituent WHERE (SELECT COUNT(*) FROM constituent AS c WHERE c.constituency = constituent.constituency) >= 25');
    $morethan_emailed = db_getOne('SELECT COUNT(DISTINCT(constituency)) FROM mp_threshold_alert');
    print "<ul><li>$people people have signed up in ";
    if ($consts==646) print 'all ';
    print "$consts constituencies";
    print "<li>$people_lastday people in the last day";
    if ($consts<645)
        print "<li>There are $left constituencies with nobody signed up";
    elseif ($consts==645)
        print "<li>There is $left constituency with nobody signed up";
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

    list($areas_info, $rows) = ycml_get_all_areas_info($q);
    $reps_info = ycml_get_all_reps_info(array_keys($areas_info));

    if ($sort=='p') {
        function by_mp($a, $b) {
            global $reps_info;
            $a_id = $a['constituency'] ? $a['constituency'] : -1;
            $b_id = $b['constituency'] ? $b['constituency'] : -1;
            $a_name = $reps_info[$a_id]['name'];
            $b_name = $reps_info[$b_id]['name'];
            return strcmp($a_name, $b_name);
        }
        usort($rows, 'by_mp');
    }
    foreach ($rows as $k=>$r) {
        $c_id = $r['constituency'] ? $r['constituency'] : -1;
        $c_name = $areas_info[$c_id]['name'];
        $r_name = $reps_info[$c_id]['name'];
        $r_id = $reps_info[$c_id]['id'];
        if (OPTION_YCML_STAGING) {
            $r_name = spoonerise($r_name);
        }
        $row = "";
        $row .= '<td>';
#        if (file_exists('/data/vhost/www.hearfromyourmp.com/docs/mpphotos/'.$r_id.'.jpg'))
#	    $row .= '<img src="/mpphotos/'.$r_id.'.jpg">';
#        else
#	    $row .= '&nbsp;';
#	$row .= '</td> <td>';
        if ($c_id != -1) $row .= '<a href="' . OPTION_BASE_URL . '/view/'.$c_id.'">';
        $row .= $c_name;
        if ($c_id != -1) $row .= '</a>';
        $row .= '</td>';
        $row .= "<td>$r_name</td>";
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
        'p'=>'MP',
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

