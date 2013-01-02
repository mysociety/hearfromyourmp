<?
// contact.php:
// Contact us form for YCML.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: contact.php,v 1.9 2009-04-02 15:06:26 matthew Exp $

require_once '../phplib/ycml.php';
require_once '../phplib/fns.php';
require_once '../phplib/comment.php';
require_once '../commonlib/phplib/utility.php';
require_once '../commonlib/phplib/person.php';

page_header("Contact Us");

if (get_http_var('contactpost')) {
    contact_form_submitted();
} else {
    contact_form();
}

page_footer();

function contact_form($errors = array()) {
    $name = get_http_var('name');
    $email = get_http_var('email');
    $P = person_if_signed_on();
    if (!is_null($P)) {
        if (is_null($name) || !$name)
            $name = $P->name_or_blank();
        if (is_null($email) || !$email)
            $email = $P->email();
    }
 
    print '<h2>Contact Us</h2>';
    $contact_email = str_replace('@', '&#64;', OPTION_CONTACT_EMAIL);
    print "<p>If you would like to comment on a message from a representative,
please use the &lsquo;comments&rsquo; section on the appropriate page. These
messages go to the " . $_SERVER['site_name'] . " Team, <strong>not
your representative</strong>. To contact your MP or other representative, please visit
<a href='http://www.writetothem.com/'>WriteToThem</a>.</p>";
    print '<p>Do <a href="/about">read the FAQ</a> first, it might be a quicker way to answer your question. ';
    printf('If you prefer, you can email %s instead of using the form.</p>', '<a href="mailto:' . $contact_email . '">' . $contact_email . '</a>');
    if (sizeof($errors)) {
        print '<ul id="errors"><li>';
        print join ('</li><li>', $errors);
        print '</li></ul>';
    } ?>
<form name="contact" accept-charset="utf-8" action="/contact" method="post"><input type="hidden" name="contactpost" value="1">
<div class="fr">Message to: <strong><?=$_SERVER['site_name'] ?> Team</strong></div>
<div class="fr"><label for="name">Your name:</label> <input type="text" id="name" name="name" value="<?=htmlentities($name) ?>" size="32"></div>
<div class="fr"><label for="email">Your email:</label> <input type="text" id="email" name="email" value="<?=htmlentities($email) ?>" size="32"></div>
<div class="fr"><label for="subject">Subject:</label> <input type="text" id="subject" name="subject" value="<?=htmlentities(get_http_var('subject')) ?>" size="50"></div>
<div class="fr">Is your message for:
<input type="radio" name="dest" value="us" id="dest_us">
<label class="inline_label" for="dest_us">the HearFromYourMP team</label>
<input type="radio" name="dest" value="mp" id="dest_mp">
<label class="inline_label" for="dest_mp">your MP</label>
<input type="radio" name="dest" value="other" id="dest_other">
<label class="inline_label" for="dest_other">someone else</label> ?
</div>
<div><label for="cf_message">Message:</label><textarea rows="7" cols="60" name="message" id="cf_message"><?=htmlentities(get_http_var('message')) ?></textarea></div>
<?  print '<p><input type="submit" name="submit" value="' . _('Send') . '"></p>';
    print '</form>';
}

function contact_form_submitted() {
    $name = get_http_var('name');
    $email = get_http_var('email');
    $subject = get_http_var('subject');
    $message = get_http_var('message');
    $dest = get_http_var('dest');
    $errors = array();
    if (!$name) $errors[] = _('Please enter your name');
    if (!$email) $errors[] = _('Please enter your email address');
    if (!validate_email($email)) $errors[] = _('Please enter a valid email address');
    if (!$subject) $errors[] = _('Please enter a subject');
    if (!$message) $errors[] = _('Please enter your message');
    if (!$dest) $errors[] = _('Please say who your message is for');
    if (sizeof($errors)) {
        contact_form($errors);
    } elseif ($dest == 'mp') {
	print _('You cannot contact your MP by filling in the HearFromYourMP
contact form. To contact your MP, please visit
<a href="http://www.writetothem.com/">www.writetothem.com</a>. We have printed
your message below so you can copy and paste it into the WriteToThem message box.');
        print '<hr><p>' . comment_prettify($message) . '</p>';
    } elseif ($dest == 'other') {
	print 'You can only contact the team behind HearFromYourMP using our contact form.
Your message is printed below so you can copy and paste it to wherever you want to send it.';
        print '<hr><p>' . comment_prettify($message) . '</p>';
    } else {
        send_contact_form($name, $email, $subject, $message);
    }
}

function send_contact_form($name, $email, $subject, $message) {
    /* User mail must be submitted with \n line endings. */
    $message = str_replace("\r\n", "\n", $message);

    $postfix = '[ Sent by contact.php from IP address ' . $_SERVER['REMOTE_ADDR'] . (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) ? ' (forwarded from '.$_SERVER['HTTP_X_FORWARDED_FOR'].')' : '') . ' ]';
    $headers = array();
    $headers['From'] = array($email, $name);
    if (!preg_match('#\[url#i', $message))
        $success = ycml_send_email(OPTION_CONTACT_EMAIL, $subject, $message . "\n\n-- \n" . $postfix, $headers);
    else
        $success = true;
    if (!$success) {
        $contact_email = str_replace('@', '&#64;', OPTION_CONTACT_EMAIL);
        err(_("Failed to send message.  Please try again, or <a href=\"mailto:" . $contact_email . '">email us</a>.'));
    }
    print _('Thanks for your feedback.  We\'ll get back to you as soon as we can!');
}

