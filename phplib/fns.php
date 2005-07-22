<?
// fns.php:
// General functions for YCML
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: fns.php,v 1.2 2005-07-22 11:54:10 matthew Exp $

require_once "../../phplib/evel.php";
require_once "../../phplib/utility.php";

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
        
    $values['signature'] = _("-- the YCML team");

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
        $spec['From'] = '"YCML" <' . OPTION_CONTACT_EMAIL . ">";
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


