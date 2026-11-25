=== WM K9 BH Exam Planner ===
Contributors: arnoldwender
Tags: dog-sport, exam, companion-dog, calculator, microchip
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Admission pre-check and score calculator for the companion dog trial (Begleithundeprüfung BH/VT), run on the club's own site.

== Description ==

* **Admission pre-check:** the shortcode `[wm_k9_zulassungscheck]` takes the dog's date of birth, the trial date, the transponder code and whether the handler holds a Sachkundenachweis. It checks three fixed values — at least 15 full calendar months on the trial date, exactly 15 digits in the chip code (format only), the handler certificate — refuses dates that do not exist and lists every reason the dog is not eligible.
* **Score calculator:** the shortcode `[wm_k9_richterbogen]` sums the five exercises of Teil A in the layout of the FCI-Prüfungsordnung 2019 (60 points, 42 to pass) and combines them with the Teil B result. The FCI-Prüfungsordnung 2025, valid from 1 January 2025, scores the exercises differently (Leinenführigkeit 30 points, no Freifolge); the form says so.
* **REST API:** `wm-k9/v1/zulassung` and `wm-k9/v1/bewertung` run the same calculations (the latter also takes a disqualification); both are public, stateless, rate-limited per anonymised client digest (60 requests/minute) and store no trial data. Booleans are `true`/`false` or `1`/`0` and points numbers with a dot within the exercise range; anything else gets a 400 answer.
* **WP-CLI:** `wp k9 status` and `wp k9 check-eligibility --dob=YYYY-MM-DD --exam=YYYY-MM-DD --chip=<15 digits> --skn=true|false`.
* **Block Bindings & GenerateBlocks:** WordPress 6.5+ Block Bindings source `wm-k9/exam-info` and GenerateBlocks 2.x Dynamic Tag `{{wm_k9_info}}` expose read-only trial parameters (`summary`, `rule_edition`, `min_age_months`, `chip_digits`, `max_points_teil_a`, `passing_points_teil_a`, `site_today`), alongside developer filters and actions.
* **Admin screen & opt-in uninstall:** "BH Exam Planner" explains the two calculators, where they run, where the values come from, and provides an explicit opt-in checkbox (`wm_k9_delete_data_on_uninstall`, default off) so uninstalling preserves options unless opted in.

The values are fixed in the code; the organising club's rules take precedence. The calculator is not an official judge's sheet and grants no admission. No custom database tables, no stored trial data, no external requests.

== Frequently Asked Questions ==

= Is technical or private support provided? =

No. This plugin comes with zero support (`0` support). Private inquiries sent via email, `arnoldwender.com`, or social/chat channels are not answered. Reproducible bugs belong solely on GitHub Issues (`https://github.com/arnoldwender/wm-wp-k9-bh-exam-planner/issues`) with no guarantee of reply or resolution. The software is provided **"AS IS"** at your sole risk with zero warranty or liability (see `SUPPORT.md` in the repository).

== Screenshots ==

1. BH Exam Planner admin reference covering admission prerequisites and Teil A / Teil B scorecard layout
2. Interactive frontend admission check ([wm_k9_zulassungscheck]) displaying a computed BESTANDEN verdict

== Changelog ==

= 1.2.2 =
* The bundled family stylesheet is identical to WM Suite Hub 1.2.5 again: it starts the admin header below WordPress's help toggle instead of shortening it. This plugin's own screens register no help tab, so they change only where another plugin adds one.

= 1.2.1 =
* Removed: `uninstall.php` deleted the option `wm_k9_planner_settings` and the transient `wm_k9_planner_cache`, which no version of the plugin ever wrote. With the opt-in enabled it now deletes the one option the plugin stores, `wm_k9_delete_data_on_uninstall`; the test suite checks that every name it deletes is one the plugin writes.
* Fixed: The 29 catalogs dropped one entry the code no longer translates and fill the plugin and author URI headers they left empty.

= 1.2.0 =
* The WM Suite Hub contract now lives in the `ArnoldWender\SuiteHub\Contracts` namespace. The spoke adapter registers only with WM Suite Hub 1.2.0 or later, so update the hub together with this plugin; used on its own, the plugin works as before.
* The `.wordpress-org/` banners show the current version.
* Removed: The archived pre-2026-11-19 marketing visuals in `versionen/`: they claimed features and conformity the plugin does not have. They were never part of the installable package.

= 1.1.3 =
* Removed the agency name, links to unrelated projects and the personal mailbox from code, catalogs and documentation; the admin family badge and the hub menu read "WM Suite". The READMEs say the plugin was built with an agentic workflow (AI coding agents) and should be reviewed like any third-party code.
* Added root `SUPPORT.md`, GitHub Issue templates, explicit zero-support & "AS IS" liability notices in READMEs and `readme.txt`, and normalized canonical GPLv2+ `arnoldwender.com` / `arnoldwender/wm-wp-k9-bh-exam-planner` links.
* Updated `.wordpress-org/` visual banners to personal `ARNOLD WENDER · OPEN SOURCE` branding.

= 1.1.2 =
* Add `.wordpress-org/` visual kit (banners, icons, live screenshots) and synchronize release metadata at `v1.1.2`.

= 1.1.1 =
* Point Plugin URI to the official plugin page.

= 1.1.0 =
* Added: `load_plugin_textdomain` on `init` and complete translations across all 29 `.po`/`.mo` locale catalogs in `languages/`.
* Added: transient-based anonymised client rate-limiting (`60` requests/minute, HTTP 429) and schema string sanitization callbacks on `wm-k9/v1/zulassung` and `wm-k9/v1/bewertung`.
* Added: opt-in data cleanup on uninstall (`wm_k9_delete_data_on_uninstall`, default `0`) registered via `register_setting` and exposed under the new Settings tab on `wm-k9-planner`.
* Added: developer filters (`wm_k9_zulassung_result`, `wm_k9_bewertung_result`, `wm_k9_rest_rate_limit`, `wm_k9_exam_info_value`) and actions (`wm_k9_ready`, `wm_k9_zulassung_checked`, `wm_k9_bewertung_evaluated`).
* Added: WordPress 6.5+ Block Bindings source `wm-k9/exam-info` and GenerateBlocks 2.x Dynamic Tag `{{wm_k9_info}}`.
* Added: CycloneDX 1.6 `bom.json` and WordPress Playground blueprint `.wordpress-org/blueprints/blueprint.json`.
* Fixed: REST argument schemas (`400 rest_invalid_param` for invalid booleans, comma decimals and out-of-range points), calendar-month age calculation without date rollover, unrounded pass comparison (`41.96` fails), site-local default trial date, frontend 4.5:1 colour pairs, and WM Suite admin family sheets.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.2.2 =
Stylesheet update only (the family sheet of WM Suite Hub 1.2.5). No data or settings change.

= 1.2.1 =
Cleanup only: the uninstaller no longer deletes two names the plugin never wrote. No data or settings change.

= 1.2.0 =
Hub users: update WM Suite Hub to 1.2.0 together with this plugin. The hub contract moved to a new PHP namespace; until both are updated the plugin keeps working on its own but is not listed in the hub cockpit.
