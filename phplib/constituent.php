<?
// constituent.php:
// Constituent table features.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: constituent.php,v 1.7 2007-10-31 18:14:02 matthew Exp $

require_once '../commonlib/phplib/person.php';

/* constituent_unsubscribe PERSON_ID ALERT_ID
 * Remove the subscription to the alert, checks the alert is owned
 * by the given person.
 */
function constituent_unsubscribe($person_id, $signup_id) {
    $row = db_getRow("select * from constituent where id = ?", $signup_id);
    if (!$row) 
        err(sprintf(_("Unknown alert %d"), intval($signup_id)));

    if ($person_id != $row['person_id'])   
        err(sprintf(_("Alert %d does not belong to person %d"), intval($signup_id), intval($person_id)));

    db_getOne('select id from constituent where id = ? for update', $signup_id);
    #    db_query("delete from alert_sent where alert_id = ?", $signup_id);
    db_query("delete from constituent where id = ?", $signup_id);
    db_commit();
}

/* constituent_unsubscribe_link EMAIL ALERT_ID
 * Returns a URL for unsubscribing from YCML.  EMAIL is
 * the email address of the person who the caller is sending
 * an email to that will contain the URL.
 */
function constituent_unsubscribe_link($signup_id, $email) {
    $url = person_make_signon_url(null, $email, 
                "POST", OPTION_BASE_URL . "/subscribe", array('direct_unsubscribe'=>$signup_id));
    return $url;
}

/* Display form for YCML sign up. */
function constituent_subscribe_box($array = array()) {
?>
<form accept-charset="utf-8" method="post" action="/subscribe" name="subscribe_form">
<div id="subscribeBox">
</div>
</form>
<? 

}

function constituent_is_rep($person_id, $area_id) {
    return db_getOne('SELECT is_rep FROM constituent
                        WHERE person_id = ? AND area_id = ?', array($person_id, $area_id) );
}
?>
