<?
// about.php:
// Main page of YCML.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: about.php,v 1.8 2005-12-21 17:51:47 etienne Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/fns.php';
require_once '../../phplib/votingarea.php';
page_header();
about_page();
page_footer();

function about_page() { ?>
<?  
    print recent_messages();
    print recent_replies();
    $num = db_getOne("select count(*) from message where state='approved'");
    print <<<EOF
<div id="indented">

<p><em>&ldquo;So, the voting is over. The politicians vanish to Westminster, and
everything carries on as before, right?&rdquo;</em></p>

<p>Wrong. Between elections the internet is really starting to challenge
politics as usual. As part of this change, we'd like to put you in
touch with your new MP. Not for a specific purpose, but in order to
hear what they're working on, to debate their thoughts in a safe,
friendly environment, and generally to build better, more useful
relationships between constituents and their MPs.</p>

<p>If you enter your details, we'll add you to a queue of other
people in your constituency. When enough have signed up, your MP will
get sent an email. It'll say &ldquo;25 of your constituents would like to
hear what you're up to. Hit reply to let them know&rdquo;. If they don't
reply, nothing will happen, until your MP gets a further email which says
there are now 50, then 75, 100, 150 &ndash; until it is nonsensical
not to reply and start talking.</p>

<p>When your MP sends you mail it won't be one-way spam, and it won't be
an inbox-filling free-for-all. Instead, each email comes with a link
at the bottom, which takes you straight to a web page containing a
copy of the email your MP wrote, along with any comments by other
constituents. To leave your thoughts, you just enter your text and hit
enter. There's no tiresome login &ndash; you can just start talking about
what they've said. Safe, easy and democratic.</p>

<p align="center"><strong>Sign up now - $num MPs have already sent out messages.</strong></p>
EOF;
    signup_form();
    print '</div>';
}

function signup_form() {
    print <<<EOF
<form method="post" action="/subscribe" accept-charset="utf-8">
<div id="subscribeBox">
    <input type="hidden" name="subscribe" id="subscribe" value="1">
    <label for="name">Name:</label>
    <input type="text" name="name" id="name" value="" size="20">
    <label for="email">Email:</label>
    <input type="text" name="email" id="email" value="" size="25">
    <label for="postcode">Postcode:</label> 
    <input type="text" name="postcode" id="postcode" value="" size="10">
    <input type="submit" class="submit" value="Sign up">
    <br><em>(for example OX1 3DR)</em>
</div>
</form>
EOF;
}

