# Changelog

All notable changes to the **ZohoMailer for Perfex CRM** module will be documented here.  
This project adheres to [Semantic Versioning](https://semver.org/).

---

## [1.2.1] - 2025-09-04
### Fixed
- Toggle state now properly disabled when Zoho integration is not enabled.
- Minor refactoring and code cleanup in mailer library and helpers.

---

## [1.2.0] - 2025-08-30
### Added
- Redesigned module header with **logo (above)** and **title + version/author (below)**.
- ZohoMailer settings page integrated under **Setup → ZohoMailer Settings**.
- Enhanced UI layout for settings form.

---

## [1.1.0] - 2025-08-24
### Added
- Full **attachment support** when sending emails via Zoho API.
- Fallback toggle (`zoho_fallback`) in settings to use Perfex’s default mail if Zoho fails.
- Logging system (`zohomailer/logs/`) with error log file.
- Basic JS and CSS assets for UI improvements.

### Changed
- Cleaned unused/commented code from mail attachment handling.
- Improved overall error handling in mailer class.

---

## [1.0.0] - 2025-08-15
### Added
- Initial release of **ZohoMailer module** for Perfex CRM.
- Integration with Zoho Mail API for sending emails.
- Settings page for configuring Zoho Client ID, Secret, and Token.
- Core module structure (`controllers`, `models`, `libraries`, `helpers`, `views`, `assets`).
