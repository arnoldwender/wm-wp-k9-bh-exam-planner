# WM K9 BH Exam Planner — Developer Manual

> Everything below is measured against the code in this repository (v1.2.2, 2026-11-21).

---

## 1. Layout

| Path | Role |
| --- | --- |
| `wm-k9-bh-exam-planner.php` | Header, constants (`WM_K9_PLANNER_VERSION` / `_FILE` / `_PATH`), `require_once` of the core classes, shortcode / REST / bridge init, `load_plugin_textdomain` and `wm_k9_ready` on `init` (5), admin screen in the admin, CLI file when `WP_CLI` is defined, hub registration on `plugins_loaded` (20). |
| `includes/class-k9-admission-validator.php` | `check_eligibility()`, `parse_date()`, `age_in_months()`, `MIN_AGE_MONTHS = 15`, `CHIP_DIGITS = 15`. |
| `includes/class-k9-scoring-engine.php` | `evaluate()`, `format_points()`, `EXERCISES` (key, label, maximum), `MAX_POINTS_TEIL_A = 60.0`, `PASSING_POINTS_TEIL_A = 42.0`, `RULE_EDITION = 'FCI-PO 2019'`. |
| `includes/class-k9-shortcodes.php` | `[wm_k9_zulassungscheck]`, `[wm_k9_richterbogen]`, `site_today()`, colour constants, private `icon()` (inline SVG). |
| `includes/class-k9-rest-controller.php` | Two routes under `wm-k9/v1` with `route_args()`, string `sanitize_callback`, and transient IP-HMAC rate-limiting (`RATE_LIMIT_MAX = 60`, `RATE_LIMIT_WINDOW = 60`). |
| `includes/class-k9-ecosystem-bridges.php` | WordPress 6.5+ Block Bindings source `wm-k9/exam-info` and GenerateBlocks 2.x Dynamic Tag `{{wm_k9_info}}`. |
| `includes/class-k9-cli.php` | `WM_K9_CLI` (`status`, `check_eligibility`, testable `eligibility()` and `success_message()`) and `WP_CLI::add_command( 'k9', … )`. |
| `includes/class-k9-admin-dashboard.php` | Menu, enqueue of the family sheets, `register_setting` (`wm_k9_delete_data_on_uninstall`), four-tab screen. |
| `includes/class-k9-spoke-adapter.php` | WM Suite Hub contract; returns early when the hub's interface does not exist. |
| `assets/css/` | `wm-admin-tokens.css`, `wm-admin-ui.css` (copies of the hub's canonical sheets, pinned by `tests/test-ui-family.php`). |
| `languages/` | 29 compiled `.po` / `.mo` pairs and `wm-k9-bh-exam-planner.pot` (43 msgids). |
| `bom.json` | CycloneDX 1.6 Software Bill of Materials (`GPL-2.0-or-later`, SHA-256 of `wm-k9-bh-exam-planner.php`). |
| `.wordpress-org/blueprints/blueprint.json` | WordPress Playground zero-network preview blueprint. |
| `uninstall.php` | Does nothing unless `wm_k9_delete_data_on_uninstall` is explicitly enabled (`1`); then deletes that flag, the only option the plugin stores. The rate-limit transients expire after 60 seconds. (Until 1.2.1 it also deleted `wm_k9_planner_settings` and `wm_k9_planner_cache`, which no version wrote.) |

---

## 2. Calculations

### `check_eligibility( string $birth_date, string $exam_date, string $microchip_code, bool $has_skn ): array{eligible, age_months, errors}`

1. Both dates through `parse_date()`: `createFromFormat( '!Y-m-d' )` (no time of day) and a round trip, so a date PHP would roll over (`2024-00-15`, `2025-10-32`, `2024-02-30`) is refused. A refused date adds one error; the other checks still run.
2. Trial date before the date of birth: its own error, `age_months` 0.
3. Otherwise `age_months = age_in_months()`: `12 × (exam year − birth year) + (exam month − birth month)`, minus one when the birth day's number is greater than the exam day's. A dog born on the 30th or 31st completes a month in a shorter month on the first day of the next month (reading of § 187 Abs. 2, § 188 Abs. 2, 3 BGB; the PO gives no counting rule). An error when `age_months < 15`.
4. Non-digits stripped from the chip code; an error unless exactly 15 digits remain.
5. An error when `$has_skn` is false.
6. Passes the result through `apply_filters( 'wm_k9_zulassung_result', $result, $context )` and fires `do_action( 'wm_k9_zulassung_checked', $result, $birth_date, $exam_date )`.

### `evaluate( array $teil_a_scores, bool $teil_b_passed, bool $disqualified = false, string $reason = '' ): array{verdict, teil_a_points, teil_a_passed, teil_b_passed, errors}`

| Exercise key | Label | Clamp |
| --- | --- | --- |
| `leinenfuehrigkeit` | Leinenführigkeit | 0–15 |
| `freifolge` | Freifolge | 0–15 |
| `sitz_bewegung` | Sitz aus der Bewegung | 0–10 |
| `ablegen_herankommen` | Ablegen mit Herankommen | 0–10 |
| `ablegen_ablenkung` | Ablegen unter Ablenkung | 0–10 |

Layout of the FCI-PO 2019 (valid from 01.01.2019). The FCI-PO 2025 (valid from 01.01.2025) has Leinenführigkeit 30, Sitz 10, Ablegen in Verbindung mit Herankommen 10, Ablegen unter Ablenkung 10 and no Freifolge; switching changes form fields and REST keys and waits for a decision.

Disqualified → `DISQUALIFIZIERT` with 0 points and the reason, or "kein Grund angegeben". Otherwise the clamped sum is rounded to six decimals (float noise only) and compared with 42; `teil_a_points` is that sum rounded to two decimals. Verdict `BESTANDEN` when Teil A and Teil B passed, else `NICHT_BESTANDEN` with the reasons; a failed Teil A reports Teil A only (both editions admit to Teil B after Teil A). Passes the result through `apply_filters( 'wm_k9_bewertung_result', $result, $context )` and fires `do_action( 'wm_k9_bewertung_evaluated', $result, $context )`. `format_points()` writes one decimal, two when the value has hundredths, with a decimal comma.

---

## 3. Shortcodes, Block Bindings & GenerateBlocks

| Surface | Identifier | Output |
| --- | --- | --- |
| Shortcode | `[wm_k9_zulassungscheck]` | Admission pre-check form (`wm_k9_dob`, `wm_k9_exam`, `wm_k9_chip`, `wm_k9_skn`, submit `wm_k9_check`) and verdict |
| Shortcode | `[wm_k9_richterbogen]` | Score calculator form (`k9_l`, `k9_f`, `k9_s`, `k9_h`, `k9_a`, `k9_b`, submit `wm_k9_score_calc`) and verdict |
| WP 6.5+ Block Bindings | `wm-k9/exam-info` | Resolves `args.key` (`summary`, `rule_edition`, `min_age_months`, `chip_digits`, `max_points_teil_a`, `passing_points_teil_a`, `site_today`) |
| GenerateBlocks 2.x Dynamic Tag | `{{wm_k9_info}}` | Renders escaped dynamic exam parameter (`{{wm_k9_info key:min_age_months}}`, defaults to `summary`) |

Both shortcode callbacks accept `$atts` untyped (WordPress before 6.5 passes `''` when a shortcode has no attributes). Posted values go through `wp_unslash()` and `sanitize_text_field()`; arrays count as empty. Inline colours come from class constants, each pair at least 4.5:1 (test group 16); icons are inline SVG with `aria-hidden="true"`.

---

## 4. REST

| Method | Route | Permission | Arguments (`route_args()`) | Response |
| --- | --- | --- | --- | --- |
| `POST` | `/wp-json/wm-k9/v1/zulassung` | `WM_K9_REST_Controller::check_zulassung_access` | `birth_date`, `exam_date`, `microchip`: string, required, sanitized; `has_skn`: boolean, default false | `200 { success, data: <check_eligibility>, timestamp }` or `429` |
| `POST` | `/wp-json/wm-k9/v1/bewertung` | `WM_K9_REST_Controller::check_bewertung_access` | the five exercise keys: number, 0 to the exercise maximum, default 0; `teil_b_passed`, `disqualified`: boolean, default false; `disqualification_reason`: string, sanitized | `200 { success, data: <evaluate>, timestamp }` or `429` |

WordPress validates each argument against its schema before the callback and answers `400 rest_invalid_param` otherwise: booleans only `true`, `false`, `1`, `0`; numbers only `is_numeric()` (a decimal comma fails); out-of-range points fail instead of being clamped. Public calls are throttled per anonymised client IP digest (`hash_hmac( 'sha256', $ip, $salt )` stored in 60-second transients, default 60 requests/minute, filterable via `wm_k9_rest_rate_limit`).

---

## 5. WP-CLI

```bash
wp k9 status
wp k9 check-eligibility --dob=2024-05-01 --exam=2025-10-01 --chip=276098100123456 --skn=true
```

`check-eligibility` takes `--exam` from today's date in the site's time zone when it is omitted, accepts `--skn` as `1`, `true` or `yes` (anything else is no certificate), and ends with `WP_CLI::success()` or `WP_CLI::error()` (exit 1) listing the reasons.

---

## 6. Hooks

### Hooks the plugin attaches to

| Hook | Where | Effect |
| --- | --- | --- |
| shortcodes registered at load | `WM_K9_Shortcodes::init()` | `[wm_k9_zulassungscheck]` and `[wm_k9_richterbogen]` |
| `init` (5) | `wm_k9_bootstrap_i18n()` | `load_plugin_textdomain( 'wm-k9-bh-exam-planner', … )` and `do_action( 'wm_k9_ready', … )` |
| `init` (20) | `WM_K9_Ecosystem_Bridges::init()` | Block Bindings source `wm-k9/exam-info` and GenerateBlocks tag `{{wm_k9_info}}` |
| `generateblocks_dynamic_tag_output` | `WM_K9_Ecosystem_Bridges::init()` | Dynamic tag fallback resolver |
| `rest_api_init` | `WM_K9_REST_Controller::init()` | `wm-k9/v1/zulassung` and `wm-k9/v1/bewertung` |
| `admin_menu`, `admin_init`, `admin_enqueue_scripts` | `WM_K9_Admin_Dashboard` | Menu, `register_setting`, and family sheets on hooks containing `wm-k9-planner` |
| `plugins_loaded` (20) | `wm_k9_register_spoke_module()` | Adds `WM_K9_Spoke_Adapter` to `wm_register_suite_module` when the hub's interface exists |

### Filters & actions the plugin offers

| Type | Name | Arguments |
| --- | --- | --- |
| Filter | `wm_k9_zulassung_result` | `( array $result, array $context )` |
| Filter | `wm_k9_bewertung_result` | `( array $result, array $context )` |
| Filter | `wm_k9_rest_rate_limit` | `( int $limit, string $bucket )` |
| Filter | `wm_k9_exam_info_value` | `( ?string $value, string $key )` |
| Action | `wm_k9_ready` | `( string $version )` |
| Action | `wm_k9_zulassung_checked` | `( array $result, string $birth_date, string $exam_date )` |
| Action | `wm_k9_bewertung_evaluated` | `( array $result, array $context )` |

---

## 7. Admin & Opt-in Uninstall

Menu *BH Exam Planner* (`manage_options`, family icon, position 84), page `wm-k9-planner`. Tabs `overview`, `examinees`, `evaluation`, and `settings`; unknown `tab` values fall back to `overview`, and the active tab carries `aria-current="page"`. The `settings` tab exposes `wm_k9_delete_data_on_uninstall` (group `wm_k9_planner_options`, default `'0'`). `uninstall.php` checks this option and exits immediately unless explicitly enabled.

---

## 8. WM Suite Hub spoke

`WM_K9_Spoke_Adapter`: slug `wm-k9-bh-exam-planner`, version from `WM_K9_PLANNER_VERSION`, health `HEALTHY` while the self-test passes and `ERROR` otherwise, SBOM component with the SHA-256 of `wm-k9-bh-exam-planner.php` and `GPL-2.0-or-later`, settings URL `admin.php?page=wm-k9-planner`, telemetry `status` (same as health), `shortcodes`, `rest_routes`; action `self_test`, every other action returns `success: false` with a plain-text message.

---

## 9. Tests

```bash
php tests/test-suite.php        # admission, scoring, REST schemas & throttling, shortcodes, CLI, adapter, hooks, bridges, uninstall, catalogs, literals
php tests/test-pot-freshness.php # every translatable string has a .pot entry
bash tests/run-mutations.sh     # mutation gate in tests/mutations.php, each must turn the suite red (control run first)
wp eval-file wp-content/plugins/wm-k9-bh-exam-planner/tests/wp-runtime-k9.php --user=1   # read-only inside WordPress
```

---

## 10. Security disclosure

See [SECURITY.md](SECURITY.md).
