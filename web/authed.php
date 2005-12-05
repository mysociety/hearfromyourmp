<?
// authed.php:
// Returns whether an email address has signed up to HFYMP. Uses shared secret
// for authentication.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: authed.php,v 1.1 2005-12-05 20:57:34 francis Exp $

require_once '../phplib/ycml.php';
require_once '../../phplib/auth.php';

header("Content-Type: text/plain");

$authed = auth_verify_with_shared_secret(get_http_var('email'), OPTION_AUTH_SHARED_SECRET, get_http_var('sign'));
if ($authed) {
    $already_signed = db_getOne("select constituent.id from constituent, person 
        where person.id = constituent.person_id and person.email = ?", array( get_http_var('email') ) );
    if ($already_signed) 
        print "already signed";
    else
        print "not signed";
} else {
    print "not authed";
}

