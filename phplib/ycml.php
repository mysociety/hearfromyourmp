<?php
/*
 * ycml.php:
 * General purpose functions specific to YCML.  This must
 * be included first by all scripts to enable error logging.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org; WWW: http://www.mysociety.org
 *
 * $Id: ycml.php,v 1.19 2007-09-18 13:08:42 matthew Exp $
 * 
 */

// Load configuration file
require_once "../conf/general";

require_once '../../phplib/db.php';
require_once '../../phplib/stash.php';
require_once "../../phplib/error.php";
require_once "../../phplib/utility.php";
require_once '../../phplib/votingarea.php';
require_once 'page.php';

/* Output buffering: PHP's output buffering is broken, because it does not
 * affect headers. However, it's worth using it anyway, because in the common
 * case of outputting an HTML page, it allows us to clear any output and
 * display a clean error page when something goes wrong. Obviously if we're
 * displaying an error, a redirect, an image or anything else this will break
 * horribly.*/
ob_start();

/* ycml_handle_error NUMBER MESSAGE
 * Display a PHP error message to the user. */
function ycml_handle_error($num, $message, $file, $line, $context) {
    if (OPTION_YCML_STAGING) {
        page_header(_("Sorry! Something's gone wrong."));
        print("<strong>$message</strong> in $file:$line");
        page_footer();
    } else {
        /* Nuke any existing page output to display the error message. */
        ob_clean();
        if ($num & E_USER_NOTICE)
            # Assume we've said everything we need to
            $err = "<p><em>$message</em></p>";
        else
            # Message will be in log file, don't display it for cleanliness
            $err = '<p>Please try again later, or <a href="mailto:team@mysociety.org">email us</a> for help resolving the problem.</p>';
        if ($num & (E_USER_ERROR | E_USER_WARNING)) {
            $err = "<p><em>$message</em></p> $err";
        }
        ycml_show_error($err);
    }
}
err_set_handler_display('ycml_handle_error');

/* POST redirects */
stash_check_for_post_redirect();

/* ycml_show_error MESSAGE
 * General purpose error display. */
function ycml_show_error($message) {
    page_header(_("Sorry! Something's gone wrong."), array('override'=>true));
    print _('<h2>Sorry!  Something\'s gone wrong.</h2>') .
        "\n" . $message;
    page_footer();
}

function comment_show_one($r, $noabuse = false) {
    if (is_string($r['posted_by_rep']))
        $r['posted_by_rep'] = ($r['posted_by_rep']=='t') ? true : false;
    if (isset($r['date']))
        $ds = prettify($r['date']);
    else
        $ds = '';
    $comment = '<p><a name="comment' . $r['id'] .'"></a>Posted by ';
    if ($r['posted_by_rep']) $comment .= '<strong>';
    if ($r['website'])
        $comment .= '<a href="' . $r['website'] . '">';
    $comment .= $r['name'];
    if ($r['website'])
        $comment .= '</a>';
    $comment .= ', ';
    $comment .= $ds;
    if ($r['posted_by_rep']) $comment .= '</strong>';
    $content = comment_prettify($r['content']);
    $comment .= ':';
    if (!$noabuse)
        $comment .= " <small>(<a href=\"/abuse?id=$r[id]\">Is this post abusive?</a>)</small>";
    /* Permalink to this comment. */
    $comment .= " <a href=\"#comment${r['id']}\" class=\"comment-permalink\" title=\"Link to this comment\">#</a>";
    $comment .= "</p>\n<div><p>$content</p></div>";
    return $comment;
}

function comment_prettify($content) {
    $content = htmlspecialchars($content);
    $content = preg_replace('#\r#', '', $content);
    $content = preg_replace('#\n{2,}#', "</p>\n<p>", $content);
    $content = ms_make_clickable($content);
    $content = str_replace('@', '&#64;', $content);
    return $content;
}

function recent_messages() {
    $q = db_query("SELECT id, subject, area_id, rep_id FROM message
        where state = 'approved' ORDER BY posted DESC LIMIT 5");
    $out = '';
    while ($r = db_fetch_array($q)) {
        if (va_is_fictional_area($r['area_id']) && !OPTION_YCML_STAGING) continue;
        $area_info = ycml_get_area_info($r['area_id']);
        $rep_info = ycml_get_rep_info($r['rep_id']);
        $rep_name = trim("$area_info[rep_prefix] $rep_info[name] $area_info[rep_suffix]");
        $out .= "<li><a href='/view/message/$r[id]'>$r[subject]</a>, by $rep_name, $area_info[name]</li>";
    }
//    if ($out) print '<div class="box"><h2>Latest messages</h2> <ul>' . $out . '</ul></div>';
    if ($out) $out = '<div class="box"><h2>Latest messages</h2> <ul>' . $out . '</ul></div>';
    return $out;
}

function recent_replies() {
  $q = db_query('SELECT comment.id,message,area_id,extract(epoch from date) as date,name
        FROM comment,message,person
        WHERE visible=1 AND comment.message = message.id AND comment.person_id = person.id
        ORDER BY date DESC LIMIT 5');
    $out = '';
    while ($r = db_fetch_array($q)) {
        if (va_is_fictional_area($r['area_id'])) continue;
        $ds = prettify($r['date']);
        $out .= "<li><a href='/view/message/$r[message]#comment$r[id]'>$r[name]</a> at $ds</li>";
    }
//    if ($out) print '<div class="box"><h2>Latest replies</h2> <ul>' . $out . '</ul></div>';
    if ($out) $out = '<div class="box"><h2>Latest replies</h2> <ul>' . $out . '</ul></div>';
    return $out;
}

function postcode_to_constituency_form() {
?>
<form method="get" action="/find_constituency" name="find_constituency_from_postcode" accept-charset="utf-8">
<div id="find_constituency">
<p style="text-align: center;"><a href="/about"><big>Has your MP sent any messages yet?</big></a></p>
    <label for="postcode">UK Postcode:</label>
    <input type="text" name="postcode" id="postcode" value="<?=htmlentities(get_http_var('postcode'))?>" size="10">
    <input type="submit" value="Let's find out">
</div>
</form>
<?
}

# rep_type PLURALIZATION
# Returns the type of representative, by default pluralised if there's
# generally more than one - so returns "MP" and "councillors". 
# Variable is plural to always pluralize, single to always be singular
function rep_type($type = '') {
    global $va_rep_name;
    $rep_type = $va_rep_name[OPTION_AREA_TYPE];
    if ($type == 'plural' || ($type=='' && OPTION_AREA_TYPE != 'WMC'))
        $rep_type .= 's';
    return $rep_type;
} 

function area_type($type = '', $plural = 0) {
    global $va_type_name;
    $area_type = $va_type_name[OPTION_AREA_TYPE];
    if ($type == 'plural' && $plural != 1) {
        if (OPTION_AREA_TYPE == 'WMC') $area_type = 'constituencies';
        else $area_type .= 's';
    }
    return $area_type;
}

