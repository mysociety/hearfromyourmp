<?php
/*
 * message.php:
 * Confirm an MP's message.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: message.php,v 1.2 2005-11-04 15:20:14 chris Exp $
 * 
 */

importparams(
        array('t',              '/.+$/',            '', null)
        array('post',           '/.+$/',            '', false)
    );

if (is_null($q_t)) {
    header("Location: ./");
    exit();
}

$r = auth_token_retrieve('message', $q_t);

if ($q_post) {
    db_query("update message set state = 'ready' where id = ?", $r['id']);
    page_header("Thanks - the message will now be sent");
    ?>
<p>Thanks. We'll now send that message out to your subscribers.</p>
    <?
    page_footer();
} else {
    page_header("Confirm a message");
    list($subject, $content)
        = db_getRow('select subject, content from message where id = ?', $r['id']);
    ?>
<p>Here is the text of the message, as it will appear to subscribers. Please
check through this and, if all is well, click the "Yes, send it" button at the
bottom of the page:</p>
<div id="message"><h2><?=htmlspecialchars($subject)?></h2>
<blockquote><p>
    <?
    $content = preg_replace('#\r#', '', htmlspecialchars($content));
    $content = preg_replace('#\n{2,}#', "</p>\n<p>", $content);
    $content = make_clickable($content);
    $content = str_replace('@', '&#64;', $content);
    print $content;
    ?>
</p></blockquote>
<form method="POST">
<p>If this message looks OK, then please click
<input type="hidden" name="t" value="<?=htmlspecialchars($q_t)?>">
<input type="submit" name="post" value="Yes, send it">
and we'll send it to your subscibers immediately. Otherwise, please get in
touch with us at
<a href="mailto:<?=htmlspecialchars(OPTION_CONTACT_EMAIL)?>"><?=htmlspecialchars(OPTION_CONTACT_EMAIL)?></a>
and we'll get back to you as soon as possible.</p>
</form>
    <?
    page_footer();
}

?>
