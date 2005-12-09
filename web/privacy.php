<?
// privacy.php:
// Privacy policy
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: privacy.php,v 1.1 2005-12-09 12:23:46 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/fns.php';
page_header();
privacy_policy();
page_footer();

function privacy_policy() { ?>
<h2>Privacy policy</h2>

<p>mySociety will not supply the names, email addresses, postcodes or any
other personal data of constituents to anyone, including MPs, except in
two limited circumstances:</p>

<ol type="a">
<li>a constituent uses the link we include to WriteToThem.com to contact
their MP, in which case they are obliged to reveal their email and postal
addresses to the MP, in accordance with that site's usage policy. 
<li>a constituent chooses to respond on the public forum on HearFromYourMP.com,
in which case we will reveal their name only, adjacent to their post(s). We
will not reveal any other information, unless the user includes personal
information on their post.
</ol>

<p>We believe that constituents have the right to listen to what their
representatives say without revealing their identities - this is what
happens every time somoene buys a local newspaper, or stands anonymously
in a crowd listening to an MP. We see no reason to challenge it.</p>

<? }
?>
