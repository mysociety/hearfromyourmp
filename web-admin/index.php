<?
/*
 * index.php:
 * Admin pages for PledgeBank.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: francis@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: index.php,v 1.7 2006-01-06 12:23:45 matthew Exp $
 * 
 */

require_once "../conf/general";
require_once "../phplib/admin-ycml.php";
require_once "../../phplib/template.php";
require_once "../../phplib/admin-phpinfo.php";
require_once "../../phplib/admin-serverinfo.php";
require_once "../../phplib/admin-configinfo.php";
require_once "../../phplib/admin.php";

$pages = array(
    new ADMIN_PAGE_YCML_LATEST,
    new ADMIN_PAGE_YCML_MAIN,
    new ADMIN_PAGE_YCML_SUBSCRIBERS,
    new ADMIN_PAGE_YCML_MULTIPLE,
    new ADMIN_PAGE_YCML_ABUSEREPORTS,
    null, // space separator on menu
    new ADMIN_PAGE_SERVERINFO,
    new ADMIN_PAGE_CONFIGINFO,
    new ADMIN_PAGE_PHPINFO,
);

admin_page_display(str_replace("http://", "", OPTION_BASE_URL . "/"), $pages, new ADMIN_PAGE_YCML_SUMMARY);

?>
