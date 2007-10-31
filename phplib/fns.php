<?
// fns.php:
// General functions for HearFromYourMP
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: fns.php,v 1.23 2007-10-31 17:15:52 matthew Exp $

require_once '../../phplib/evel.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/person.php';

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

    # If being run from cron... :-/
    if (!isset($values['rep_type'])) {
        if (OPTION_AREA_TYPE=='WMC')
            $rep_type = 'MP';
        else
            $rep_type = 'Councillor';
        $values['rep_type'] = $rep_type;
    }
    if (!isset($_SERVER['site_name'])) {
        $_SERVER['site_name'] = "HearFromYour$values[rep_type]";
    }
    $values['site_name'] = $_SERVER['site_name'];
    $values['signature'] = "--\nthe " . $_SERVER['site_name'] . ' team';

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
        $spec['From'] = $_SERVER['site_name'] . ' <' . OPTION_CONTACT_EMAIL . ">";
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

