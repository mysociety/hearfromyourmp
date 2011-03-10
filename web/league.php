<?
// league.php:
// League table for HearFromYourMP
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: league.php,v 1.41 2009-06-29 16:03:10 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/reps.php';

$sort = get_http_var('s');
if (!$sort || preg_match('/[^csmelrp]/', $sort)) $sort = 's';
$sort_orders = array('l'=>'latest DESC', 'c'=>'area_id', 'm'=>'messages DESC',
                     'r'=>'comments DESC', 's'=>'count DESC', 'e'=>'emails_to_rep DESC',
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
    list($areas_info, $rows) = league_fetch_data($sort);
    foreach ($rows as $k=>$r) {
        $c_id = $r['area_id'] ? $r['area_id'] : -1;
        $c_name = $areas_info[$c_id]['name'];
        $rows[$k] = "\"$c_name\",$r[count]\n";
    }
    foreach ($rows as $row) {
        print $row;
    }
}

function league_fetch_data($sort) {
    global $sort_orders;
    $q = db_query("SELECT COUNT(id) AS count,area_id,
    EXTRACT(epoch FROM MAX(creation_time)) AS latest,
    (SELECT COUNT(*) FROM message WHERE state in ('approved','closed') and area_id = constituent.area_id) AS messages,
    (SELECT COUNT(*) FROM comment,message WHERE area_id = constituent.area_id AND message.id=comment.message AND visible > 0) AS comments,
    (SELECT COUNT(*) FROM rep_threshold_alert WHERE rep_threshold_alert.area_id = constituent.area_id
        AND extract(epoch from whensent) > (select max(created) from rep_cache where constituent.area_id=rep_cache.area_id)) AS emails_to_rep,
    (SELECT status FROM rep_nothanks WHERE area_id = constituent.area_id) AS nothanks
    FROM constituent WHERE area_id IS NOT NULL AND is_rep='f' GROUP BY area_id ORDER BY " . 
    $sort_orders[$sort] );
    return ycml_get_all_areas_info($q);
}

function league_table($sort) {
    # Gah, so that they're available in the sort routines...
    global $reps_info, $areas_info;

    $area_ids = array();
    $q = db_query('select distinct(area_id) from constituent where area_id is not null');
    while ($r = db_fetch_row($q)) {
        $area_ids[] = $r[0];
    }
    if (OPTION_POSTING_DISABLED) {
        $reps_info = array();
    } else {
        $reps_info = ycml_get_all_reps_info($area_ids);
    }
    list($areas_info, $rows) = league_fetch_data($sort);

    $consts = db_getOne("SELECT COUNT(DISTINCT(area_id)) FROM constituent WHERE is_rep='f'");
    $people = db_getOne("SELECT COUNT(DISTINCT(person_id)) FROM constituent WHERE is_rep='f'");
    $people_lastday = db_getOne("SELECT COUNT(DISTINCT(person_id)) FROM constituent WHERE creation_time > current_timestamp - interval '1 day' AND is_rep='f'");
    $morethan = db_getAll("SELECT area_id FROM constituent WHERE area_id IS NOT NULL AND is_rep='f' GROUP BY area_id HAVING count(*)>=" . OPTION_THRESHOLD_STEP);
    $morethan = count($morethan);
    # This way is far too slow:
    # $morethan = db_getOne('SELECT COUNT(DISTINCT(constituency)) FROM constituent WHERE (SELECT COUNT(*) FROM constituent AS c WHERE c.constituency = constituent.constituency) >= 25');
    $morethan_emailed = db_getOne('SELECT COUNT(DISTINCT(area_id)) FROM rep_threshold_alert');
    $mp_written_messages = db_getOne("SELECT COUNT(*) FROM message WHERE state in ('approved','closed')");
    $comments = db_getOne("SELECT COUNT(*) FROM comment WHERE visible > 0");

    if ($sort=='p') {
        function by_mp($a, $b) {
            global $reps_info;
            $a_id = $a['area_id'] ? $a['area_id'] : -1;
            $b_id = $b['area_id'] ? $b['area_id'] : -1;
            $a_name = isset($reps_info[$a_id]['names']) ? $reps_info[$a_id]['names'][0] : '-';
            $b_name = isset($reps_info[$b_id]['names']) ? $reps_info[$b_id]['names'][0] : '-';
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
    echo '<ul><li>' . number_format($people) . ' ' . make_plural($people, 'person has', 'people have') . " signed up in ";
    if (OPTION_AREA_TYPE == 'WMC' && $consts==650) echo 'all ';
    echo "$consts ", area_type('plural', $consts);
    echo "<li>$people_lastday ", make_plural($people_lastday, 'person', 'people'), ' in the last day';
    echo "<li>$morethan ", make_plural($morethan, area_type() . ' has', area_type('plural') . ' have'),
         ' ', OPTION_THRESHOLD_STEP, " or more subscribers, $morethan_emailed ",
         make_plural($morethan_emailed, 'has', 'have'), ' been sent emails in this Parliament';
    echo '<li>', number_format($mp_written_messages), ' ', make_plural($mp_written_messages, 'message'),
         ' sent by ', rep_type('plural'), ", ", number_format($comments), ' ',
         make_plural($comments, 'comment'), ' made by constituents';
    print '</ul>';
    print '<p><strong>Click on the headings (e.g. ' . area_type() . ') to sort the table by different columns.</strong></p>';

    foreach ($rows as $k=>$r) {
        $c_id = $r['area_id'] ? $r['area_id'] : -1;
        $c_name = $areas_info[$c_id]['name'];
        if (isset($reps_info[$c_id]['names']))
            $r_name = join(', ', $reps_info[$c_id]['names']);
        else
            $r_name = '<em>Unknown</em>';
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
            $row .= '<td align="center">' . $r['emails_to_rep'] . '</td>';
            $row .= '<td align="center">' . $r['messages'] . '</td>';
            $row .= '<td align="center">' . $r['comments'] . '</td>';
        }
        $row .= '<td>' . prettify($r['latest']) . '</td>';
        $rows[$k] = $row;
    }
    if (count($rows)) {
        table_header($sort);
        $a = 0;
        print "<tbody>";
        foreach ($rows as $row) {
            print '<tr'.($a++%2==0?' class="alt"':'').'>';
            print $row;
            print '</tr>'."\n";
        }
        print "</tbody>";
        print '</table>';
    } else {
        print '<p>No-one has signed up to HearFromYourMP at all, anywhere, ever.</p>';
    }
}

function table_header($sort) {
    $rep_type = rep_type();

    print '<table border="0" cellpadding="4" cellspacing="0">';
    print '<thead>';
    print '<tr>';
    $cols = array(
        'c' => ucwords(area_type()),
        'p' => ucwords($rep_type),
        's'=>'Signups',
        'e' => "Emails sent to $rep_type this Parliament",
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
    print '</thead>';
    print "\n";
}

