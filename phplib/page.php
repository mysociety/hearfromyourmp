<?
// page.php:
// Header, footer and other layout parts for pages.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: page.php,v 1.17 2006-08-08 15:11:07 chris Exp $

require_once '../../phplib/person.php';
require_once '../../phplib/tracking.php';

/* page_header TITLE [PARAMS]
 * Print top part of HTML page, with the given TITLE. This prints up to the
 * start of the "content" <div>.  If PARAMS['nonav'] is true then the top 
 * title and navigation are not displayed, or if PARAMS['noprint'] is true
 * then they are not there if the page is printed.  */
function page_header($title='', $params = array()) {
    static $header_outputted = 0;
    if ($header_outputted && !array_key_exists('override', $params)) {
        return;
    }
    header('Content-Type: text/html; charset=utf-8');
    $P = person_if_signed_on(true); /* Don't renew any login cookie. */
    $header_outputted = 1;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?
    if ($title)
        print htmlspecialchars($title) . ' - ';
?>HearFromYourMP.com<?
    if (!$title)
        print ' - Sign up to hear from your MP about local issues, and to discuss them with other constituents';
?></title>
<style type="text/css">@import url("/ycml.css");</style>
</head>
<body>
<h1><? if ($_SERVER['REQUEST_URI']!='/') print '<a href="/">'; ?>
HearFromYourMP<?
if ($_SERVER['REQUEST_URI']!='/') print '</a>';
print ' <span id="betatest">Beta&nbsp;Test</span>';
?>
</h1>
<?
    // Display who is logged in 
    if ($P) {
        print '<p id="signedon" class="noprint">';
        print _('Hello, ');
        if ($P->name_or_blank())
            print htmlspecialchars($P->name);
        else 
            print htmlspecialchars($P->email);
        print ' <small>(<a href="/logout">';
        print _('this isn\'t you?  click here');
        print '</a>)</small></p>';
    }

    // Warn that we are on a testing site
    $devwarning = array();
    if (OPTION_YCML_STAGING) {
        $devwarning[] = _('This is a test site for developers only.<br><strong>These are not
        messages from the MPs; nothing here is real.</strong><br>You probably want
<a href="http://www.hearfromyourmp.com/">the real site</a>.');
    } ?>
<div id="w"><div id="content">
<?
    if (count($devwarning) > 0) {
        ?><p class="noprint" align="center" style="color: #cc0000;"><?
        print join('<br>', $devwarning);
        ?></p><?
    }
}

/* page_footer PARAMS
 * Print bottom of HTML page. This closes the "content" <div>.  If
 * PARAMS['nonav'] is true then the footer navigation is not displayed. If 
 * PARAMS['extra'] is set, then it is passed to track_event as extra
 * user-tracking information to be associated with this page view. */
function page_footer($params = array()) {
    static $footer_outputted = 0; 
    if (!$footer_outputted) {
        $footer_outputted = 1;
?>
</div></div>
<?
    $extra = null;
    if (array_key_exists('extra', $params) && $params['extra'])
        $extra = $params['extra'];
    track_event($extra);
?>
<p id="footer">
Built by <a href="http://www.mysociety.org/">mySociety</a>.
Powered by <a href="http://www.easynet.net/publicsector/">Easynet</a>.
<a href="/privacy">Privacy policy</a>
| <a href="/terms">Terms of use</a>
| <a href="/about">About this site</a>
| <a href="/link">Link to us</a>
| <a href="/contact">Contact us</a></p>
</body>
</html>
<?  }
}

?>
