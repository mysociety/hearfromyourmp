<?
// index.php:
// Main page of YCML.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: terms.php,v 1.1 2005-10-14 17:34:49 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/fns.php';
page_header();
front_page();
page_footer();

function front_page() { ?>
<h2>Terms of Use</h2>
<ol>

<li>Users are allowed a maximum of two posts per day. This policy is
part of hard-worn experience from local political discussion forums
around the world, and it has been proven to encourage people to think
and consider more before they post.

<li>The purposes of each discussion is for constructive debate on the
issues set forth in each email the MP sends.  You can write to your MP
asking for him/her to discuss certain issues, but you must stick
roughly to the topic raised

<li>The following behaviours are deemed unacceptable, and will result
in your posts being deleted, and/or your account being removed: ad
hominem attacks on the MP or other users, irrelevent and off-topic
responses (see 2), and partisan posturing which isn't really trying to
solve the problems being discussed. This may all seem a bit tough, but
HearFromYourMP is a public space, and we reserve all rights to keep it
a nice one.

<li>We do not allow you to hit reply and write directly back to the MP
&mdash; this is to prevent their email address being harvested for spam.
Rather, we encourage private responses back through
<a href="http://www.writetothem.com/">WriteToThem</a>,
linked from the bottom of every MP email.

</ol>
<? }
?>
