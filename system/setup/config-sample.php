<?php
session_start();

/* DATABASE *****************************************/
$dbname = '<%dbname%>';
$dbuser = '<%dbuser%>';
$dbpass = '<%dbpass%>';
$dbhost = '<%dbhost%>';
$dbcharset = 'utf8';
$timezone = '+02:00';
$prefix = "<%prefix%>";

require_once "system/classes/DB.php";
/****************************************************/

require_once "system/includes/options.php";

if(get_option("admin_SiteDebugMode") == "debugmode")
{
	ini_set("display_startup_errors", true);
	error_reporting(E_ALL ^ E_NOTICE);
	$add_modules_menu = true; // Menüye modules sayfasının eklenip eklenmemesini belirler
}
else
{
	ini_set("display_startup_errors", false);
	error_reporting(0);
	$add_modules_menu = false; // Menüye modules sayfasının eklenip eklenmemesini belirler
}

require_once dirname(__FILE__) . "/system/view_engine/View.php";
if(in_admin)
	loadView("nofolder");

/* SECURITY */
$secureKey = '<%securekey%>';
$sessionKeysPrefix = "<%sessionKeysPrefix%>";
/***************************************************/