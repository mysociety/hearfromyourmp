<?
// index.php:
// Main page of HearFromYourMP.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: index.php,v 1.30 2007-12-15 14:40:29 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/constituent.php';
require_once '../phplib/recent.php';
require_once '../commonlib/phplib/utility.php';

page_header();
front_page();
page_footer();

function front_page() {

    echo recent_messages();
    echo recent_replies();

    echo '<div id="indented">';
    $P = person_if_signed_on(true); /* Don't renew any login cookie. */
    if ( $P ) 
        logged_in_content($P);
    else
        normal_content();
    echo '</div>';
    
    $people = db_getOne("SELECT COUNT(DISTINCT(person_id)) FROM constituent WHERE is_rep='f'");
    $consts = db_getOne("SELECT COUNT(DISTINCT(area_id)) FROM constituent WHERE is_rep='f'");

    echo '<p align="center">', number_format($people), ' ', make_plural($people, 'person has', 'people have'),
        ' signed up in ';
    if ($consts==650) echo 'all ';
    echo $consts, ' ', area_type('plural', $consts);
    echo ' &mdash; <a href="/league">League table</a></p>';
}

function normal_content () {

    $rep_type = rep_type('single');
    $rep_type_plural = rep_type();
    $rep_type_anti_plural = 's';
    if (OPTION_AREA_TYPE != 'WMC')
        $rep_type_anti_plural = '';

    echo '<h2 align="center">Get email from your ', $rep_type_plural,
    '<br>Discuss it with ';
    if ($rep_type != $rep_type_plural) {
        echo 'them';
    } else {
        echo "your $rep_type_plural";
    }
    echo ' and other local people</h2>';
    constituent_subscribe_box();
?>

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

<?
}


function logged_in_content ( $P ) {

    echo '<h2>Your Alerts:</h2>';

    // get all the areas that the user is subscribed to
    $area_ids     = array();
    $area_ids_all = db_getAll( 'SELECT area_id FROM constituent WHERE person_id = ?', $P->id );    
    foreach ( $area_ids_all as $arr) {
        $area_ids[] = $arr['area_id'];
    }
    
    # If not subscribed to any stop here
    if ( ! count( $area_ids) ) {
        echo "You are not subscribed to any alerts...";
        return;
    }
    
    // echo '<pre>', var_dump( $area_ids ), '</pre>';
        
    // loop over all the areas that the user is subscribed to and list the
    // representatives there.

    echo '<ul>';
    foreach ( $area_ids as $area_id ) {
        if (va_is_fictional_area($area_id) && !OPTION_YCML_STAGING) continue;
        $reps_info = ycml_get_reps_for_area($area_id);    
        $area_info = ycml_get_area_info($area_id);
        foreach ( $reps_info as $rep ) {
            $name    = rep_name($rep['name']);
            $area    = $area_info['name'];
            $area_id = $area_info['id'];
            echo "<li><a href=\"/view/$area_id\">$name</a>, $area</li>";
        }
    }    
    echo '</ul>';
    
    $messages_q = db_query( 'SELECT m.id, m.area_id, m.subject, m.rep_name FROM message m, constituent c WHERE c.person_id = ? AND c.area_id = m.area_id ORDER BY m.posted DESC LIMIT 6', $P->id );
    $out = '';
    
    while ($m = db_fetch_array($messages_q)) {
        if (va_is_fictional_area($m['area_id']) && !OPTION_YCML_STAGING) continue;
        $area_info = ycml_get_area_info($m['area_id']);
        $rep_name = rep_name($m['rep_name']);
        $out .= "<li><a href='/view/message/$m[id]'>$m[subject]</a>, by $rep_name, $area_info[name]</li>";
    }

    echo '<h2>Recent messages:</h2>';
    if ($out) 
        echo '<ul>' . $out . '</ul>';   
    else
        echo '<p>Your representatives have not sent any messages yet.</p>';

}
