<?
// index.php:
// Main page of YCML.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.23 2006-05-31 16:10:22 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/fns.php';
require_once '../../phplib/utility.php';
page_header();
$extra = front_page();
page_footer(array('extra' => $extra));

function front_page() { ?>
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
<?
    $pagestyle = rand(0, 2);
    
    if ($pagestyle == 0) {
        ?>
<ol>
<li>Enter your details and we'll add you to a queue of other people in your
constituency who also want a more genuine sort of interaction than just being
sent newsletters.</li>
<li>When 25 people in your constituency have signed up, we send an automatic
email to your MP. That email says "25 of your constituents would like to hear
what you're up to, and would like to discuss it.  Hit reply to let them
know".</li>
<li>If your MP doesn't reply to this message, nothing will happen until your MP
gets a further email which says there are now 50, then 75, 100, 150, ...
people.</li>
<li>When your MP sends you an email, we provide a public space to discuss what
has been said. Each email from your MP comes with a link at the bottom, which
takes you straight to an extremely simple page for discussing what the MP has
just said. To leave your thoughts, you just enter your text and hit enter.
There's no tiresome login&mdash;you can just start talking about what they've
said. Safe, easy and democratic.</li>
</ol>
<?  } else {
        ?>
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

<?  }

    $pagestyle = "1.$pagestyle";

    $people = db_getOne('SELECT COUNT(DISTINCT(person_id)) FROM constituent');
    # Minus one in the next row to account for test constituency
    $consts = db_getOne('SELECT COUNT(DISTINCT(constituency)) FROM constituent') - 1;
    print "<p align='center'>$people people have signed up in ";
    if ($consts==646) print 'all ';
    print "$consts constituencies &mdash; <a href='/league'>League table</a></p>";

    return "frontpagestyle=$pagestyle";
}

?>
