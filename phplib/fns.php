<?
// fns.php:
// General functions for HearFromYourMP
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: fns.php,v 1.19 2007-08-01 15:07:54 matthew Exp $

require_once "../../phplib/evel.php";
require_once "../../phplib/utility.php";
require_once "../../phplib/mapit.php";
require_once "../../phplib/dadem.php";
require_once '../../phplib/votingarea.php';

# ycml_get_constituency_id POSTCODE
# Given a postcode, returns the WMC id
function ycml_get_constituency_id($postcode) {
    $postcode = canonicalise_postcode($postcode);
    $areas = mapit_get_voting_areas($postcode);
    if (mapit_get_error($areas)) {
        /* This error should never happen, as earlier postcode validation in form will stop it */
        err('Invalid postcode while subscribing, please check and try again.');
    }
    return $areas['WMC'];
}

# ycml_get_constituency_info WMC_ID
# Given a WMC id, returns (REP NAME, REP SUFFIX, AREA NAME)
function ycml_get_area_info($wmc_id) {
    $area_info = mapit_get_voting_area_info($wmc_id);
    mapit_check_error($area_info);
    if ($area_info['type'] != 'WMC')
        err('Invalid area type');
    return $area_info;
}

function ycml_get_all_areas_info($q, $ignore_fictional = true) {
    $areas_info = array();
    $cache = db_getAll('SELECT id,name FROM constituency_cache');
    foreach ($cache as $r) {
        $areas_info[$r['id']] = array('name'=>$r['name']);
    }

    $rows = array(); $ids = array();
    while ($r = db_fetch_array($q)) {
        if ($r['constituency'] && $ignore_fictional && va_is_fictional_area($r['constituency']) && !OPTION_YCML_STAGING)
            continue;
        $rows[] = array_map('htmlspecialchars', $r);
        if ($r['constituency'] && !array_key_exists($r['constituency'], $areas_info))
            $ids[] = $r['constituency'];
    }

    if (count($ids)) {
        $areas_info2 = mapit_get_voting_areas_info($ids);
        foreach ($areas_info2 as $id => $row) {
            db_query('INSERT INTO constituency_cache (id,name) VALUES (?,?)', array($id, $row['name']));
        }
        db_commit();
        $areas_info = $areas_info + $areas_info2;
    }

    return array($areas_info, $rows);
}

# ycml_get_mp_info WMC_ID
# Given a WMC id, returns rep's name and party
function ycml_get_mp_info($wmc_id, $all = 0) {
    $reps = dadem_get_representatives($wmc_id, $all);
    dadem_check_error($reps);
    if (count($reps) == 0)
        return array();
    if (!$all && count($reps) != 1)
        err("Unexpectedly found ".count($reps)." MPs for your postcode.");
    $reps_info = dadem_get_representatives_info($reps);
    # TODO: Get method (email only?) from here
    foreach ($reps_info as $id => $rep_info) {
        if (OPTION_YCML_STAGING)
            $rep_info['name'] = spoonerise($rep_info['name']);
        if (array_key_exists('id', $rep_info) && file_exists('/data/vhost/www.hearfromyourmp.com/docs/mpphotos/'.$rep_info['id'].'.jpg'))
            $rep_info['image'] = 'http://www.hearfromyourmp.com/mpphotos/' . $rep_info['id'] . '.jpg';
    }
    return $rep_info;
}

function ycml_get_all_reps_info($ids) {
    $reps_info = array();
    $cache = db_getAll('SELECT id, rep_id, rep_name FROM constituency_cache');
    foreach ($cache as $r) {
        if ($r['rep_name'] && $r['rep_id'])
            $reps_info[$r['id']] = array('id'=>$r['rep_id'], 'name'=>$r['rep_name']);
    }

    $ids = array_values(array_diff($ids, array_keys($reps_info)));
    if (count($ids)) {
        $reps = dadem_get_representatives($ids);
        dadem_check_error($reps);
        $reps_info2 = array();
        foreach ($reps as $c_id => $row) {
            if (isset($row[0])) {
                $reps_info2[] = $row[0];
            } else {
                $reps_info[$c_id] = array('id'=>0, 'name'=>'-');
            }
        }
        $reps_info2 = dadem_get_representatives_info($reps_info2);
        foreach ($reps_info2 as $id => $row) {
            $reps_info[$row['voting_area']] = array('id'=>$id, 'name'=>$row['name']);
            db_query('UPDATE constituency_cache SET rep_id=?, rep_name=?, rep_created=? WHERE id=?',
                array($id, $row['name'], $row['whencreated'], $row['voting_area']));
        }
        db_commit();
    }
    return $reps_info;
}

// $to can either be one address in a string, or an array of (address, name)
function ycml_send_email_template($to, $template_name, $values, $headers = array()) {
    global $lang;

    if (array_key_exists('date', $values))
        $values['pretty_date'] = prettify($values['date'], false);
    if (array_key_exists('name', $values)) {
        $values['creator_name'] = $values['name'];
        $values['name'] = null;
    }
    if (array_key_exists('email', $values)) {
        $values['creator_email'] = $values['email'];
        $values['email'] = null;
    }
        
    $values['signature'] = _("--\nthe HearFromYourMP team");

    if (is_file("../templates/emails/$lang/$template_name"))
        $template = file_get_contents("../templates/emails/$lang/$template_name");
    else
        $template = file_get_contents("../templates/emails/$template_name");

    $spec = array(
        '_template_' => $template,
        '_parameters_' => $values
    );
    $spec = array_merge($spec, $headers);
    return ycml_send_email_internal($to, $spec);
}

// $to can be as above
function ycml_send_email($to, $subject, $message, $headers = array()) {
    $spec = array(
        '_unwrapped_body_' => $message,
        'Subject' => $subject,
    );
    $spec = array_merge($spec, $headers);
    return ycml_send_email_internal($to, $spec);
}

function ycml_send_email_internal($to, $spec) {
    // Add standard YCML From header
    if (!array_key_exists("From", $spec)) {
        $spec['From'] = '"HearFromYourMP" <' . OPTION_CONTACT_EMAIL . ">";
    }

    $spec['To'] = array($to);
    $recip = is_array($to) ? $to[0] : $to;
    $result = evel_send($spec, $recip);
    $error = evel_get_error($result);
    if ($error) 
        error_log("ycml_send_email_internal: " . $error);
    $success = $error ? FALSE : TRUE;

    return $success;
}

function ycml_make_view_url($message_id, $email) {
    return person_make_signon_url(null, $email, 'GET', OPTION_BASE_URL . '/view/message/' . $message_id, null);
}

function ycml_constituency_lookup($c) {
    $c = strtolower(str_replace(array('_','.'), array(' ','&amp;'), $c));
    $id = db_getOne('SELECT id FROM constituency_cache WHERE name ILIKE ?', $c);
    return $id;
}
