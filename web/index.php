<?
// index.php:
// Main page of HearFromYourMP.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.27 2007-09-18 12:58:31 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/fns.php';
require_once '../phplib/constituent.php';
require_once '../../phplib/utility.php';

page_header();
front_page();
page_footer();

function front_page() {
    $rep_type = rep_type('single');
    $rep_type_plural = rep_type();
    $rep_type_anti_plural = 's';
    if (OPTION_AREA_TYPE != 'WMC')
        $rep_type_anti_plural = '';
    $people = db_getOne('SELECT COUNT(DISTINCT(person_id)) FROM constituent');
    $consts = db_getOne('SELECT COUNT(DISTINCT(area_id)) FROM constituent');

    echo recent_messages();
    echo recent_replies();
?>
<div id="indented">
<h2 align="center">Get email from your <?=$rep_type_plural ?>
<br>Discuss it with your <?=$rep_type_plural ?> and other local people</h2>
<?  constituent_subscribe_box(); ?>

<p>If you enter your details, we&rsquo;ll add you to a queue of other people in
your <?=area_type() ?>. When enough have signed up, your <?=$rep_type_plural ?> will get sent
an email. It&rsquo;ll say &ldquo;<?=OPTION_THRESHOLD_STEP ?> of your constituents would like to hear what
you&rsquo;re up to. Hit reply to let them know&rdquo;. If they don&rsquo;t reply,
nothing will happen, until your <?=$rep_type_plural ?> get<?=$rep_type_anti_plural ?> a
further email which says there are now <?=2*OPTION_THRESHOLD_STEP ?>, then <?=3*OPTION_THRESHOLD_STEP ?>,
<?=4*OPTION_THRESHOLD_STEP ?>, <?=6*OPTION_THRESHOLD_STEP ?> &mdash; until it is nonsensical not to
reply and start talking.</p>

<p>When your <?=$rep_type_plural ?> send<?=$rep_type_anti_plural ?> you
mail it won&rsquo;t be one-way spam, and it won&rsquo;t be
an inbox-filling free-for-all. Instead, each email comes with a link
at the bottom, which takes you straight to a web page containing a
copy of the email your <?=$rep_type ?> wrote, along with any comments by other
constituents. To leave your thoughts, you just enter your text and hit
enter. There&rsquo;s no tiresome login &mdash; you can just start talking about
what they&rsquo;ve said. Safe, easy and democratic.</p>

</div>
<?
    echo '<p align="center">', $people, ' ', make_plural($people, 'person has', 'people have'),
        ' signed up in ';
    if ($consts==646) echo 'all ';
    echo $consts, ' ', area_type('plural', $consts);
    echo ' &mdash; <a href="/league">League table</a></p>';
}

