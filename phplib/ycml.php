<?php
/*
 * ycml.php:
 * General purpose functions specific to YCML.  This must
 * be included first by all scripts to enable error logging.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org; WWW: http://www.mysociety.org
 *
 * $Id: ycml.php,v 1.26 2008-03-20 10:33:31 matthew Exp $
 * 
 */

// Load configuration file
require_once "../conf/general";

require_once '../commonlib/phplib/db.php';
require_once '../commonlib/phplib/stash.php';
require_once "../commonlib/phplib/error.php";
require_once '../commonlib/phplib/mapit.php';
require_once "../commonlib/phplib/utility.php";
require_once '../commonlib/phplib/votingarea.php';
require_once 'page.php';

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
        if ($num & E_USER_NOTICE)
            # Assume we've said everything we need to
            $err = "<p><em>$message</em></p>";
        else
            # Message will be in log file, don't display it for cleanliness
            $err = '<p>Please try again later, or <a href="mailto:' . OPTION_CONTACT_EMAIL . '">email us</a> for help resolving the problem.</p>';
        if ($num & (E_USER_ERROR | E_USER_WARNING)) {
            $err = "<p><em>$message</em></p> $err";
        }
        ycml_show_error($err);
    }
}
err_set_handler_display('ycml_handle_error');

/* ycml_show_error MESSAGE
 * General purpose error display. */
function ycml_show_error($message) {
    page_header(_("Sorry! Something's gone wrong."), array('override'=>true));
    print _('<h2>Sorry!  Something\'s gone wrong.</h2>') .
        "\n" . $message;
    page_footer();
}

/* Find out what domain we're on */
# TODO - assumes that the hostname has two (or more) dots in it or the $m[2]
# borks. Should behave better if the regex does not match.
preg_match('#^(.+)\.(.+?)\.(.+?)$#', strtolower($_SERVER['HTTP_HOST']), $m);
if ($m[2] == 'hearfromyourcouncillor') {
    if ($m[1] != 'cheltenham' && !preg_match('#^/admin#', $_SERVER['REQUEST_URI'])) {
        # XXX Only site for now is Cheltenham
	header('Location: http://cheltenham.hearfromyourcouncillor.com'
	    . $_SERVER['REQUEST_URI']);
	exit;
    } else {
        define('OPTION_AREA_ID', 2326);
        define('OPTION_AREA_TYPE', 'DIW');
        define('OPTION_THRESHOLD_STEP', '5');
    }
} else {
    # XXX Will do for now!
    define('OPTION_AREA_ID', 0);
    define('OPTION_AREA_TYPE', 'WMC');
    if (OPTION_THRESHOLD_STEP_STAGING) {
	    define('OPTION_THRESHOLD_STEP', OPTION_THRESHOLD_STEP_STAGING);
    } else {
    	define('OPTION_THRESHOLD_STEP', '25');
    }
    
}

/* POST redirects */
stash_check_for_post_redirect();

/*
function postcode_to_constituency_form() {
?>
<form method="get" action="/find_constituency" name="find_constituency_from_postcode" accept-charset="utf-8">
<div id="find_constituency">
<p style="text-align: center;"><a href="/about"><big>Has your MP sent any messages yet?</big></a></p>
    <label for="postcode">UK Postcode:</label>
    <input type="text" name="postcode" id="postcode" value="<?=htmlentities(get_http_var('postcode'))?>" size="10">
    <input type="submit" value="Let's find out">
</div>
</form>
<?
}
*/

# rep_type PLURALIZATION
# Returns the type of representative, by default pluralised if there's
# generally more than one - so returns "MP" and "councillors". 
# Variable is plural to always pluralize, single to always be singular
function rep_type($type = '') {
    global $va_rep_name;
    $rep_type = $va_rep_name[OPTION_AREA_TYPE];
    if ($type == 'plural' || ($type=='' && OPTION_AREA_TYPE != 'WMC'))
        $rep_type .= 's';
    return $rep_type;
} 

function rep_name($name) {
    global $va_rep_prefix, $va_rep_suffix;
    $prefix = array_key_exists(OPTION_AREA_TYPE, $va_rep_prefix) ? $va_rep_prefix[OPTION_AREA_TYPE] : '';
    $suffix = array_key_exists(OPTION_AREA_TYPE, $va_rep_suffix) ? $va_rep_suffix[OPTION_AREA_TYPE] : '';
    return trim("$prefix $name $suffix");
}

function area_type($type = '', $plural = 0) {
    global $va_type_name;
    $area_type = $va_type_name[OPTION_AREA_TYPE];
    if ($type == 'plural' && $plural != 1) {
        if (OPTION_AREA_TYPE == 'WMC') $area_type = 'constituencies';
        else $area_type .= 's';
    }
    return $area_type;
}

function get_example_postcode() {
    if (OPTION_AREA_ID)
        return canonicalise_postcode(mapit_call('area/example_postcode', OPTION_AREA_ID));
    return 'OX1 3DR';
}

