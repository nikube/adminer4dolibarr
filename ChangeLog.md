# CHANGELOG MODULE ADMINER4DOLIBARR FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## 0.4

- SECURITY: renamed the non-admin option `ADMINER4DOLIBARR_ALLOW_NON_ADMIN` to `ADMINER4DOLIBARR_GRANT_FULL_SQL_ACCESS_TO_NON_ADMIN` with an explicit DANGER warning, to make clear it grants full read/write/DROP access to the whole database. **Breaking:** re-enable the option after upgrade if you relied on it.
- Fixed PHP "Undefined array key" warning on first Adminer access (`$_SESSION["db"]...` checked with `empty()`)
- Mapped the Dolibarr database type to the matching Adminer driver (MySQL/MariaDB, PostgreSQL, SQLite) instead of hardcoding "server"
- Ensured the output buffer is flushed even when Adminer exits early (shutdown function). The Dolibarr DB connection is no longer explicitly closed: Adminer shares and closes the same mysqli link, so closing it again raised "mysqli object is already closed".
- Documented why Adminer reuses Dolibarr's PHP session (a separate session breaks Adminer's CSRF handshake → infinite redirect loop)
- FIXED infinite redirect loop / blank page in the browser (assets failing with NS_BINDING_ABORTED). Adminer calls session_regenerate_id() whenever $_POST["auth"] is set; the wrapper was re-injecting auth on *every* request, so the session id kept changing, the login never stuck, and the page redirected to itself forever. Auto-login now seeds Adminer's session state directly and never posts auth credentials to Adminer during normal page load.
- Serve Adminer's static `?file=` assets before bootstrapping Dolibarr, avoiding session locks and slow/aborted parallel browser asset requests.
- Removed leftover ModuleBuilder "MyObject" scaffolding (numbering models, document templates, specimen action) from the setup page and module descriptor
- Removed duplicated admin access check in setup page; minor descriptor cleanup (picto, duplicate editor_url)

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
