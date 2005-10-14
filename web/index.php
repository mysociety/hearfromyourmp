<?
// index.php:
// Main page of YCML.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.7 2005-10-14 17:34:48 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/fns.php';
page_header();
front_page();
page_footer();

function front_page() { ?>
<h2 align="center">Sign up to hear from your MP about local issues,<br>and to discuss them with other constituents</h2>
<form method="post" action="/subscribe">
<div id="subscribeBox">
    <input type="hidden" name="subscribe" id="subscribe" value="1">
    <label for="name">Name:</label>
    <input type="text" name="name" id="name" value="" size="20">
    <label for="email">Email:</label>
    <input type="text" name="email" id="email" value="" size="25">
    <label for="postcode">Postcode:</label> 
    <input type="text" name="postcode" id="postcode" value="" size="10">
&nbsp; 
    <input type="submit" class="submit" value="Sign up">
    <br><em>(for example OX1 3DR)</em>
    </div>
</form>
<p style="text-align: center;"><a href="/about">Tell me more</a></p>
<?
        $people = db_getOne('SELECT COUNT(DISTINCT(person_id)) FROM constituent');
        $consts = db_getOne('SELECT COUNT(DISTINCT(constituency)) FROM constituent');
        print "<p align='center'>Current status: $people people have signed up in $consts constituencies.</p>";
} ?>
