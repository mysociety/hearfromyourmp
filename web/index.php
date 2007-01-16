<?
// index.php:
// Main page of YCML.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.25 2007-01-16 13:26:47 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/fns.php';
require_once '../../phplib/utility.php';
page_header();
front_page();
page_footer();

function front_page() {
    print recent_messages();
    print recent_replies();
?>
<div id="indented">
<h2 align="center">Get email from your MP
<br>Discuss it with your MP and other local people</h2>
<form method="post" action="/subscribe" name="frontpage_subscribe" accept-charset="utf-8">
<div id="subscribeBox">
    <input type="hidden" name="subscribe" id="subscribe" value="1">
    <label for="name">Your name:</label>
    <input type="text" name="name" id="name" value="<?=htmlentities(get_http_var('name'))?>" size="20">
    <label for="email">Your email:</label>
    <input type="text" name="email" id="email" value="<?=htmlentities(get_http_var('email'))?>" size="25">
    <label for="postcode">UK postcode:</label> 
    <input type="text" name="postcode" id="postcode" value="<?=htmlentities(get_http_var('pc'))?>" size="10">
&nbsp; 
    <input type="submit" class="submit" value="Sign up">
    <input type="hidden" name="sign" id="sign" value="<?=htmlentities(get_http_var('sign'))?>">
    <br><em>(for example OX1 3DR)</em>
    </div>
</form>

<p>If you enter your details, we'll add you to a queue of other people in
your constituency. When enough have signed up, your MP will get sent
an email. It'll say "25 of your constituents would like to hear what
you're up to. Hit reply to let them know". If they don't reply,
nothing will happen, until your MP gets a further email which says
there are now 50, then 75, 100, 150 &mdash; until it is nonsensical not to
reply and start talking.</p>

<p>When your MP sends you mail it won't be one-way spam, and it won't be
an inbox-filling free-for-all. Instead, each email comes with a link
at the bottom, which takes you straight to a web page containing a
copy of the email your MP wrote, along with any comments by other
constituents. To leave your thoughts, you just enter your text and hit
enter. There's no tiresome login &mdash; you can just start talking about
what they've said. Safe, easy and democratic.</p>

</div>
<?
    $people = db_getOne('SELECT COUNT(DISTINCT(person_id)) FROM constituent');
    # Minus one in the next row to account for test constituency
    $consts = db_getOne('SELECT COUNT(DISTINCT(constituency)) FROM constituent') - 1;
    print "<p align='center'>$people people have signed up in ";
    if ($consts==646) print 'all ';
    print "$consts constituencies &mdash; <a href='/league'>League table</a></p>";
}

?>
