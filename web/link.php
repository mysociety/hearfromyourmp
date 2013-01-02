<?
// index.php:
// Main page of YCML.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: link.php,v 1.8 2007-12-14 16:27:40 matthew Exp $

require_once '../phplib/ycml.php';

page_header();
link_to_us_page();
page_footer();

function link_box() {
    $example_postcode = get_example_postcode();
    if (OPTION_AREA_ID==2326) {
        $bgcolor = '#eeffee';
        $font = 'Helvetica, Arial, sans-serif';
        $border = '#02927b';
        $link = '#000000';
    } else {
        $bgcolor = '#d9ecff';
        $font = 'Georgia, serif';
        $border = '#c06db3';
        $link = '#20005c';
    }
    $html = '
<form style="text-align: left; position: relative; background-color: ' . $bgcolor . ';
        color: #000000; font-family: ' . $font . '; display:table; _width: 24em;
        margin: 0 0 0 0; border: solid 2px ' . $border . '; padding: 0 0 0 0;"
        action="http://' . $_SERVER['HTTP_HOST'] . '/subscribe">
    <p style="padding: 0.5em 0.5em 0.5em 0.5em; margin: 0 0 0 0;">
        <a href="http://' . $_SERVER['HTTP_HOST'] . '/" style="color: ' . $link . '; 
            text-decoration:none; font-weight:bold">
        Sign up to ' . $_SERVER['site_name'] . '</a>
        <br><strong>Name:</strong> <input type="text" name="name" id="name" size="20">
        <br><strong>Email:</strong> <input type="text" name="email" id="email" size="25">
        <br><strong>UK Postcode:</strong> <input type="text" name="postcode" id="postcode" size="10">
        <input type="hidden" name="subscribe" id="subscribe" value="1">
        <input type="submit" class="submit" value="Sign up">
        <br><em>(for example ' . $example_postcode . ')</em>
        <a href="http://' . $_SERVER['HTTP_HOST'] . '/about" style="color: ' . $link . '; font-weight:bold">
        Tell me more</a>
    </p>
</form>
';
    return $html;
}


function link_to_us_page() {
    $rep_type = rep_type();
    $contact_email = str_replace('@', '&#64;', OPTION_CONTACT_EMAIL);
?>
<h2>How to link to us</h2>

<p>You can add a simple box to your website which people can use to sign
up to hear from their <?=$rep_type ?>. It'll look like this.</p>

<?=link_box()?>

<p>To add it to your site, copy the HTML code given below into your own webpage.</p>

<pre class="htmlsource"><?=htmlspecialchars(link_box())?></pre>

<p>Feel free to hack around with the design as much as you like. Any questions,
just drop us a line at <a
href="mailto:<?=$contact_email?>"><?=$contact_email?></a>.</p>

<?

}
