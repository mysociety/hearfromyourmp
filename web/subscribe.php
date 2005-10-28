<?
// subscribe.php:
// Signing up for HearFromYourMP.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: subscribe.php,v 1.11 2005-10-28 10:17:35 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/fns.php';
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
} elseif (get_http_var('direct_unsubscribe')) {
    // Clicked from email to unsubscribe
    $constituent_id = get_http_var('direct_unsubscribe');
    $P = person_if_signed_on();
    if (!$P) 
        err(_('Unexpectedly not signed on after following unsubscribe link'));
    $row = db_getRow("select * from constituent where id = ?", $constituent_id);
    print '<p>';
    if ($row) {
        constituent_unsubscribe($P->id(), $constituent_id);
        print "Thanks! You won't receive more email from that constituency.";
    } else {
        print "Thanks! You are already unsubscribed from that constituency.";
    }
    print '</p>';
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

    $wmc_id = ycml_get_constituency_id($q_postcode);
    $area_info = ycml_get_area_info($wmc_id);
    $rep_info = ycml_get_mp_info($wmc_id);

    /* Check for authentication forwarded from WriteToThem.com */
    $external_auth = auth_verify_with_shared_secret($q_email, OPTION_AUTH_SHARED_SECRET, get_http_var('sign'));
    if ($external_auth) {
        $person = person_get_or_create($q_email, $q_name);
    } else {
        /* Otherwise get the user to log in. */
        $r = array();
        $r['reason_web'] = _('Before adding you to HearFromYourMP, we need to confirm your email address.');
        $r['reason_email'] = _("You'll then be signed up to get emails from $rep_info[name] in the $area_info[name] constituency.");
        $r['reason_email_subject'] = _("Subscribe to HearFromYourMP");
        $r['instantly_send_email'] = true;
        $person = person_signon($r, $q_email, $q_name);
    }
    $person_id = $person->id();

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
<p class="loudmessage" align="center"><?=sprintf("Thanks! You're the %s person to sign up to get emails from %s in the %s constituency.
When the service launches fully, we'll be emailing every MP with 25 subscribers or more to ask them to send a message.
In the mean time, you can find out lots and lots more information about %s on our sister site
<a href=\"http://www.theyworkforyou.com/mp/?c=%s\">TheyWorkForYou</a>.",
    english_ordinal($count), $rep_info['name'], $area_info['name'], $rep_info['name'], urlencode($area_info['name'])) ?></p>
<p><?
        if ($return = get_http_var('r')) {
            print '<a href="' . htmlspecialchars($return). '">Continue to where you came from</a>';
        } else {
            print '<a href="/">HearFromYourMP home page</a></p>';
        } ?></p><?
    } else { ?>
<p class="loudmessage" align="center">You have already signed up to HearFromYourMP in this constituency!</p>
<?
    }

}

?>
