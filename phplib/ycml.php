<?php
/*
 * ycml.php:
 * General purpose functions specific to YCML.  This must
 * be included first by all scripts to enable error logging.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org; WWW: http://www.mysociety.org
 *
 * $Id: ycml.php,v 1.3 2005-08-12 17:46:09 matthew Exp $
 * 
 */

// Load configuration file
require_once "../conf/general";

require_once '../../phplib/db.php';
require_once '../../phplib/stash.php';
require_once "../../phplib/error.php";
require_once "../../phplib/utility.php";
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

?>
