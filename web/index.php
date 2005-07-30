<?
// index.php:
// Main page of YCML.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.4 2005-07-30 17:00:56 matthew Exp $

require_once '../phplib/ycml.php';
page_header();
front_page();
page_footer();

function front_page() { ?>
<img align="right" src="../shoupsm.png" width="204" height="329" alt="">
<?  $q = db_query('SELECT id,subject,constituency FROM message ORDER BY posted DESC LIMIT 5');
    $out = '';
    while ($r = db_fetch_array($q)) {
        $area_info = ycml_get_area_info($r['constituency']);
        $rep_info = ycml_get_mp_info($r['constituency']);
        $out .= "<li><a href='/view/message/$r[id]'>$r[subject]</a>, by $rep_info[name] $area_info[rep_suffix], $area_info[name]</li>";
    }
    if ($out) print '<div class="box"><h2>Latest messages</h2> <ul>' . $out . '</ul></div>';
    $q = db_query('SELECT comment.id,message,constituency,extract(epoch from date) as date,name
        FROM comment,message,person
        WHERE comment.message = message.id AND comment.person_id = person.id
        ORDER BY posted DESC LIMIT 5');
    $out = '';
    while ($r = db_fetch_array($q)) {
        $area_info = ycml_get_area_info($r['constituency']);
        $rep_info = ycml_get_mp_info($r['constituency']);
        $ds = prettify($r['date']);
        $out .= "<li><a href='/view/message/$r[message]#comment$r[id]'>$r[name]</a> at $ds</li>";
    }
    if ($out) print '<div class="box"><h2>Latest replies</h2> <ul>' . $out . '</ul></div>';
    signup_form();
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
get sent an email. It'll say &ldquo;20 of your constituents would like to
hear what you're up to &ndash; hit reply to let them know&rdquo;. If they don't
reply, nothing will happen, until they get an email which says there
are now 100 people; 200 people; 500 people &ndash; until it is nonsensical
not to reply and start talking.</p>

<p>When your MP replies, it won't be one-way spam, and it won't be an
inbox-filling free-for-all. Instead, each email will have a link at
the bottom, which will take you straight to a forum where the first
post will contain the MP's email. There'll be no tiresome login &ndash; you
can just start talking about what they've said. Safe, easy and
democratic.</p>

<p><strong>Sign up now.</strong></p>
</div>
EOF;
}

function signup_form() {
    print <<<EOF
<form class="box" method="post" action="/subscribe">
<h2>Sign up now</h2>
    <input type="hidden" name="subscribe" id="subscribe" value="1">
    <label for="name">Name:</label>
    <input type="text" name="name" id="name" value="" size="20">
    <label for="email">Email:</label>
    <input type="text" name="email" id="email" value="" size="25">
    <label for="postcode">Postcode:</label> 
    <input type="text" name="postcode" id="postcode" value="" size="10">
    <input type="submit" class="submit" value="Sign up">
    <br><em>(for example OX1 3DR)</em>
</form>
EOF;
}

