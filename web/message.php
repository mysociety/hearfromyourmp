<?php
/*
 * message.php:
 * Confirm an MP's message.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: message.php,v 1.6 2005-11-09 18:37:33 sandpit Exp $
 * 
 */

require_once '../phplib/ycml.php';
require_once '../commonlib/phplib/importparams.php';

importparams(
        array('t',              '/.+$/',            '', null)
    );

if (is_null($q_t)) {
    header("Location: ./");
    exit();
}

$r = auth_token_retrieve('message', $q_t);

db_query("update message set state = 'approved' where id = ?", $r['id']);
db_commit();

page_header("Thanks - the message will now be sent");
?>
<p>Thanks. We'll now send that message out to your subscribers.</p>
<?
page_footer();

?>
