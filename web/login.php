<?php
/*
 * login.php:
 * Identification and authentication of users.
 * 
 * The important thing here is that we mustn't leak information about whether
 * a given email address has an account or not. That means that, until we have
 * either processed a password, or have had the user click through from an
 * email token, we can't give any indication of whether the user has an account
 * or not.
 * 
 * There are a number of pages here:
 * 
 *  login
 *      Shown when the user doesn't have a cookie and login is needed. Either
 *      solicit a password or allow the user to click a button to get sent an
 *      email with a token in it. Supplied with parameters: stash, the stash
 *      key for the request which should complete once the user has logged in;
 *      email, the user's email address; and optionally name, the user's real
 *      name.
 *
 *  login-error
 *      Shown when the user enters an incorrect password or an unknown email
 *      address on the login page.
 *
 *  create-password
 *      Shown when a user logs in by means of an emailed token and has already
 *      created or signed a pledge, or posted a comment, to ask them to give a
 *      password for future logins.
 *
 *  change-name
 *      Shown when a user logs in but their name is significantly different
 *      from the name shown on their account. Gives them the options of
 *      changing the name recorded, or continuing with the old name.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: chris@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: login.php,v 1.16 2007-11-01 15:05:22 matthew Exp $
 * 
 */

require_once '../phplib/ycml.php';
require_once '../phplib/fns.php';
require_once '../phplib/constituent.php';
require_once '../commonlib/phplib/auth.php';
require_once '../commonlib/phplib/person.php';
require_once '../commonlib/phplib/stash.php';
require_once '../commonlib/phplib/importparams.php';

/* As a first step try to set a cookie and read it on redirect, so that we can
 * warn the user explicitly if they appear to be refusing cookies. */
if (!array_key_exists('test_cookie', $_COOKIE)) {
    if (array_key_exists('test_cookie', $_GET)) {
        page_header("Please enable cookies");
        ?>
<p>It appears that you don't have "cookies" enabled in your browser.
<strong>To continue, you must enable cookies</strong>. Please
read <a href="http://www.google.com/cookies.html">this page from Google
explaining how to do that</a>, and then click the "back" button and
try again</p>
<?
        page_footer();
        exit();
    } else {
        setcookie('test_cookie', '1', 0, '/', person_cookie_domain());
        header("Location: /login?" . $_SERVER['QUERY_STRING'] . "&test_cookie=1\n");
        exit();
    }
}

/* Get all the parameters which we might use. */
importparams(
        array('stash',          '/^[0-9a-f]+$/',    '', null),
        array('email',          '/^[^@]+@[^@]+$/',  '', null),
        array('name',           '//',               '', null),
        array('password',       '/[^\s]/',          '', null),
        array('t',              '/^.+$/',           '', null),
        array('rememberme',     '/./',              '', false),

        /* Buttons on login page. */
        array('LogIn',          '/^.+$/',           '', false),
        array('SendEmail',      '/^.+$/',           '', false),

        array('SetPassword',    '/^.+$/',           '', false),
        array('NoPassword',     '/^.+$/',           '', false),

        /* Buttons on name change page */
        array('KeepName',       '/^.+$/',            '', false),
        array('ChangeName',     '/^.+$/',            '', false)
    );
if ($q_name=='<Enter your name>') {
    $q_name=null;
}

/* General purpose login, asks for email also. */
if (get_http_var("now")) {
    $P = person_signon(array(
                    'reason_web' => "To log into HearFromYourMP, we need to check your email address.",
                    'reason_email' => "Then you will be logged into HearFromYourMP, and can set or change your password.",
                    'reason_email_subject' => 'Log into HearFromYourMP'

                ));
    page_header("Logged in");
    print "You're now logged in as <strong>";
    if ($P->has_name())
        print htmlspecialchars($P->name);
    else 
        print htmlspecialchars($P->email);
    print "</strong>.  Enjoy using HearFromYourMP!";
    page_footer();
    exit;
}

/* Do token case first because if the user isn't logged in *and* has a token
 * (unlikely but possible) the other branch would fail for lack of a stash
 * parameter. */
if (!is_null($q_t)) {
    /* Process emailed token */
    $d = auth_token_retrieve('login', $q_t);
    if (!$d)
        err("Please check the URL (i.e. the long code of letters and numbers) is copied correctly from your email, as the token $q_t was not found.");
    $P = person_get($d['email']);
    if (is_null($P)) {
        if (!$d['name']) {
            page_header('Subscribe to HearFromYourMP');
            print '<div id="errors">You do not appear to be registered. Please sign up!</div>';
            constituent_subscribe_box(array('email'=>$d['email']));
            page_footer();
            exit();
        } else {
            $P = person_get_or_create($d['email'], $d['name']);
        }
    }

    $P->inc_numlogins();
    
    db_commit();

    /* Now give the user their cookie. */
    set_login_cookie($P);

    /* Recover "parameters" from token. */
    $q_h_email = htmlspecialchars($q_email = $d['email']);
    if (array_key_exists('name', $d) && !is_null($d['name'])) {
        $q_h_name = htmlspecialchars($q_name = $d['name']);
    } else {
        $q_h_name = $q_name = null;
    }
    $q_h_stash = htmlspecialchars($q_stash = $d['stash']);

    /* If the 'direct' key exists in the token, don't do any intervening
     * pages. */
    if (!array_key_exists('direct', $d)) {
        if ($q_name && !$P->matches_name($q_name))
            $P->name($q_name);
        /* Can set this to some condition if you don't want to always offer password */
        change_password_page($P);
    }
    stash_redirect($q_stash);
    /* NOTREACHED */
}

$P = person_if_signed_on();
if (!is_null($P)) {
    /* Person is already signed in. */
    if ($q_SetPassword)
        change_password_page($P);
    if ($q_name && !$P->matches_name($q_name))
        /* ... but they have specified a name which differs from their recorded
         * name. Change it. */
        $P->name($q_name);
    if (!is_null($q_stash))
        /* No name change, just pass them through to the page they actually
         * wanted. */
        stash_redirect($q_stash);
    else {
        err('A required parameter was missing');
    }
} elseif (is_null($q_stash)) {
    header("Location: /login?now=1");
} else {
    /* Main login page. */
    login_page();
}

/* login_page
 * Render the login page, or respond to a button pressed on it. */
function login_page() {
    global $q_stash, $q_email, $q_name, $q_LogIn, $q_SendEmail, $q_rememberme;

    if (is_null($q_stash)) {
        err("Required parameter was missing");
    }

    if ($q_LogIn) {
        /* User has tried to log in. */
        if (is_null($q_email)) {
            login_form(array('email'=>'Please enter your email address.'));
            exit();
        }
        if (!validate_email($q_email)) {
            login_form(array('email'=>'Please enter a valid email address'));
            exit();
        }
        global $q_password;
        $P = person_get($q_email);
        if (is_null($P) || !$P->check_password($q_password)) {
            login_form(array('badpass'=>'Either your email or password weren\'t recognised.  Please try again.'));
            exit();
        } else {
            /* User has logged in correctly. Decide whether they are changing
             * their name. */
            set_login_cookie($P, $q_rememberme ? 28 * 24 * 3600 : null);
            if ($q_name && !$P->matches_name($q_name))
                $P->name($q_name);
            $P->inc_numlogins();
            db_commit();
            stash_redirect($q_stash);
            /* NOTREACHED */
        }
    } elseif ($q_SendEmail) {
        /* User has asked to be sent email. */
        if (is_null($q_email)) {
            login_form(array('email'=>'Please enter your email address.'));
            exit();
        }
        if (!validate_email($q_email)) {
            login_form(array('email'=>'Please enter a valid email address'));
            exit();
        }

        $token = auth_token_store('login', array(
                        'email' => $q_email,
                        'name' => $q_name,
                        'stash' => $q_stash,
                        'direct' => 1
                    ));
        db_commit();
        $url = OPTION_BASE_URL . "/L/$token";
        $extra = stash_get_extra($q_stash);
        $template_data = rabx_unserialise($extra);
        $template_data['url'] = $url;
        $template_data['user_name'] = $q_name ? " $q_name" : '';
        $template_data['user_email'] = $q_email;
        $to = $q_name ? array($q_email, $q_name) : $q_email;
        ycml_send_email_template($to,
            array_key_exists('template', $template_data) 
                ?  $template_data['template'] : 'generic-confirm', 
            $template_data);
        page_header("Now check your email");
    ?>
<p id="loudmessage">
Now check your email!<br>
We've sent you an email, and you'll need to click the link in it before you can
continue
</p>
<?

        page_footer(array('nonav' => 1));
        exit();
        /* NOTREACHED */
    } else {
        login_form();
        exit();
    }
}

/* login_form ERRORS
 * Print the login form. ERRORS is a list of errors encountered when the form
 * was processed. */
function login_form($errors = array()) {
    /* Just render the form. */
    global $q_h_stash, $q_h_email, $q_h_name, $q_stash, $q_email, $q_name;

    page_header('Checking your email address');

    if (is_null($q_name))
        $q_name = $q_h_name = '';   /* shouldn't happen */

    $extra = stash_get_extra($q_stash);
    $template_data = rabx_unserialise($extra);
    $reason = htmlspecialchars($template_data['reason_web']);

    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', array_values($errors));
        print '</li></ul></div>';
    }

    /* Split into two forms to avoid "do you want to remember this
     * password" prompt in, e.g., Mozilla. */
?>

<div class="pledge">
<form action="/login" name="login" class="login" method="POST" accept-charset="utf-8">
<input type="hidden" name="stash" value="<?=$q_h_stash?>">
<input type="hidden" name="name" id="name" value="<?=$q_h_name?>">

<p><strong><?=$reason?></strong></p>

<ul>
<li><p>We'll send an email to that address containing a link that logs you in.
</p></li>

<? if (is_null($q_email) || $errors) { ?>
<li> Enter your email address: <input<? if (array_key_exists('email', $errors) || array_key_exists('badpass', $errors)) print ' class="error"' ?> type="text" size="30" name="email" id="email" value="<?=$q_h_email?>">
<? } else { ?>
<input type="hidden" name="email" value="<?=$q_h_email?>">
<? } ?>

<input type="submit" name="SendEmail" value="Go">

</li>
</ul>

</form>
</div>
<?

    page_footer();
}

/* change_password_page PERSON
 * Show the logged-in PERSON a form to allow them to set or reset a password
 * for their account. */
function change_password_page($P) {
    global $q_stash, $q_email, $q_name, $q_SetPassword, $q_NoPassword;
    global $q_h_stash, $q_h_email, $q_h_name;

    if (is_null($q_name))
        $q_name = $q_h_name = '';   /* shouldn't happen */

    $error = null;
    if ($q_SetPassword) {
        global $q_pw1, $q_pw2;
        importparams(
                array('pw1',        '/[^\s]+/',      '', null),
                array('pw2',        '/[^\s]+/',      '', null)
            );
        if (is_null($q_pw1) || is_null($q_pw2))
            $error = _("Please type your new password twice");
        elseif (strlen($q_pw1)<5 || strlen($q_pw2)<5)
            $error = _('Your password must be at least 5 characters long');
        elseif ($q_pw1 != $q_pw2)
            $error = _("Please type the same password twice");
        else {
            $P->password($q_pw1);
            db_commit();
            return;
        }
    } else if ($q_NoPassword)
        return;

    if ($P->has_password()) {
        page_header('Change your password');
        print <<<EOF
<p>There is a password set for your email address on HearFromYourMP. Perhaps
you've forgotten it? You can set a new password using this form:</p>
EOF;
    } else {
        page_header('Set a password');
        print <<<EOF
<p>On this page you can set a password which you can use to identify yourself
to HearFromYourMP, so that you can post comments in the future without having
to find an email from us first. You don't have to set a password if you don't want to.
</p>
EOF;
    }

    if (!is_null($error))
        print "<div id=\"errors\"><ul><li>$error</li><ul></div>";

    print <<<EOF
<div class="pledge">

<p><strong>Would you like to set a HearFromYourMP password?</strong></p>

<ul>

<li><form action="/login" name="loginNoPassword" class="login" method="POST" accept-charset="utf-8">
<input type="hidden" name="stash" value="$q_h_stash">
<input type="hidden" name="email" value="$q_h_email">
<input type="hidden" name="name" value="$q_h_name">
No, I don't want to think of a password right now.
<input type="submit" name="NoPassword" value="Click here to continue">
<br><small>(you can set a password another time)</small>
</form></li>

<li><form action="/login" name="loginSetPassword" class="login" method="POST" accept-charset="utf-8">
<input type="hidden" name="stash" value="$q_h_stash">
<input type="hidden" name="email" value="$q_h_email">
<input type="hidden" name="name" value="$q_h_name">
<input type="hidden" name="SetPassword" value="1">
Yes, I'd like to set a password, so I don't have to keep going back to my email.
<br>
    <strong>Password:</strong> <input type="password" name="pw1" id="pw1" size="15">
    <strong>Password (again):</strong> <input type="password" name="pw2" size="15">
    <input type="submit" name="SetPassword" value="Set password">
</form>
</li>

</ul>

</div>
EOF;

    page_footer(array('nonav' => 1));
    exit();
}

/* set_login_cookie PERSON [DURATION]
 * Set a login cookie for the given PERSON. If set, EXPIRES is the time which
 * will be set for the cookie to expire; otherwise, a session cookie is set. */
function set_login_cookie($P, $duration = null) {
    // error_log('set cookie');
    setcookie('pb_person_id', person_cookie_token($P->id(), $duration), is_null($duration) ? 0 : time() + $duration, '/', person_cookie_domain());
}

?>
