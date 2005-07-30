<?
// subscribe.php:
// Signing up for YCML.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: subscribe.php,v 1.4 2005-07-30 15:35:46 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/constituent.php';
require_once '../../phplib/person.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/importparams.php';

$title = _('Signing up');
page_header($title);
if (get_http_var('subscribe')) {
    $errors = do_subscribe();
    if (is_array($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
        constituent_subscribe_box();
    }
/* } elseif (get_http_var('direct_unsubscribe')) {
    // Clicked from email to unsubscribe
    $alert_id = get_http_var('direct_unsubscribe');
    $P = person_if_signed_on();
    if (!$P) 
        err(_('Unexpectedly not signed on after following unsubscribe link'));
    $desc = alert_h_description($alert_id);
    print '<p>';
    if ($desc) {
        alert_unsubscribe($P->id(), $alert_id);
        printf(_("Thanks!  You won't receive more email about %s."), $desc);
    } else {
        print _("Thanks!  You are already unsubscribed from YCML.");
    }
    print '</p>';
*/
} else {
    constituent_subscribe_box();
}
page_footer();

function do_subscribe() {
    global $q_email, $q_name, $q_postcode;
    $errors = importparams(
                array('name',      "/./", 'Please enter a name'),
                array('email',      "importparams_validate_email"),
                array('postcode',      "importparams_validate_postcode")
            );
    if (!is_null($errors))
        return $errors;

    /* Get the user to log in. */
    $r = array();
    $r['reason_web'] = _('Before adding you to YCML, we need to confirm your email address.');
    $r['reason_email'] = _("You'll then be emailed when the threshold is reached, etc.");
    $r['reason_email_subject'] = _("Subscribe to YCML");
    $person = person_signon($r, $q_email, $q_name);
    $person_id = $person->id();

    $wmc_id = ycml_get_constituency_id($q_postcode);
    $area_info = ycml_get_area_info($wmc_id);
    $rep_info = ycml_get_mp_info($wmc_id);

    $already_signed = db_getOne("select id from constituent where 
        constituency = ? and person_id = ?
        for update", array( $wmc_id, $person_id ) );
    if (!$already_signed) {
        db_query("insert into constituent (
                    person_id, constituency,
                    postcode, creation_ipaddr
                )
                values (?, ?, ?, ?)", array(
                    $person_id, $wmc_id,
                    $q_postcode, $_SERVER['REMOTE_ADDR']
                ));
        db_commit();
    
        $count = db_getOne("select count(*) from constituent where constituency = ?", array( $wmc_id ) );
        if ($count == 20) {
            # Time to send first email
        } elseif ($count == 50) {
            # Time to send second email
        } elseif ($count % 100 == 0) {
            # Send another reminder email?
        }
?>
<p class="loudmessage" align="center"><?=sprintf(_("Thanks for subscribing to %s's YCML for the %s constituency!  You're the %s person to sign up. You'll now get emailed when threshold reached, person sends then, etc."), $rep_info['name'], $area_info['name'], english_ordinal($count)) ?> <a href="/"><?=_('YCML home page') ?></a></p>
<?
    } else { ?>
<p class="loudmessage" align="center">You have already signed up to this YCML!</p>
<?
    }

}

?>
