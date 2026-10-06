# Adminer Database Manager for Dolibarr

Integrate [Adminer](https://www.adminer.org/) database management tool directly into your Dolibarr installation with automatic authentication and security.

## Features

- **Seamless Integration**: Access Adminer directly from Dolibarr's admin menu
- **Automatic Authentication**: Uses your Dolibarr database credentials automatically
- **Security First**: Admin-only access with Dolibarr's authentication system
- **Simple Setup**: Just enable the module and start managing your database
- **Full Adminer Features**: Complete database management (browse, edit, SQL queries, export, import, etc.)

## What is Adminer?

Adminer (formerly phpMinAdmin) is a full-featured database management tool written in PHP. It's a lightweight alternative to phpMyAdmin with support for MySQL, PostgreSQL, SQLite, MS SQL, Oracle, and more.

## Installation

### Prerequisites

- Dolibarr 16.0 or higher
- PHP 7.1 or higher
- Administrator access to your Dolibarr installation

### Step 1: Install the Module

#### From ZIP file (recommended)

1. Download the module ZIP file: `module_adminer4dolibarr-0.3.zip`
2. In Dolibarr, go to **Home → Setup → Modules → Deploy external module**
3. Upload the ZIP file
4. Click "Deploy"

The module includes Adminer 6.1.1 - no additional downloads required!

#### From source

1. Clone or extract the module to your Dolibarr custom directory:
   ```bash
   cd /path/to/dolibarr/htdocs/custom/
   git clone <repository-url> adminer4dolibarr
   ```

### Step 2: Enable the Module

1. Log into Dolibarr as an administrator
2. Go to **Home → Setup → Modules**
3. Search for "Adminer Database Manager"
4. Click **Activate**

## Usage

Once the module is enabled:

1. Go to **Home** in Dolibarr's main menu
2. In the left sidebar, you'll see **Database Manager (Adminer)**
3. Click on it to access Adminer

The database connection is **automatically configured** with your Dolibarr credentials - no need to enter server, username, password, or database name!

## Security

- **Admin-Only Access**: Only users with administrator privileges can access Adminer
- **Dolibarr Authentication**: Uses Dolibarr's existing security and session management
- **Database Isolation**: Automatically connects to your Dolibarr database only
- **No External Access**: Protected by Dolibarr's login system

## Updating Adminer

This module includes Adminer 6.1.1. To update to a newer version of Adminer:

1. Download the latest version from [adminer.org](https://www.adminer.org/)
2. Replace the existing `adminer-6.1.1.php` file in `/custom/adminer4dolibarr/`
3. Update the filename reference in `adminer4dolibarrindex.php` if the version number changed

## Troubleshooting

### Cannot access the menu

- Ensure you're logged in as an administrator
- Check that the module is activated
- Clear your browser cache and refresh

### Database connection issues

- The module uses your Dolibarr database configuration from `conf/conf.php`
- If connection fails, check your Dolibarr database settings

## Uninstallation

1. Go to **Home → Setup → Modules**
2. Find "Adminer Database Manager"
3. Click **Disable**
4. Optionally, delete the module folder from `/custom/adminer4dolibarr/`

## Support

For issues, questions, or contributions:
- Check the [Dolibarr documentation](https://wiki.dolibarr.org/)
- Visit [Adminer documentation](https://www.adminer.org/)

## License

### Module Code
GPLv3 or (at your option) any later version. See file COPYING for more information.

### Adminer
Adminer is licensed under Apache License 2.0 or GPL 2. See [Adminer's license](https://www.adminer.org/#license) for details.

## Credits

### Adminer Database Tool
- **Author:** Jakub Vrána
- **Website:** [https://www.adminer.org/](https://www.adminer.org/)
- **License:** Apache License 2.0 / GPL v2
- **Version included:** 6.1.1

### Dolibarr Integration
- **Developed by:** Anatole Conseil (nz@anatoleconseil.com)
- **License:** GPL v3+
- **Built with:** [Dolibarr Module Builder](https://wiki.dolibarr.org/index.php/Module_builder)

## Changelog

### Version 0.8
- Upgraded bundled Adminer from 6.1.0 to 6.1.1

### Version 0.7
- Fixed "Invalid CSRF token" appearing after visiting any other Dolibarr page (session key collision with Dolibarr's own token)
- Adminer opens directly on the Dolibarr database
- Upgraded bundled Adminer from 6.0.2 to 6.1.0

### Version 0.6
- Upgraded bundled Adminer from 6.0.0 to 6.0.2

### Version 0.5
- Upgraded bundled Adminer from 5.4.2 to 6.0.0

### Version 0.4
- Security: renamed `ADMINER4DOLIBARR_ALLOW_NON_ADMIN` to `ADMINER4DOLIBARR_GRANT_FULL_SQL_ACCESS_TO_NON_ADMIN` with explicit warning
- Fixed browser redirect loop (auto-login now seeds Adminer session state instead of posting auth)
- Driver mapping for MySQL/MariaDB, PostgreSQL, SQLite

### Version 0.3
- Upgraded Adminer from 5.4.1 to 5.4.2
- Fixed max_input_vars error (stripped Dolibarr parameters before Adminer form processing)
- Reduced Dolibarr overhead with additional NOREQUIRE defines

### Version 0.1 (Initial Release)
- Initial integration of Adminer into Dolibarr
- Automatic database authentication
- Admin menu integration
- Security restrictions (admin-only access)
