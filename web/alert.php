<?
// alert.php:
// Ubsigning from alerts.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: alert.php,v 1.1 2005-08-26 15:35:35 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/alert.php';
require_once '../commonlib/phplib/person.php';

page_header('Comment alerts');
if (get_http_var('direct_unsubscribe')) {
    // Clicked from email to unsubscribe
    $alert_id = get_http_var('direct_unsubscribe');
    $P = person_if_signed_on();
    if (!$P) 
        err(_('Unexpectedly not signed on after following unsubscribe link'));
    $desc = alert_h_description($alert_id);
    print '<p>';
    if ($desc) {
        alert_unsubscribe($P->id(), $alert_id);
        printf(_("Thanks!  You won't receive more email about %s."), $desc);
    } else {
        print _("Thanks!  You are already unsubscribed from that alert.");
    }
    print '</p>';
} else {
    err("No alert id given");
}
page_footer();

?>
