<?
// constituent.php:
// Constituent table features.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: constituent.php,v 1.6 2007-09-18 12:58:30 matthew Exp $

require_once '../../phplib/person.php';

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
    $name = get_http_var('name');
    if (array_key_exists('email', $array))
        $email = $array['email'];
    else
        $email = get_http_var('email');
    $postcode = get_http_var('postcode');
    $return = get_http_var('r');

    $P = person_if_signed_on();
    if (!is_null($P)) {
        if (is_null($name) || !$name)
            $name = $P->name_or_blank();
        if (is_null($email) || !$email)
            $email = $P->email();
    }

    if (OPTION_AREA_ID)
        $example_postcode = canonicalise_postcode(mapit_get_example_postcode(OPTION_AREA_ID));
    else
        $example_postcode = 'OX1 3DR';

?>
<form accept-charset="utf-8" method="post" action="/subscribe" name="subscribe_form">
<div id="subscribeBox">
<input type="hidden" name="r" value="<?=htmlspecialchars($return) ?>">
    <input type="hidden" name="subscribe" id="subscribe" value="1">
    <label for="name">Your name:</label>
    <input type="text" name="name" id="name" value="<?=htmlspecialchars($name) ?>" size="20">
    <label for="email">Your email:</label>
    <input type="text" name="email" id="email" value="<?=htmlspecialchars($email) ?>" size="25">
    <label for="postcode">UK postcode:</label> 
    <input type="text" name="postcode" id="postcode" value="<?=htmlspecialchars($postcode) ?>" size="10">
&nbsp; 
    <input type="submit" class="submit" value="Sign up">
    <input type="hidden" name="sign" id="sign" value="<?=htmlentities(get_http_var('sign'))?>">
    <br><em>(for example <?=$example_postcode ?>)</em>
</div>
</form>
<? 

}

function constituent_is_rep($person_id, $area_id) {
    return db_getOne('SELECT is_rep FROM constituent
                        WHERE person_id = ? AND area_id = ?', array($person_id, $area_id) );
}
?>
