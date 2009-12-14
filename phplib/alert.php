<?
// alert.php:
// Email alerts for new comments.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: alert.php,v 1.1 2005-08-26 15:35:32 matthew Exp $

require_once '../commonlib/phplib/db.php';
require_once '../commonlib/phplib/person.php';

/* alert_signup PERSON MESSAGE
 * Signs PERSON up to receive email alerts when comments are posted to MESSAGE. */
function alert_signup($person_id, $message_id) {
    $already = db_getOne("SELECT id FROM alert WHERE person_id = ? AND message_id = ? FOR UPDATE", array($person_id, $message_id));
    if (is_null($already)) {
        db_query("INSERT INTO alert (person_id, message_id) values (?, ?)", array($person_id, $message_id));
    }
}

/* alert_unsubscribe PERSON_ID ALERT_ID
 * Remove the subscription to the alert, checks the alert is owned
 * by the given person. */
function alert_unsubscribe($person_id, $alert_id) {
    $row = db_getRow("select * from alert where id = ?", $alert_id);
    if (!$row) 
        err(sprintf(_("Unknown alert %d"), intval($alert_id)));

    if ($person_id != $row['person_id'])   
        err(sprintf(_("Alert %d does not belong to person %d"), intval($alert_id), intval($person_id)));

    db_getOne('select id from alert where id = ? for update', $alert_id);
    db_query("delete from alert_sent where alert_id = ?", $alert_id);
    db_query("delete from alert where id = ?", $alert_id);
    db_commit();
}

/* alert_h_description ALERT_ID
 * Returns a textual description of an alert.
 */
function alert_h_description($alert_id) {
    $row = db_getRow("select * from alert where id = ?", $alert_id);
    if (!$row)
        return false;
    return "new comments on that message";
}

/* alert_unsubscribe_link EMAIL ALERT_ID
 * Returns a URL for unsubscribing to this alert.  EMAIL is
 * the email address of the person who the caller is sending
 * an email to that will contain the URL.
 */
function alert_unsubscribe_link($alert_id, $email) {
    $url = person_make_signon_url(null, $email, 
                "POST", OPTION_BASE_URL . "/alert", array('direct_unsubscribe'=>$alert_id));
    return $url;
}
