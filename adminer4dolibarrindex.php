<?php
/* Copyright (C) 2025 SuperAdmin
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       adminer4dolibarr/adminer4dolibarrindex.php
 *	\ingroup    adminer4dolibarr
 *	\brief      Adminer Database Manager for Dolibarr - Secure wrapper with auto-login
 */

// Adminer serves its own static assets through ?file=... and exits immediately.
// Serve these before loading Dolibarr to avoid session locks and bootstrap overhead
// on parallel browser requests.
$adminer_asset_files = array('default.css', 'dark.css', 'functions.js', 'jush.js', 'logo.png');
if (isset($_GET['file']) && in_array($_GET['file'], $adminer_asset_files, true)) {
	include __DIR__ . '/adminer-5.4.2.php';
	exit;
}

// Start output buffering to isolate Adminer's output from Dolibarr
ob_start();

// CRITICAL: Disable as much Dolibarr overhead as possible
// Adminer is a database manager - Dolibarr headers, menus, JS, etc. are not needed.
// This drastically reduces the number of input variables processed (fixes max_input_vars errors).
// Safe because: 1) Admin-only access (checked below) 2) Adminer has its own security
define('NOCSRFCHECK', 1);
define('NOTOKENRENEWAL', 1);
define('NOREQUIRETRAN', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);
define('NOREQUIREAJAX', 1);
define('NOREQUIRESOC', 1);
define('NOSCANGETFORINJECTION', 1);
define('NOSCANPOSTFORINJECTION', 1);
define('NOBROWSERNOTIF', 1);
define('NOIPCHECK', 1);

// Increase PHP limits for Adminer operations
// Note: max_input_vars CANNOT be set via ini_set() - must use .htaccess or .user.ini
@ini_set('max_input_nesting_level', '128');
@ini_set('max_execution_time', '300');
@ini_set('memory_limit', '256M');
@ini_set('post_max_size', '128M');
@ini_set('upload_max_filesize', '128M');

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}


/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var User $user
 */

// Note: Translation files NOT loaded due to NOREQUIRETRAN=1
// Adminer has its own translation system, we don't need Dolibarr translations

// Security check - Check if user has permission
// Either user is admin OR full SQL access has been explicitly granted to non-admin
// users via configuration. WARNING: that option gives DROP/UPDATE on the whole base.
if (empty($user->admin) && !getDolGlobalInt('ADMINER4DOLIBARR_GRANT_FULL_SQL_ACCESS_TO_NON_ADMIN')) {
	accessforbidden('Administrator access required (or grant full SQL access to non-admin users in module configuration)');
}

if (!isModEnabled('adminer4dolibarr')) {
	accessforbidden('Module not enabled');
}

// NOTE on sessions: Adminer reuses Dolibarr's already-started PHP session to store
// its own CSRF token and connection state under $_SESSION. We intentionally do NOT
// start a separate session here: main.inc.php has already called session_start(), so
// switching session.save_path / session.name at this point would create a brand new
// empty session on every request. That breaks Adminer's CSRF handshake (the token
// shown in the login form never matches the one validated on submit), producing an
// infinite "Invalid CSRF token" redirect loop. Sharing Dolibarr's session is safe
// here because access is already restricted to admins (or explicitly granted users).

// Aggressively clean up ALL Dolibarr-specific GET/POST/SESSION parameters
// This is the key fix for max_input_vars errors: Dolibarr injects many hidden
// parameters (menus, tokens, action, etc.) that accumulate with Adminer's own
// form fields (especially on tables with many columns), exceeding the PHP limit.
$dolibarr_params = array(
	'idmenu', 'mainmenu', 'leftmenu', 'topmenu',
	'modulepart', 'backtopage', 'backtolist', 'backtopageforcancel',
	'optioncss', 'dol_use_jmobile', 'dol_no_mouse_hover',
	'dol_hide_topmenu', 'dol_hide_leftmenu', 'dol_openinpopup',
);
foreach ($dolibarr_params as $param) {
	unset($_GET[$param]);
	unset($_POST[$param]);
}

// Create the Adminer session token to avoid PHP warnings
// Based on working dbadmin module pattern
$zd = isset($_SESSION["token"]) ? $_SESSION["token"] : null;
if (!is_numeric($zd)) {
	$_SESSION["token"] = rand(1, 1e6);
}

// Map the Dolibarr database type to the matching Adminer driver.
// Adminer uses "server" as the driver key for MySQL/MariaDB, "pgsql" for PostgreSQL, etc.
$adminer_driver = 'server';
switch ($dolibarr_main_db_type) {
	case 'mysql':
	case 'mysqli':
		$adminer_driver = 'server';
		break;
	case 'pgsql':
		$adminer_driver = 'pgsql';
		break;
	case 'sqlite3':
		$adminer_driver = 'sqlite';
		break;
}

// Seed Adminer's login state directly instead of posting auth credentials to Adminer.
// Adminer calls session_regenerate_id() whenever $_POST["auth"] is present. In a real
// browser, the main page and the ?file= asset requests are concurrent, so that
// regeneration can race with asset loading and cause redirect loops/aborted assets.
// The session keys below match Adminer's own set_password()/login marker layout.
$is_asset_request = isset($_GET['file']); // Adminer serves its own css/js/img via ?file=
if (!$is_asset_request && empty($_POST['logout'])) {
	$_GET[$adminer_driver] = isset($_GET[$adminer_driver]) ? $_GET[$adminer_driver] : "";
	$_GET["username"] = "";
	$_SESSION["pwds"][$adminer_driver][""][""] = "";
	$_SESSION["db"][$adminer_driver][""][""][""] = true;
}


/*
 * View - Load Adminer with pre-filled Dolibarr credentials
 */

// Check if adminer file exists
$adminer_file = __DIR__ . '/adminer-5.4.2.php';
if (!file_exists($adminer_file)) {
	// Simple error message (can't use llxHeader/llxFooter due to NOREQUIREHTML)
	die('<html><body><h1>Error</h1><p>Adminer file not found: adminer-5.4.2.php</p><p>Please download Adminer 5.4.2 and place it in the module directory.</p></body></html>');
}

// Store Dolibarr credentials in global scope so they're accessible in adminer_object()
$GLOBALS['dolibarr_db_config'] = array(
	'host' => $dolibarr_main_db_host,
	'user' => $dolibarr_main_db_user,
	'pass' => $dolibarr_main_db_pass,
	'name' => $dolibarr_main_db_name,
	'type' => $dolibarr_main_db_type,
	'port' => !empty($dolibarr_main_db_port) ? $dolibarr_main_db_port : ''
);

/**
 * Adminer plugin loader function
 *
 * IMPORTANT: This function must be defined BEFORE including adminer-5.4.2.php
 * Adminer will call this function after loading its base classes, allowing us
 * to return a customized Adminer instance with auto-login functionality.
 *
 * This uses Adminer 5.x's built-in Plugins system instead of the old AdminerPlugin wrapper.
 *
 * @return Adminer\Plugins Adminer instance with Dolibarr auto-login plugin
 */
function adminer_object()
{
	/**
	 * AdminerDolibarr - Auto-login plugin for Dolibarr integration
	 *
	 * Extends Adminer\Plugin to integrate with Adminer 5.x's plugin system.
	 * Provides automatic authentication using Dolibarr's database credentials.
	 *
	 * Based on the working dbadmin module pattern
	 */
	class AdminerDolibarr extends Adminer\Plugin
	{
		/**
		 * Provide database credentials from Dolibarr configuration
		 *
		 * @return array [server, username, password]
		 */
		function credentials()
		{
			$config = $GLOBALS['dolibarr_db_config'];

			// Handle port in server address if specified
			$server = $config['host'];
			if (!empty($config['port'])) {
				$server .= ':' . $config['port'];
			}

			return array($server, $config['user'], $config['pass']);
		}

		/**
		 * Auto-select Dolibarr database
		 *
		 * @return string Database name
		 */
		function database()
		{
			return $GLOBALS['dolibarr_db_config']['name'];
		}

		/**
		 * Validate login (always allow since Dolibarr already authenticated)
		 *
		 * @param string $login    Database username
		 * @param string $password Database password
		 * @return bool Always true (authentication handled by Dolibarr)
		 */
		function login($login, $password)
		{
			// User is already authenticated by Dolibarr (admin check above)
			// So we allow the database login
			return true;
		}

	}

	// Return Adminer Plugins instance with our Dolibarr auto-login plugin
	// The Plugins class uses reflection to intercept and forward method calls to our plugin
	return new Adminer\Plugins(array(new AdminerDolibarr()));
}

// Info box for large file uploads - Removed to prevent errors
// Display this in the module config page instead
// print '<div class="info">';
// print '<strong>' . $langs->trans('Adminer4DolibarrUploadTitle') . '</strong><br>';
// print $langs->trans('Adminer4DolibarrUploadNotice', DOL_DATA_ROOT . '/adminer4dolibarr/temp');
// print '</div><br>';

// Adminer ends its rendering with exit()/die() in most code paths, so any cleanup
// placed AFTER the include below would never run. Register it as a shutdown function
// to flush the output buffer in all cases.
//
// We deliberately do NOT close $db here: Adminer connects with the same MySQL
// credentials and closes the underlying mysqli link itself at the end of its run.
// Calling DoliDBMysqli->close() afterwards throws "mysqli object is already closed".
// PHP closes the connection on shutdown anyway, so there is nothing left to do.
register_shutdown_function(function () {
	while (ob_get_level() > 0) {
		@ob_end_flush();
	}
});

// Include Adminer directly (based on dbadmin module pattern)
// No need to inject Dolibarr CSRF tokens since:
// 1. We disabled Dolibarr's CSRF check with NOCSRFCHECK
// 2. Adminer has its own CSRF protection
// 3. Injecting extra tokens can exceed max_input_vars and break Adminer's forms
include $adminer_file;
