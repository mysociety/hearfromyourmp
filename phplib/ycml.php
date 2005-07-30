<?php
/*
 * ycml.php:
 * General purpose functions specific to YCML.  This must
 * be included first by all scripts to enable error logging.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org; WWW: http://www.mysociety.org
 *
 * $Id: ycml.php,v 1.2 2005-07-30 15:35:44 matthew Exp $
 * 
 */

// Load configuration file
require_once "../conf/general";

require_once '../../phplib/db.php';
require_once '../../phplib/stash.php';
require_once "../../phplib/error.php";
require_once "../../phplib/utility.php";
require_once "../../phplib/mapit.php";
require_once "../../phplib/dadem.php";
require_once 'page.php';

/* POST redirects */
stash_check_for_post_redirect();

/* Output buffering: PHP's output buffering is broken, because it does not
 * affect headers. However, it's worth using it anyway, because in the common
 * case of outputting an HTML page, it allows us to clear any output and
 * display a clean error page when something goes wrong. Obviously if we're
 * displaying an error, a redirect, an image or anything else this will break
 * horribly.*/
ob_start();

/* ycml_handle_error NUMBER MESSAGE
 * Display a PHP error message to the user. */
function ycml_handle_error($num, $message, $file, $line, $context) {
    if (OPTION_YCML_STAGING) {
        page_header(_("Sorry! Something's gone wrong."));
        print("<strong>$message</strong> in $file:$line");
        page_footer();
    } else {
        /* Nuke any existing page output to display the error message. */
        ob_clean();
        /* Message will be in log file, don't display it for cleanliness */
        $err = p(_('Please try again later, or <a href="mailto:team@mysociety.org">email us</a> for help resolving the problem.'));
        if ($num & E_USER_ERROR) {
            $err = "<p><em>$message</em></p> $err";
        }
        ycml_show_error($err);
    }
}
err_set_handler_display('ycml_handle_error');

/* ycml_show_error MESSAGE
 * General purpose eror display. */
function ycml_show_error($message) {
    page_header(_("Sorry! Something's gone wrong."), array('override'=>true));
    print _('<h2>Sorry!  Something\'s gone wrong.</h2>') .
        "\n<p>" . $message . '</p>';
    page_footer();
}

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
    return $rep_info;
}

?>
