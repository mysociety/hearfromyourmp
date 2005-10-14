<?
// fns.php:
// General functions for HearFromYourMP
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: fns.php,v 1.6 2005-10-14 17:34:46 matthew Exp $

require_once "../../phplib/evel.php";
require_once "../../phplib/utility.php";
require_once "../../phplib/mapit.php";
require_once "../../phplib/dadem.php";

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

# ycml_get_mp_info WMC_ID
# Given a WMC id, returns rep's name and party
function ycml_get_mp_info($wmc_id) {
    $reps = dadem_get_representatives($wmc_id);
    dadem_check_error($reps);
    $rep_info = dadem_get_representative_info($reps[0]);
    # TODO: Get method (email only?) from here
    if (OPTION_YCML_STAGING) {
        $rep_info['name'] = spoonerise($rep_info['name']);
    }
    return $rep_info;
}

// $to can be one recipient address in a string, or an array of addresses
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
        
    $values['signature'] = _("-- the HearFromYourMP team");

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

// $to can be one recipient address in a string, or an array of addresses
function ycml_send_email($to, $subject, $message, $headers = array()) {
    $spec = array(
        '_unwrapped_body_' => $message,
        'Subject' => $subject,
    );
    $spec = array_merge($spec, $headers);
    return ycml_send_email_internal($to, $spec);
}

function ycml_send_email_internal($to, $spec) {
    // Construct parameters

    // Add standard YCML From header
    if (!array_key_exists("From", $spec)) {
        $spec['From'] = '"HearFromYourMP" <' . OPTION_CONTACT_EMAIL . ">";
    }

    // With one recipient, put in header.  Otherwise default to undisclosed recip.
    if (!is_array($to)) {
        $spec['To'] = $to;
        $to = array($to);
    }

    // Send the message
    $result = evel_send($spec, $to);
    $error = evel_get_error($result);
    if ($error) 
        error_log("ycml_send_email_internal: " . $error);
    $success = $error ? FALSE : TRUE;

    return $success;
}

function ycml_make_view_url($message_id, $email) {
    return person_make_signon_url(null, $email, 'GET', OPTION_BASE_URL . '/view/message/' . $message_id, null);
}
