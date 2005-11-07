<?php
/*
 * message.php:
 * Confirm an MP's message.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: message.php,v 1.3 2005-11-07 16:11:02 sandpit Exp $
 * 
 */

require_once '../phplib/ycml.php';
require_once '../../phplib/importparams.php';

importparams(
        array('t',              '/.+$/',            '', null),
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
        = db_getRow_list('select subject, content from message where id = ?', $r['id']);
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
</div>
<form method="POST">
<p>If this message looks OK, then please click
<input type="hidden" name="t" value="<?=htmlspecialchars($q_t)?>">
<input type="submit" name="post" value="Yes, send it">
and we'll send it to your subscibers immediately. Otherwise, please get in
touch with us at
<a href="mailto:<?=htmlspecialchars(OPTION_CONTACT_EMAIL)?>"><?=htmlspecialchars(OPTION_CONTACT_EMAIL)?></a>
and we'll get back to you as soon as possible.</p>
<p><strong>You must confirm your message for it to be sent to constituents. If you do not confirm the message it will not be sent.</strong> Please mail us at <a href="mailto:team@hearfromyourmp.com">team@hearfromyourmp.com</a> if you have any questions or anything is wrong with the appearance of the message.</p>
</form>
    <?
    page_footer();
}

?>
