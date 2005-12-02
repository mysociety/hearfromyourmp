<?
// index.php:
// Main page of YCML.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.18 2005-12-02 18:05:55 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/fns.php';
require_once '../../phplib/utility.php';
page_header();
front_page();
page_footer();

function front_page() { ?>
<h2 align="center">Get email from your MP
<br>Discuss it with your MP and other local people</h2>
<form method="post" action="/subscribe" name="frontpage_subscribe" accept-charset="utf-8">
<div id="subscribeBox">
    <input type="hidden" name="subscribe" id="subscribe" value="1">
    <label for="name">Name:</label>
    <input type="text" name="name" id="name" value="<?=htmlentities(get_http_var('name'))?>" size="20">
    <label for="email">Email:</label>
    <input type="text" name="email" id="email" value="<?=htmlentities(get_http_var('email'))?>" size="25">
    <label for="postcode">UK Postcode:</label> 
    <input type="text" name="postcode" id="postcode" value="<?=htmlentities(get_http_var('pc'))?>" size="10">
&nbsp; 
    <input type="submit" class="submit" value="Sign up">
    <input type="hidden" name="sign" id="sign" value="<?=htmlentities(get_http_var('sign'))?>">
    <br><em>(for example OX1 3DR)</em>
    </div>
</form>
<p style="text-align: center;"><a href="/about"><big>How this site works</big></a></p>
<?
        $people = db_getOne('SELECT COUNT(DISTINCT(person_id)) FROM constituent');
        # Minus one in the next row to account for test constituency
        $consts = db_getOne('SELECT COUNT(DISTINCT(constituency)) FROM constituent') - 1;
        print "<p align='center'>$people people have signed up in $consts constituencies &mdash; <a href='/league'>League table</a></p>";
} ?>
