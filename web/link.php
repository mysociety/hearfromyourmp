<?
// index.php:
// Main page of YCML.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: link.php,v 1.4 2007-09-18 12:58:31 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/fns.php';
require_once '../../phplib/mapit.php';

page_header();
link_to_us_page();
page_footer();

function link_box() {
    $rep_type = rep_type();
    if (OPTION_AREA_ID) 
        $example_postcode = canonicalise_postcode(mapit_get_example_postcode(OPTION_AREA_ID));
    else
        $example_postcode = 'OX1 3DR';
    $html = '
<form style="text-align: left; position: relative; background-color: #d9ecff;
        color: #000000; font-family: Georgia, serif; display:table; _width: 24em;
        margin: 0 0 0 0; border: solid 2px #c06db3; padding: 0 0 0 0;"
        action="http://www.hearfromyourmp.com/subscribe">
    <p style="padding: 0.5em 0.5em 0.5em 0.5em; margin: 0 0 0 0;">
        <a href="http://www.hearfromyourmp.com" style="color: #20005C; 
            text-decoration:none; font-weight:bold">
        Sign up to Hear From Your ' . $rep_type . '</a>
        <br><strong>Name:</strong> <input type="text" name="name" id="name" size="20">
        <br><strong>Email:</strong> <input type="text" name="email" id="email" size="25">
        <br><strong>UK Postcode:</strong> <input type="text" name="postcode" id="postcode" size="10">
        <input type="hidden" name="subscribe" id="subscribe" value="1">
        <input type="submit" class="submit" value="Sign up">
        <br><em>(for example ' . $example_postcode . ')</em>
        <a href="http://www.hearfromyourmp.com/about" style="color: #20005C; font-weight:bold">
        Tell me more</a>
    </p>
</form>
';
    return $html;
}


function link_to_us_page() {
    $rep_type = rep_type();
?>
<h2>How to link to us</h2>

<p>You can add a simple box to your website which people can use to sign
up to hear from their <?=$rep_type ?>. It'll look like this.</p>

<?=link_box()?>

<p>To add it to your site, copy the HTML code given below into your own webpage.</p>

<pre class="htmlsource"><?=htmlspecialchars(link_box())?></pre>

<p>Feel free to hack around with the design as much as you like. Any questions,
just drop us a line at <a
href="mailto:team@hearfromyourmp.com">team@hearfromyourmp.com</a>.</p>


<?

}
