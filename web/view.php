<?
# view.php:
# The meat of YCML
# 
# Things this script has to do:
# * View messages for a particular constituency
# * View a thread for a particular message
# * Deal with posting a comment
# 
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: matthew@mysociety.org. WWW: http://www.mysociety.org
#
# $Id: view.php,v 1.1 2005-07-22 11:54:10 matthew Exp $

require_once '../phplib/ycml.php';
# require_once '../phplib/constituent.php';
# require_once '../../phplib/person.php';
# require_once '../../phplib/utility.php';
require_once '../../phplib/importparams.php';
# require_once '../../phplib/mapit.php';
# require_once '../../phplib/dadem.php';

$constituency_id = get_http_var('c');
$thread_id = get_http_var('t');

page_header('Viewing page, BETTER HEADER HERE');
if ($thread_id) {
    show_comments($thread_id);
    # Show thread for particular message.
} elseif ($constituency_id) {
    # Show list of messages for this particular constituency.
    show_messages($constituency_id);
} else {
    # Main page. Show nothing? Or list of constituencies?
}
page_footer();

function show_messages($c_id) {
    'select *, (select count(*) from comment where comment.message=message.id) as numposts from message';
}

function show_comments($t_id) {
    
}

?>
