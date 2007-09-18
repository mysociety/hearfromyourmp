<?
// subscribe.php:
// Signing up for HearFromYourMP.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: subscribe.php,v 1.33 2007-09-18 13:08:43 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/fns.php';
require_once '../phplib/constituent.php';
require_once '../../phplib/person.php';
require_once '../../phplib/utility.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/crosssell.php';

$title = _('Signing up');
page_header($title);
$extra = '';
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
    $area_type = area_type();
    if ($row) {
        constituent_unsubscribe($P->id(), $constituent_id);
        print "Thanks! You won't receive more email from that $area_type.";
    } else {
        print "Thanks! You are already unsubscribed from that $area_type.";
    }
    print '</p>';
} else {
    constituent_subscribe_box();
}
page_footer(array('extra' => $extra));

function do_subscribe() {
    global $q_email, $q_name, $q_postcode, $q_h_postcode, $extra;
    $errors = importparams(
                array('name',      "/./", 'Please enter a name'),
                array('email',      "importparams_validate_email"),
                array('postcode',      "importparams_validate_postcode")
            );
    if (!is_null($errors))
        return $errors;

    $area_id = ycml_get_area_id($q_postcode);
    if (!$area_id)
        return array('That postcode does not appear to be in the correct region');
    $area_info = ycml_get_area_info($area_id);
    if (OPTION_AREA_ID && $area_info['parent_area_id'] != OPTION_AREA_ID)
        return array('That postcode does not appear to be in the correct region');
    $reps_info = ycml_get_reps_for_area($area_id);
    if (!count($reps_info)) {
        $rep_name = 'the future ' . $area_info['rep_name'];
    } elseif (count($reps_info)>1) {
        $rep_name = 'your ' . strtolower($area_info['rep_name_plural']);
    } else {
        list($rep_id, $rep_info) = each($reps_info);
        $rep_name = $rep_info['name'];
    }

    /* Check for authentication forwarded from WriteToThem.com */
    $external_auth = auth_verify_with_shared_secret($q_email, OPTION_AUTH_SHARED_SECRET, get_http_var('sign'));
    if ($external_auth) {
        $person = person_get_or_create($q_email, $q_name);
    } else {
        $person = person_if_signed_on();
        if (!$person || $person->email() != $q_email) {
            /* Otherwise get the user to log in. */
            $template_data = array();
            $template_data['reason_web'] = 'Before adding you to ' . $_SERVER['site_name'] . ', we need to confirm your email address.';
            $template_data['rep_name'] = $rep_name;
            $template_data['area_name'] = $area_info['name'];
            $template_data['user_name'] = $q_name;
            $template_data['user_email'] = $q_email;
            $token = auth_token_store('login', array(
                'email' => $q_email,
                'name' => $q_name,
                'stash' => stash_request(),
                'direct' => 1
            ));
            db_commit();
            $url = OPTION_BASE_URL . "/L/$token";
            $template_data['url'] = $url;
            ycml_send_email_template(array($q_email, $q_name), 'confirm-subscribe', $template_data);
            page_header("Now check your email");
?>
<p id="loudmessage">
Now check your email!<br>
We've sent you an email, and you'll need to click the link in it before you can
continue
</p>
<?
            page_footer(array('nonav' => 1));
            exit();
            /* NOTREACHED */
        }
    }
    $person_id = $person->id();

    $already_signed = db_getOne("select id from constituent where 
        area_id = ? and person_id = ?
        for update", array( $area_id, $person_id ) );
    if ($already_signed) { ?>
<p class="loudmessage" align="center">You have already signed up to <?=$_SERVER['site_name'] ?> in this <?=$area_info['type']?>!</p>
<?  #    return;
    }

    if (!$already_signed) {
        db_query("insert into constituent (
                    person_id, area_id,
                    postcode, creation_ipaddr
                )
                values (?, ?, ?, ?)", array(
                    $person_id, $area_id,
                    $q_postcode, $_SERVER['REMOTE_ADDR']
                ));
        db_commit();
        $extra = "subscribed=1";
    }
    $count = db_getOne("select count(*) from constituent where area_id = ?", $area_id);
    $nothanks = db_getRow('SELECT status,website,gender FROM rep_nothanks WHERE area_id = ?', $area_id);
?>
<p id="loudmessage"><?
    if (!$already_signed)
        print sprintf("<strong>Great!</strong> You're the %s person to sign up to get emails from %s in the %s %s. ",
            english_ordinal($count), $rep_name, $area_info['name'], $area_info['type_name']);
    if ($nothanks['status'] == 't') {
        $rep_gender = $nothanks['gender'];
        if ($rep_gender == 'm') { $nomi = 'he is'; $accu = 'him'; $geni = 'his'; }
        elseif ($rep_gender == 'f') { $nomi = 'she is'; $accu = 'her'; $geni = 'her'; }
        else { $nomi = 'they are'; $accu = 'them'; $geni = 'their'; }
        $rep_website = $nothanks['website']; ?>
Unfortunately, <?=$rep_name ?> has said <?=$nomi ?> not interested in using this
service<?
        if ($rep_website)
            print ', and asks that we encourage users to visit ' . $geni . ' website at <a href="' . $rep_website . '">' . $rep_website . '</a>';
?>. You can still contact <?=$accu ?> directly via our service
<a href="http://www.writetothem.com/">www.writetothem.com</a>.</p>

<p>In accordance with our site policy we will continue to allow signups for
<?=$area_info['name'] ?>. As our FAQ says &quot;There is one list per
<?=$area_info['type'] ?>, not per <?=$area_info['rep_name'] ?>, and we will continue to accept subscribers
regardless of whether your current <?=$area_info['rep_name'] ?> chooses to use the site or not.
If your <?=$area_info['rep_name'] ?> changes for any reason, we will hand access to the list
over to their successor.&quot;</p>
<?  } else {
        #$next_threshold = db_getOne('select rep_threshold(?, +1, 5)', $count);
        #$next_next_threshold = db_getOne('select rep_threshold(?, +1, 5)', $next_threshold);
?>
<?  }

    $advert_shown = crosssell_display_advert("hfymp", $q_email, $q_name, $q_postcode);
    if ($extra)
        $extra .= '; advert=' . $advert_shown;
    else
        $extra = 'advert=' . $advert_shown;

    if ($return = get_http_var('r'))
        print '<p><a href="' . htmlspecialchars($return). '">Continue to where you came from</a></p>';
}

?>
