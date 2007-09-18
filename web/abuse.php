<?
// abuse.php
// Abuse reporting page for HearFromYourMP.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: abuse.php,v 1.3 2007-09-18 12:58:31 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/fns.php';
require_once '../../phplib/importparams.php';
require_once '../../phplib/utility.php';

page_header('Report Abuse');
report_abusive_thing();
page_footer();

/* report_abusive_thing
 * Reporting of abusive comments. */
function report_abusive_thing() {
    global $q_id, $q_reason, $q_email;
    global $q_h_id, $q_h_reason;
    $errors = importparams(
                array('id',         '/^[0-9a-f]{8}$/',              ''),
                array('reason',     '//',                           '', null),
                array('email',      '//',                           '', null)
            );
    if (!is_null($errors)) {
        print '<p>A required parameter was missing. ' . join(" ",$errors);
        return;
    }

    /* Find information about the associated comment. */
    $more = '';
        $message_id = db_getOne('select message from comment where id = ?', $q_id);
        $more = "\nAuthor: " . db_getOne('select name from comment,person where comment.person_id=person.id AND comment.id = ?', $q_id)
                . "\nText: " . db_getOne('select content from comment where id = ?', $q_id); /* XXX */

    if (is_null($message_id)) {
        print '<h2>Report Abuse</h2>';
        print "The comment couldn't be found.  It has probably been deleted already.";
        return;
    }

    if (!is_null($q_reason)) {
        $ip = $_SERVER["REMOTE_ADDR"];
        db_query('insert into abusereport (comment_id, reason, ipaddr, email) values (?, ?, ?, ?)', 
            array($q_id, $q_reason, $ip, $q_email));
        db_commit();
        $admin_url = OPTION_ADMIN_URL . "/?page=ycmlabusereports";
        ycml_send_email(OPTION_CONTACT_EMAIL, $_SERVER['site_name'] . " abuse report", _(<<<EOF
New abuse report for comment id $q_id from IP $ip, email $q_email

$more

$admin_url

Reason given: $q_reason
EOF
));
        print '<p><strong>Thank you!</strong> One of our team will investigate that comment as soon as possible.</p>';
        print '<p><a href="./">Return to the home page</a>.</p>';
        return;
    }

    $subject = htmlspecialchars(db_getOne('select subject from message where id = ?', $message_id));

    print '<form accept-charset="utf-8" action="abuse" method="post" name="abuse" class="pledge">';
    print '<h2>Report something wrong with a comment</h2>';
    print '<p>You are reporting the following comment as being abusive, suspicious or having something wrong with it.</p>';
    print '<blockquote>';
    print comment_show_one(db_getRow('select *,extract(epoch from date) as date,name,email,website from comment,person where comment.person_id=person.id AND comment.id = ?', $q_id), true);
    print '</blockquote>';
    printf("<p>This is on the message <strong>%s</strong>.</p>", $subject);

    print <<<EOF
<input type="hidden" name="abusive" value="1">
<input type="hidden" name="id" value="$q_h_id">
EOF;

    /* XXX we should add a drop-down for category of abuse, to drive home the
     * point that this is an *abuse* report. */

    print '<p>';
    printf(_('Please give a short reason for reporting this comment.'));
    print '<br><input type="text" name="reason" size="60"></p>
<p><input name="submit" type="submit" value="' . _('Submit') . '"><br>';
    printf(_('If you would like us to get back to you about your abuse report, please give your email address.'));
    print '<br><input type="text" name="email" size="60"></p>
</form>';

}

