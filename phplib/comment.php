<?
/*
 * comment.php:
 * Comment related functions
 * 
 * Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org; WWW: http://www.mysociety.org
 *
 * $Id: comment.php,v 1.1 2007-10-31 17:15:52 matthew Exp $
 * 
 */

function comment_show($cc, $first, $last) {
    #global $q_message;
    $html = '';
    for ($i = $first; $i <= $last; ++$i) {
        $r = $cc[$i];
        $r['posted_by_rep'] = ($r['posted_by_rep']=='t') ? true : false;
        $html .= '<li';
        if ($r['posted_by_rep']) $html .= ' class="by_rep"';
        $html .= '>' . comment_show_one($r);
/*      XXX COMMENTED OUT AS NO THREADING TO START
        $html .= '<a href="view?mode=post;article=$q_message;replyid=$id">Reply to this</a>.';
        # Consider whether the following comments are replies to this comment.
        $R = "$refs,$id";
        for ($j = $i + 1; $j <= $last && preg_match("/^$R(,|$)/", $cc[$j][1]); ++$j) {}
        --$j;
        if ($j > $i)
            $html .= "<ul>" . comment_show($cc, $i + 1, $j) . "</ul>";
        $i = $j;
*/
        $html .= "</li>";
    }
    return $html;
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

