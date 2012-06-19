<?php
/*
 * recent.php:
 * Functions for displaying most recent stuff
 * 
 * Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org; WWW: http://www.mysociety.org
 *
 * $Id: recent.php,v 1.2 2007-12-11 20:03:01 angie Exp $
 * 
 */

require_once '../commonlib/phplib/votingarea.php';
require_once 'reps.php';

function recent_messages() {
    $q = db_query("SELECT id, subject, area_id, rep_id FROM message
        where state in ('approved','closed') ORDER BY posted DESC LIMIT 5");
    $out = '';
    while ($r = db_fetch_array($q)) {
        if (va_is_fictional_area($r['area_id']) && !OPTION_YCML_STAGING) continue;
        $area_info = ycml_get_area_info($r['area_id']);
        $rep_info = ycml_get_rep_info($r['rep_id']);
        $rep_name = rep_name($rep_info['name']);
        $out .= "<li><a href='/view/message/$r[id]'>$r[subject]</a>, by $rep_name, $area_info[name]</li>";
    }
//    if ($out) print '<div class="box"><h2>Latest messages</h2> <ul>' . $out . '</ul></div>';
    if ($out) $out = '<div class="box"><h2>Latest messages</h2> <ul>' . $out . '</ul></div>';
    return $out;
}

function recent_replies() {
  $q = db_query('SELECT comment.id,message,area_id,extract(epoch from date) as date,name
        FROM comment,message,person
        WHERE visible=1 AND comment.message = message.id AND comment.person_id = person.id
        ORDER BY date DESC LIMIT 5');
    $out = '';
    while ($r = db_fetch_array($q)) {
        if (va_is_fictional_area($r['area_id']) && !OPTION_YCML_STAGING) continue;
        $ds = prettify($r['date']);
        $out .= "<li><a href='/view/message/$r[message]#comment$r[id]'>$r[name]</a> at $ds</li>";
    }
//    if ($out) print '<div class="box"><h2>Latest replies</h2> <ul>' . $out . '</ul></div>';
    if ($out) $out = '<div class="box"><h2>Latest replies</h2> <ul>' . $out . '</ul></div>';
    return $out;
}

