<?
// page.php:
// Header, footer and other layout parts for pages.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: page.php,v 1.30 2009-09-24 16:16:53 matthew Exp $

require_once '../commonlib/phplib/person.php';
require_once '../commonlib/phplib/mapit.php';

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

    $rep_type = rep_type();
    if (strstr($rep_type, 'councillor')) $rep_type = 'councillor';
    $rep_type_u = ucfirst($rep_type);
    $site_name = "HearFromYour$rep_type_u";
    $_SERVER['site_name'] = $site_name;
    if (OPTION_AREA_ID) {
        $area_info = mapit_get_voting_area_info(OPTION_AREA_ID);
        $site_name .= ' &ndash; ' . short_name($area_info['name']);
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
        print htmlspecialchars($title) . ' &ndash; ';
    echo $site_name;
    if (!$title)
        echo " &ndash; Sign up to hear from your $rep_type about local issues, and to discuss them with other constituents";
?></title>
<style type="text/css">@import url("/ycml.css");</style>
<!--[if LT IE 7]>
<style type="text/css">@import url("/ie6.css");</style>
<![endif]-->

<?
    if (OPTION_AREA_ID==2326) {
        echo '<style type="text/css">@import url("/css/cheltenham.css");</style>';
    }
    echo '</head><body>';
    
    // Warn that we are on a testing site
    $devwarning = array();
    if (OPTION_YCML_STAGING) {
        $devwarning[] = _('This is a test site for developers only.<br><strong>These are not
        messages from representatives; nothing here is real.</strong><br>You probably want
<a href="http://www.hearfromyourmp.com/">the real site</a>.');
    }
    if (count($devwarning) > 0) {
        echo '<p class="noprint" align="center" style="color: #b00; border-bottom: 1px solid #b00; background-color: #fcc; margin: 0">';
        echo join('<br>', $devwarning);
        echo '</p>';
    }
    
    // Special case Cheltenham
    if (OPTION_AREA_ID==2326) {
        echo '<a title="Back to Cheltenham Council website" href="http://www.cheltenham.gov.uk/"><img id="cobrand_logo" alt="Return to www.cheltenham.gov.uk" src="/cheltenham-logo.jpg"></a>';
    }

    echo '<h1>';
    echo '<a href="http://www.mysociety.org/"><img alt="Visit mySociety.org" src="/mysociety-dark-50.png" id="logo"><span id="logoie"></span></a>';

    if ($_SERVER['REQUEST_URI']!='/') print '<a href="/">';
    echo $site_name;
    if ($_SERVER['REQUEST_URI']!='/') print '</a>';

    echo '</h1>';

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

    echo '<div id="w"><div id="content">';
}

/* page_footer PARAMS
 * Print bottom of HTML page. This closes the "content" <div>.  If
 * PARAMS['nonav'] is true then the footer navigation is not displayed. If 
 * PARAMS['extra'] is set, then it was passed to track_event as extra
 * user-tracking information to be associated with this page view. */
function page_footer($params = array()) {
    static $footer_outputted = 0; 
    if (!$footer_outputted) {
        $footer_outputted = 1;

    echo '</div></div>';
?>
<p id="footer">
<a href="/about">About this site</a>
<? if (OPTION_AREA_TYPE=='WMC') { ?> | <a href="/about-mps">If you are an MP</a><? } ?>
| <a href="/league">League table</a>
| <a href="/link">Link to us</a>
| <a href="/contact">Contact us</a>
<br>
<small>Built by <a href="http://www.mysociety.org/">mySociety</a>.
Powered by <a href="http://www.m247.com/">M247</a>.
<a href="/privacy">Privacy policy</a>.
<a href="/terms">Terms of use</a>.</small>
</p>
<?  if (OPTION_WEB_DOMAIN == 'www.hearfromyourmp.com') { ?>
<!-- Piwik -->
<script type="text/javascript">
var pkBaseURL = (("https:" == document.location.protocol) ? "https://piwik.mysociety.org/" : "http://piwik.mysociety.org/");
document.write(unescape("%3Cscript src='" + pkBaseURL + "piwik.js' type='text/javascript'%3E%3C/script%3E"));
</script><script type="text/javascript">
try {
var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", 3);
piwikTracker.trackPageView();
piwikTracker.enableLinkTracking();
} catch( err ) {}
</script><noscript><p><img src="http://piwik.mysociety.org/piwik.php?idsite=3" style="border:0" alt=""/></p></noscript>
<!-- End Piwik Tag -->
<?  } ?>
</body>
</html>
<? } }

function short_name($name) {
    # Special case Durham as it's the only place with two councils of the same name
    if ($name == 'Durham County Council') return 'Durham County';
    if ($name == 'Durham City Council') return 'Durham City';
    $name = preg_replace('/ (Borough|City|District|County) Council$/', '', $name);
    $name = preg_replace('/ Council$/', '', $name);
    $name = preg_replace('/ & /', ' and ', $name);
    return $name;
}
