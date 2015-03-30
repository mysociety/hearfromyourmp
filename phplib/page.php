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
        $area_info = mapit_call('area', OPTION_AREA_ID);
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
<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Source+Sans+Pro:400,600">
<link rel="stylesheet" type="text/css" media="all" href="/assets/css/banner.css">
<style type="text/css">@import url("/ycml.css");</style>
<!--[if LT IE 7]>
<style type="text/css">@import url("/ie6.css");</style>
<![endif]-->

<?
    if (OPTION_AREA_ID==2326) {
        echo '<style type="text/css">@import url("/css/cheltenham.css");</style>';
    }
    if (OPTION_WEB_DOMAIN == 'www.hearfromyourmp.com') { ?>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-660910-10']);
  _gaq.push (['_gat._anonymizeIp']);
  _gaq.push(['_trackPageview']);
  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>
<?
    }
?>
</head>
<body>

<div class="retirement-banner retirement-banner--hearfromyourmp">
  <div class="retirement-banner__inner">
    <a class="retirement-banner__logo" href="https://www.mysociety.org/">mySociety</a>
    <p class="retirement-banner__description">
HearFromYourMP is now closed. The site is available as an archive for you to
browse, but you can no longer sign up or receive updates.
<a href="/closed">Find out more&hellip;</a></p>
  </div>
</div>

<?
    
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
<br>
<small>Built by <a href="http://www.mysociety.org/">mySociety</a>.
Powered by <a href="http://www.bytemark.co.uk/">Bytemark</a>.
<a href="/privacy">Privacy and cookies</a>.
<a href="/terms">Terms of use</a>.</small>
</p>
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
