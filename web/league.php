<?
// league.php:
// League table for HearFromYourMP
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: league.php,v 1.30 2007-11-01 15:26:03 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/reps.php';

$sort = get_http_var('s');
if (!$sort || preg_match('/[^csmelrp]/', $sort)) $sort = 's';
$sort_orders = array('l'=>'latest DESC', 'c'=>'area_id', 'm'=>'messages DESC',
                     'r'=>'comments DESC', 's'=>'count DESC', 'e'=>'emails_to_mp DESC',
                     'p'=>'area_id');

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
    $q = db_query("SELECT COUNT(id) AS count, area_id,
    EXTRACT(epoch FROM MAX(creation_time)) AS latest,
    (SELECT COUNT(*) FROM message WHERE state = 'approved' and area_id = constituent.area_id) AS messages,
    (SELECT COUNT(*) FROM comment,message WHERE area_id = constituent.area_id AND message.id=comment.message AND visible > 0) AS comments,
    (SELECT COUNT(*) FROM rep_threshold_alert,rep_cache WHERE rep_threshold_alert.area_id = constituent.area_id
        AND rep_threshold_alert.area_id=rep_cache.area_id AND extract(epoch from whensent)>created) AS emails_to_mp
    FROM constituent WHERE area_id IS NOT NULL GROUP BY area_id ORDER BY " . 
    $sort_orders[$sort] );

    list($areas_info, $rows) = ycml_get_all_areas_info($q);

    foreach ($rows as $k=>$r) {
        $c_id = $r['area_id'] ? $r['area_id'] : -1;
        $c_name = $areas_info[$c_id]['name'];
        $rows[$k] = "\"$c_name\",$r[count]\n";
    }
    foreach ($rows as $row) {
        print $row;
    }
}

function league_table($sort) {
    # Gah, so that they're available in the sort routines...
    global $reps_info, $areas_info, $sort_orders;

    $q = db_query("SELECT COUNT(id) AS count,area_id,
    EXTRACT(epoch FROM MAX(creation_time)) AS latest,
    (SELECT COUNT(*) FROM message WHERE state = 'approved' and area_id = constituent.area_id) AS messages,
    (SELECT COUNT(*) FROM comment,message WHERE area_id = constituent.area_id AND message.id=comment.message AND visible > 0) AS comments,
    (SELECT COUNT(*) FROM rep_threshold_alert,rep_cache WHERE rep_threshold_alert.area_id = constituent.area_id
        AND rep_threshold_alert.area_id=rep_cache.area_id AND extract(epoch from whensent)>created) AS emails_to_mp,
    (SELECT status FROM rep_nothanks WHERE area_id = constituent.area_id) AS nothanks
    FROM constituent WHERE area_id IS NOT NULL GROUP BY area_id ORDER BY " . 
    $sort_orders[$sort] );

    list($areas_info, $rows) = ycml_get_all_areas_info($q);
    $reps_info = ycml_get_all_reps_info(array_keys($areas_info));

    $consts = db_getOne('SELECT COUNT(DISTINCT(area_id)) FROM constituent');
    $people = db_getOne('SELECT COUNT(DISTINCT(person_id)) FROM constituent');
    $people_lastday = db_getOne('SELECT COUNT(DISTINCT(person_id)) FROM constituent WHERE creation_time > current_timestamp - interval \'1 day\'');
    $morethan = db_getAll('SELECT area_id FROM constituent WHERE area_id IS NOT NULL GROUP BY area_id HAVING count(*)>=' . OPTION_THRESHOLD_STEP);
    $morethan = count($morethan);
    # This way is far too slow:
    # $morethan = db_getOne('SELECT COUNT(DISTINCT(constituency)) FROM constituent WHERE (SELECT COUNT(*) FROM constituent AS c WHERE c.constituency = constituent.constituency) >= 25');
    $morethan_emailed = db_getOne('SELECT COUNT(DISTINCT(area_id)) FROM rep_threshold_alert');
    $mp_written_messages = db_getOne("SELECT COUNT(*) FROM message WHERE state = 'approved'");
    $comments = db_getOne("SELECT COUNT(*) FROM comment WHERE visible > 0");

    if ($sort=='p') {
        function by_mp($a, $b) {
            global $reps_info;
            $a_id = $a['area_id'] ? $a['area_id'] : -1;
            $b_id = $b['area_id'] ? $b['area_id'] : -1;
            $a_name = $reps_info[$a_id]['names'][0];
            $b_name = $reps_info[$b_id]['names'][0];
            return strcmp($a_name, $b_name);
        }
        usort($rows, 'by_mp');
    } elseif ($sort=='c') {
        function by_area($a, $b) {
            global $areas_info;
            $a_id = $a['area_id'] ? $a['area_id'] : -1;
            $b_id = $b['area_id'] ? $b['area_id'] : -1;
            $a_name = $areas_info[$a_id]['name'];
            $b_name = $areas_info[$b_id]['name'];
            return strcmp($a_name, $b_name);
        }
        usort($rows, 'by_area');
    }

    echo '<h2>Current Status</h2>';
    echo "<ul><li>$people ", make_plural($people, 'person has', 'people have'), " signed up in ";
    if (OPTION_AREA_TYPE == 'WMC' && $consts==646) echo 'all ';
    echo "$consts ", area_type('plural', $consts);
    echo "<li>$people_lastday ", make_plural($people_lastday, 'person', 'people'), ' in the last day';
    if (OPTION_AREA_TYPE == 'WMC') {
        $left = 646 - $consts;
        if ($consts<645)
            print "<li>There are $left constituencies with nobody signed up";
        elseif ($consts==645)
            print "<li>There is 1 constituency with nobody signed up";
    }
    print "<li>$morethan constituencies have " . OPTION_THRESHOLD_STEP . " or more subscribers, $morethan_emailed have been sent emails";
    print "<li>$mp_written_messages messages sent by " . rep_type('plural') . ", $comments comments made by constituents";
    print '</ul>';
    print '<p><strong>Click on the headings (e.g. ' . area_type() . ') to sort the table by different columns.</strong></p>';

    foreach ($rows as $k=>$r) {
        $c_id = $r['area_id'] ? $r['area_id'] : -1;
        $c_name = $areas_info[$c_id]['name'];
        $r_name = join(', ', $reps_info[$c_id]['names']);
        #$r_id = $reps_info[$c_id]['ids'];
        if (OPTION_YCML_STAGING) {
            $r_name = spoonerise($r_name);
        }
        $row = "";
        $row .= '<td>';
#        if (file_exists('/data/vhost/www.hearfromyourmp.com/docs/mpphotos/'.$r_id.'.jpg'))
#            $row .= '<img src="/mpphotos/'.$r_id.'.jpg">';
#        else
#            $row .= '&nbsp;';
#        $row .= '</td> <td>';
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
    $rep_type = rep_type();

    print '<table border="0" cellpadding="4" cellspacing="0"><tr>';
    $cols = array(
        'c' => ucwords(area_type()),
        'p' => ucwords($rep_type),
        's'=>'Signups',
        'e' => "Emails sent asking $rep_type to post",
        'm' => "Messages sent by $rep_type",
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

