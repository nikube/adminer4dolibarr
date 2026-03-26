# CHANGELOG MODULE ADMINER4DOLIBARR FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## 0.3

- Upgraded Adminer from 5.4.1 to 5.4.2
- Fixed max_input_vars error by stripping Dolibarr parameters (menus, tokens, optioncss, etc.) before Adminer processes forms
- Added NOBROWSERNOTIF and NOIPCHECK defines to reduce Dolibarr overhead
- Removed unnecessary custom token functions (Adminer has its own CSRF protection)

## 0.1

Initial release
- Adminer 5.4.1 integration into Dolibarr
- Automatic authentication with Dolibarr database credentials
- Admin-only access by default
- Optional non-admin access configuration
- Full bilingual support (English & French)
- Simple About page with proper credits
