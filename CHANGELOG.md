# Changelog — WM K9 BH Exam Planner

All notable changes to **WM K9 BH Exam Planner** are documented in this file in adherence to [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) and [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Fixed
- Switched GenerateBlocks 2.x Dynamic Tag filtering from `generateblocks_dynamic_tag_output` to `generateblocks_dynamic_tag_replacement` with `mixed` safety.
- Standardized WM Suite Hub spoke registration filter to `wm_register_suite_module` across code and docs (including 24 translated READMEs), added `WM_K9_Admin_Dashboard::render_page()` for Hub menu dispatch, added `Tested up to: 7.1` in main PHP header, and synchronized `bom.json`.

## [1.2.2] - 2026-11-21

### Changed

- The bundled family stylesheet is identical to WM Suite Hub 1.2.5 again: it starts the admin header below WordPress's help toggle instead of shortening it. This plugin's own screens register no help tab, so they change only where another plugin adds one.

## [1.2.1] - 2026-11-21

### Removed

- `uninstall.php` deleted the option `wm_k9_planner_settings` and the transient `wm_k9_planner_cache`, which no version of the plugin ever wrote. With the opt-in enabled it now deletes the one option the plugin stores, `wm_k9_delete_data_on_uninstall`; the test suite checks that every name it deletes is one the plugin writes.

### Fixed

- The 29 catalogs dropped one entry the code no longer translates and fill the plugin and author URI headers they left empty.

## [1.2.0] - 2026-11-20

### Changed
- The WM Suite Hub contract now lives in the `ArnoldWender\SuiteHub\Contracts` namespace. The spoke adapter registers only with WM Suite Hub 1.2.0 or later, so update the hub together with this plugin; used on its own, the plugin works as before.
- The `.wordpress-org/` banners show the current version.

### Removed
- The archived pre-2026-11-19 marketing visuals in `versionen/`: they claimed features and conformity the plugin does not have. They were never part of the installable package.

## [1.1.3] - 2026-11-19

- Removed the agency name, links to unrelated projects and the personal mailbox from code, catalogs and documentation; the admin family badge and the hub menu read "WM Suite". The READMEs say the plugin was built with an agentic workflow (AI coding agents) and should be reviewed like any third-party code.

- Added root `SUPPORT.md`, `.github/ISSUE_TEMPLATE/{config.yml,bug_report.yml}`, and explicit zero-support / "AS IS" liability notices across `README.md`, `README.de.md`, `README.es.md`, `readme.txt`, `SECURITY.md`, and `llms.txt`.
- Migrated plugin author/catalog/repository metadata to personal open-source distribution (`Arnold Wender`, `https://arnoldwender.com/wordpress-plugins/wm-k9-bh-exam-planner/`, `https://github.com/arnoldwender/wm-wp-k9-bh-exam-planner`), normalized FSF GPL-2.0 `LICENSE` metadata where applicable, and updated `.wordpress-org/` banner branding to `ARNOLD WENDER · OPEN SOURCE`.

## [Unreleased]

## [1.1.2] - 2026-11-19

### Added
- `.wordpress-org/` visual kit (`banner-1544x500.png`, `banner-772x250.png`, `icon-256x256.png`, `icon-128x128.png`, `icon.svg`) and live Playwright screenshots (`screenshot-1.png` through `screenshot-2.png`) wired into `README.md`, `README.de.md`, `README.es.md`, and `readme.txt`.
- Canonical catalog URL `https://arnoldwender.com/wordpress-plugins/wm-k9-bh-exam-planner/` across root documentation and `llms.txt`.

### Changed
- Archived superseded legacy banners and mockups from `assets/images/` and `docs/screenshots/` under `versionen/visuals-pre-2026-11-19/` with `SHA256SUMS` and `MOTIVO.txt` (kept `docs/screenshots/design-family/` intact).
- Synchronized release metadata, `bom.json`, translations, and test suite at `v1.1.2`.

---

## [1.1.1] - 2026-11-19

### Changed
- Point `Plugin URI` to `https://arnoldwender.com/wordpress-plugins/wm-k9-bh-exam-planner/` and align release metadata at `v1.1.1`.

---

## [1.1.0] - 2026-11-16

### Added
- **Internationalization (`load_plugin_textdomain` & 29 compiled locale catalogs):** Added `wm_k9_bootstrap_i18n()` on `init` calling `load_plugin_textdomain( 'wm-k9-bh-exam-planner', false, ... )` and translated all 43 catalog strings across all 29 `.po` / `.mo` locale catalogs in `languages/`.
- **REST Rate-Limiting & Sanitization:** Added transient-based per-client rate-limiting (`60` requests per minute, HTTP `429 wm_k9_rate_limited`, filterable via `wm_k9_rest_rate_limit`) using anonymised `hash_hmac( 'sha256', ... )` digests so raw IP addresses are never stored, plus schema `sanitize_callback` hooks on string parameters in `WM_K9_REST_Controller`.
- **Opt-In Uninstall (`D-UNINSTALL`):** Updated `uninstall.php` to preserve options and transients by default unless `wm_k9_delete_data_on_uninstall` (default `'0'`) is explicitly enabled, registered via `register_setting` and exposed under the new *Einstellungen* tab on `wm-k9-planner`.
- **Developer Hooks & Ecosystem Bridges:** Added `WM_K9_Ecosystem_Bridges` registering WordPress 6.5+ Block Bindings source `wm-k9/exam-info` and GenerateBlocks 2.x Dynamic Tag `{{wm_k9_info}}`, alongside developer filters (`wm_k9_zulassung_result`, `wm_k9_bewertung_result`, `wm_k9_rest_rate_limit`, `wm_k9_exam_info_value`) and actions (`wm_k9_ready`, `wm_k9_zulassung_checked`, `wm_k9_bewertung_evaluated`).
- **Release & Compliance Metadata:** Added CycloneDX 1.6 `bom.json`, `.license-allowlist.yml`, WordPress Playground `.wordpress-org/blueprints/blueprint.json`, `AGENTS.md -> CLAUDE.md` symlink, and expanded `.distignore`.

### Fixed — the catalog and the code had stopped overlapping (2026-09-20)

- **26 of the 27 translatable strings had no `.pot` entry, so nobody could translate them into any language.** A string with no catalog entry never reaches a translator: nothing errors, no warning is printed, and the interface simply stays German. What was missing is the part of the plugin that talks to a person — every reason the admission pre-check gives for turning a dog away (age not reached, exam date before the date of birth, a transponder code that is not 15 digits, no Sachkundenachweis), the verdicts of both forms and the WP-CLI output.
- **What the catalogs did carry were four words: `Bestanden`, `Prüfung`, `Unterordnung`, `Zulassung`** — translated into 23 locales, 92 real translations. Only `Zulassung` still occurs in the code. The other three name things the code no longer says, so for the best part of a year 29 locales held a vocabulary list rather than a translation of the plugin.
- Regenerated with `wp i18n make-pot` (the tool that defines a fresh catalog: it follows concatenation, escapes, contexts and plurals) and merged into all 29 catalogs with `msgmerge --no-fuzzy-matching`. The `.pot` went from 4 entries to 32. **Nothing was deleted:** the 69 translations of the three retired words are kept as obsolete `#~` blocks, so if `Bestanden`, `Prüfung` or `Unterordnung` returns to the code, its 23 translations come back with it. Verified by a count that does not pass through the merge — intersecting the catalog msgids with the strings the extractor finds in the code — which reported the same 23 live and 69 retired.
- `wp-plugin-fleet-po-mojibake-repair.py` re-run afterwards as rule 6 of `CLAUDE.md` requires: 30 catalogs, 0 double-encoded, 0 unrecoverable. The repair of 2026-09-15 held.
- **New gate `tests/test-pot-freshness.php`**, run by CI on PHP 8.3: it extracts the strings from the source and fails if any lacks a `.pot` entry. It reads the text domain from the plugin header rather than a constant (a second copy is a second thing that can be wrong), treats zero extracted strings as a failure rather than a pass, ignores the plugin-header fields `make-pot` adds, requires wp-cli with no escape hatch, and runs its own self-test before judging the plugin.

### The version this file opens on matches the version the plugin ships (D15, 2026-09-17)

- **The plugin is 1.0.0, and the changelog now opens on 1.0.0 too.** This file used to open on `[1.1.0]`, a release that `git tag` (0) and `gh release list` (0) both deny and that no other file in the repo agreed with. The `1.1.0` entry moved **verbatim and complete** to an appendix at the foot of this file: nothing was deleted, because a changelog is a record and the record is that those entries were written. Moved by the fleet tooling, which refuses to write if a single non-blank line would be lost.

### Family sheets (2026-09-16)
- Re-synced from the hub: the badge variants carry the state tokens on body surfaces, where the command-banner inks measured 1.49:1 to 2.21:1 against the composed background (hub PR #19), and the canonical sheet dropped five classes that belonged to a single plugin (hub PR #21). `tests/test-ui-family.php` re-pins both md5s.

### Fixed (reader pass, 2026-09-15)
- **REST arguments without schemas.** The routes declared no `args`, so WordPress validated nothing: `has_skn=nein`, `off` or ` false` reached `rest_sanitize_boolean()`, which reads every string but "false" and "0" as true, and a handler without a Sachkundenachweis came back eligible (the same for `teil_b_passed` and `disqualified`); points sent as `14,5` became 14.0 and turned a 42.5-point sheet into NICHT_BESTANDEN; a `birth_date` array raised "Array to string conversion". Both routes now declare boolean, number (0 to the exercise maximum) and required string arguments, and core answers `400 rest_invalid_param`. Measured before the fix on a local WordPress install and the demo Box with `tests/wp-runtime-k9.php`: 4 passed, 14 failed.
- **Impossible dates rolled over.** `createFromFormat( 'Y-m-d' )` accepted `2024-00-15` as 2023-12-15 and `2025-10-32` as 2025-11-01, and the dog came back eligible; it also copied the current time of day into both dates. Dates are now parsed with `!Y-m-d` and a round trip.
- **Age at month ends.** `DateTime::diff()` rejected a dog born on 30 November 2023 on 1 March 2025. Months are now counted as calendar months, completed on the day with the birth day's number or on the first of the next month when the month is shorter (reading of § 187 Abs. 2, § 188 Abs. 2, 3 BGB; the Prüfungsordnung gives no counting rule).
- **Exam before birth** read "Mindestalter nicht erreicht: 21 Monate"; it now says the exam date lies before the date of birth. **An unreadable date hid the other reasons**; every check now runs.
- **Score form pre-filled with full marks and Teil B ticked**, so an untouched submit printed "BESTANDEN 60/60", and after a submit the form showed those defaults instead of the entries. Fields now start empty and required, Teil B unticked, and both forms keep what was entered (unslashed and sanitised).
- **UTC date as "today"** in the form and in `wp k9 check-eligibility`; now the site's time zone (`current_datetime()`).
- **Rounding before the pass comparison:** 41.96 points were rounded to 42.0 and passed. The unrounded sum is compared now, and points show up to two decimals with a decimal comma; the reasons used a decimal point ("25.0 … 42.0") next to a heading with a comma.
- **Hub health was the literal `HEALTHY`** and telemetry `ACTIVE`, also with a failing self-test; both now follow the self-test (`ERROR` when it fails). Unknown-action messages were HTML-escaped inside the returned data, so the hub CLI printed `&quot;`.
- **Fatal on WordPress 6.4:** the shortcode callbacks required an `array`, and WordPress before 6.5 passes `''` for a shortcode without attributes. The header declares 6.4.
- **Catalogs with double-encoded UTF-8:** 62 strings in 26 catalogs (es_ES "AdmisiÃ³n", the msgid "PrÃ¼fung" in all of them); the admin tab "Zulassung" showed the garbage in 8 locales. Repaired with the fleet tooling and recompiled; test group 17 reads the `.mo` files.
- **Contrast below 4.5:1** in the frontend: error text 3.62:1, buttons 3.77:1 and 4.10:1, field borders 2.36:1; all pairs now 5.4:1 or more.
- The chip field's `maxlength="15"` blocked codes written with spaces; now 32.
- **Wording:** "ZULASSUNG ERTEILT" and the CLI's "erfüllt alle Kriterien" became "Vorabprüfung bestanden … die drei geprüften Kriterien"; the score result says "rechnerisch"; the admin overview no longer says both calculators run as a WP-CLI command; a disqualification without reason no longer asserts a "Verstoß gegen Prüfungsordnung".
- **Rule sources checked:** the five exercises and maxima are those of the FCI-Prüfungsordnung 2019; the FCI-Prüfungsordnung 2025, valid from 1 January 2025, scores Leinenführigkeit 30 and has no Freifolge. The score form, the admin screen and the docs now say so; switching the layout waits for a decision. 15 months is the VDH's minimum age; the FCI-PO sets 70 % for Teil A and requires the Sachkundeprüfung.
- Values are no longer repeated as literals in the form, CLI, admin screen and adapter (`EXERCISES`, `CHIP_DIGITS`, `RULE_EDITION`); the unused `MAX_POINTS_TEIL_A` is used, the unused `sanitize_formula()` is gone, `@filemtime` is replaced by a file check.

### Documentation (reader pass, 2026-09-15)
- README.md, README.de.md, README.es.md, DEVELOPERS.md, CLAUDE.md (and AGENTS.md, now identical to it), readme.txt, llms.txt, SECURITY.md, CONTRIBUTING.md and `public/humans.txt` describe the code above. `humans.txt` still said "Founder & CEO", MySQL, Gutenberg, React, HMAC logs and BFSG/WCAG and DSGVO compliance; AGENTS.md still named `/src/` and MySQL; SECURITY.md cited CRA Art. 14 and BSI guidelines without a mapping; CONTRIBUTING.md promised legal compliance; the test labels said "FCI/VDH PO 2025", "ISO 11784/11785" and "CycloneDX SBOM Compliance".
- The README no longer shows the banner (an FCI-style emblem with "FCI / VDH PO COMPLIANT", heart rate and GPS readings) or points to the outdated admin screenshot; `.distignore` keeps `assets/images/` out of the package. The 24 translated READMEs lose the banner and the mockup captioned "Dashboard UI". Deleting the images waits for Arnold.
- CI runs the family sheet test and the mutation check, and requires the suite's results line; the SBOM step is named as the warning it is.

### Documentation (2026-09-14)
- `README.md`, `README.de.md`, `README.es.md`, the 24 translated READMEs, `DEVELOPERS.md`, `CLAUDE.md`, `llms.txt`, `CONTRIBUTING.md`, `SECURITY.md`, `readme.txt` and the header description describe what the plugin ships: an admission pre-check and a score calculator, each as a shortcode, a public REST route and (for admission) a WP-CLI command, an information screen and a hub adapter. Until now they promised a trial scheduler, a `[wm_k9_exam_registration]` form, microchip checksum verification against country and manufacturer codes, `wp k9exam status|verify|export`, REST routes `/status` `/submit` `/export`, three tables, GoBD exports, an HMAC chain, a `wm_k9_registration_submitted` action and conformity with the "FCI / VDH 2025" rules; none of those exist in this repository. The translated READMEs are generated from `README.md` by the fleet script `wp-plugin-fleet-translations-sync.py`.
- The entries **[1.1.0]** and **[1.0.0]** below list features that never shipped as described; they are kept as the record of what was written at the time, each such line marked. The plugin header stays at 1.0.0.

### Fixed (2026-09-14)
- **REST booleans from a form were always true.** `wm-k9/v1/zulassung` and `/bewertung` cast `has_skn`, `teil_b_passed` and `disqualified` with `(bool)`, so the string `"false"` counted as true: a handler without a Sachkundenachweis came back eligible, a failed Teil B came back `BESTANDEN` and `disqualified=false` disqualified the dog (all three reproduced on a local WordPress install). The route now uses `rest_sanitize_boolean()`. Test group 9 fails with the old casts.
- **Admin screen with invented numbers and empty tabs.** The overview showed "Registered Dogs", "Eligible" and "Exams Planned" fixed at 0, and the Examinees, Evaluation and Settings tabs held placeholder comments (`Add registration list / validation tool here`) under text promising lists, sheets and settings. The screen now explains the two calculators and their entry points; the Settings tab is gone because there is nothing to set. Dashicons instead of emoji, `aria-current` on the active tab, family sheets on any hook containing the page slug.
- **The hub adapter confirmed every action** and reported `exams: 0`; it now implements `self_test` and reports unknown actions as failures, and telemetry lists what exists.
- **Frontend claims:** the forms said "gemäß FCI PO 2025 & ISO 11784/11785" and "Digitaler Richterbogen (FCI 2025)", and a short chip code was reported as not conforming to "the ISO 11784/11785 standard"; they now name the three fixed checks, say the association's rules take precedence and that the calculator is not an official sheet. Six emoji became inline SVG icons. `wp k9 status` printed a hardcoded version and "BETRIEBSBEREIT".

### Changed
- **Plugin Check on the dist view (2026-09-14):** `readme.txt` added. Without it the checker read `README.md` as the readme and flagged the missing stable tag, tested version and licence line. The new file mirrors the plugin header (Stable tag 1.0.0, Requires PHP 8.1, GPLv2 or later) and describes only what the code registers: `[wm_k9_zulassungscheck]`, `[wm_k9_richterbogen]`, `wm-k9/v1/zulassung` and `/bewertung`, `wp k9 status` and `wp k9 check-eligibility`. `Tested up to: 7.1` is backed by the local WordPress runtime pass.

### Design family (hackaton phase 3, 2026-09-14)
- The admin screen renders from the two WM Suite family sheets (`assets/css/wm-admin-tokens.css`, `assets/css/wm-admin-ui.css`, byte-identical copies of the hub's canonical files, pinned by `tests/test-ui-family.php`) instead of the plugin's own `admin-obsidian.css`; same handles as every WM plugin, versioned by file time. Native `.wrap`, family badge with the hub/standalone state, gettext menu titles, family menu icon, visible focus rings on every control. Applied with the fleet tooling.

---

## [1.0.0] — 2026-08-25

### Initial Enterprise Release
- Core architectural implementation for Cynology & Sports Compliance. — *what shipped are two calculators*
- WP-CLI management commands under `wp k9`.
- Revisionssicheres GoBD cryptographic ledger and telemetry hooks. — *never shipped*
- Strict compliance with VDH/FCI Prüfungsordnung 2025 (BH/VT & IBGH), ISO 11784/11785 & GoBD. — *the code applies fixed values from the FCI-PO 2019 layout; no conformity is assessed*

---

## Appendix — the entry written for 1.1.0 on 2026-08-29, never released

> **Not a released version — the plugin does not carry this number.** Measured 2026-09-17: `git tag` → 0, `gh release list` → 0, and `wm-k9-bh-exam-planner.php`, `readme.txt`, `SECURITY.md`, `includes/class-k9-spoke-adapter.php` carry **1.0.0**, which is the version this plugin ships as. The entry is kept verbatim as the record of what was written on 2026-08-29; only its heading changed, because Keep a Changelog reserves `## [x.y.z]` for releases and this was never one. Raising the plugin to 1.1.0 means raising `wm-k9-bh-exam-planner.php`, `readme.txt`, `SECURITY.md`, `includes/class-k9-spoke-adapter.php` in the same commit — the number lives in more than one file.

### Added
- **24 Official European Union Languages:** Full binary `.mo` and source `.po` translation packages in `languages/` (`bg_BG`, `cs_CZ`, `da_DK`, `de_DE`, `el_GR`, `en_GB`, `es_ES`, `et_EE`, `fi_FI`, `fr_FR`, `ga_IE`, `hr_HR`, `hu_HU`, `it_IT`, `lt_LT`, `lv_LV`, `mt_MT`, `nl_NL`, `pl_PL`, `pt_PT`, `ro_RO`, `sk_SK`, `sl_SI`, `sv_SE`). — *the catalogs held four words each (double-encoded in 26 of them until 2026-09-15), of which only `Zulassung` occurred in the code; the other 26 translatable strings had no entry at all until 2026-09-20*
- **Developer Documentation Suite:** Created `DEVELOPERS.md` with complete architecture diagrams, hook references, database schema, and REST API guides. — *the manual described tables, routes and a hash chain that never shipped; rewritten from the code on 2026-09-14*
- **Decoupled REST API Controller:** Registered namespace `/wp-json/wm-k9/v1` with strict nonce and permission validation. — *the namespace exists with two public routes and no nonce; argument schemas since 2026-09-15*
- **Zero-CDN Frontend Shortcode:** Implemented `[wm_k9_zulassungscheck]` with inline SVG icons and CSS design tokens. — *the shortcode exists; it used emoji and inline hex styles, no tokens*
- **CycloneDX v1.6 SBOM Engine:** Dynamic SHA-256 entry file integrity hashing and spoke adapter integration for **WM Suite Hub**. — *one component fragment with a SHA-256; no CycloneDX document or schema validation*
- **Enterprise Showcase Assets:** High-fidelity 16:9 marketing banner and cockpit showcase mockups conditioned on real screenshot topology. — *the cockpit mockup shows a screen the plugin does not have; the banner claims FCI/VDH conformity*
- **Clean Uninstallation Lifecycle:** Comprehensive `uninstall.php` handler for clean database and transient cache purging. — *it deletes an option and a transient the plugin never writes; there is no table*

### Security
- **CWE-1236 Mitigation:** Active sanitization against formula injection attacks on all CSV exports and dynamic text inputs. — *never shipped: there is no CSV export; the uncalled `sanitize_formula()` was removed on 2026-09-15*
- **Constant-Time Cryptography:** Enforced `hash_equals()` verification on all HMAC-SHA256 signature chains. — *never shipped: there is no hash chain*
- **SQL Parameterization:** Refactored all queries to use strict `$wpdb->prepare()` with parameterized placeholders. — *never applied: the plugin runs no database query*

### Quality & Tests
- Expanded standalone deterministic test suite (`tests/test-suite.php`) with 100% green pass rate and zero external dependencies.
