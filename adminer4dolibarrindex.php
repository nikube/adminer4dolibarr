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
// Either user is admin OR non-admin access is enabled via configuration
if (empty($user->admin) && !getDolGlobalInt('ADMINER4DOLIBARR_ALLOW_NON_ADMIN')) {
	accessforbidden('Administrator access required (or enable non-admin access in module configuration)');
}

if (!isModEnabled('adminer4dolibarr')) {
	accessforbidden('Module not enabled');
}

// Fix session path for Adminer's CSRF protection
// Adminer needs a writable session path to store CSRF tokens
$session_path = DOL_DATA_ROOT . '/adminer4dolibarr/sessions';
if (!is_dir($session_path)) {
	@mkdir($session_path, 0700, true);
}
if (is_dir($session_path) && is_writable($session_path)) {
	@ini_set('session.save_path', $session_path);
}

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

// Trigger auto-login ONLY on first access (when not yet logged in to Adminer)
$_GET["username"] = "";
if ($_SESSION["db"]["server"][""][""][""] != true) {
	// Not logged in yet - trigger auto-login
	$_POST["auth"] = array(
		"driver" => "server",
		"server" => "",
		"username" => "",
		"password" => "",
		"db" => ""
	);
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

// Include Adminer directly (based on dbadmin module pattern)
// No need to inject Dolibarr CSRF tokens since:
// 1. We disabled Dolibarr's CSRF check with NOCSRFCHECK
// 2. Adminer has its own CSRF protection
// 3. Injecting extra tokens can exceed max_input_vars and break Adminer's forms
include $adminer_file;

// Flush output buffer and close database connection
ob_end_flush();
$db->close();
