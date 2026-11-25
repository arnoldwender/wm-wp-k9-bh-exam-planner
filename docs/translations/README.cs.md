# WM K9 BH Exam Planner (Čeština)

<p align="center">
  [🇧🇬 Български](README.bg.md) • [🇨🇿 Čeština](README.cs.md) • [🇩🇰 Dansk](README.da.md) • [🇩🇪 Deutsch](../../README.de.md) • [🇬🇷 Ελληνικά](README.el.md) • [🇬🇧 English](../../README.md) • [🇪🇸 Español](../../README.es.md) • [🇪🇪 Eesti](README.et.md) • [🇫🇮 Suomi](README.fi.md) • [🇫🇷 Français](README.fr.md) • [🇮🇪 Gaeilge](README.ga.md) • [🇭🇷 Hrvatski](README.hr.md)<br/>[🇭🇺 Magyar](README.hu.md) • [🇮🇹 Italiano](README.it.md) • [🇱🇹 Lietuvių](README.lt.md) • [🇱🇻 Latviešu](README.lv.md) • [🇲🇹 Malti](README.mt.md) • [🇳🇱 Nederlands](README.nl.md) • [🇵🇱 Polski](README.pl.md) • [🇵🇹 Português](README.pt.md) • [🇷🇴 Română](README.ro.md) • [🇸🇰 Slovenčina](README.sk.md) • [🇸🇮 Slovenščina](README.sl.md) • [🇸🇪 Svenska](README.sv.md)
</p>

---

<p align="center">
  <a href="https://wordpress.org"><img src="https://img.shields.io/badge/WordPress-6.4%20--%207.1-21759b.svg?logo=wordpress&logoColor=white" alt="WordPress Version" /></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.1%20%7C%208.2%20%7C%208.3-777bb4.svg?logo=php&logoColor=white" alt="PHP Version" /></a>
  <img src="https://img.shields.io/badge/Language-cs_CZ-blue.svg" alt="Čeština" />
  <img src="https://img.shields.io/badge/Zero--CDN-100%25%20Local-purple.svg" alt="Zero-CDN" />
  <a href="../../LICENSE"><img src="https://img.shields.io/badge/License-GPLv2%20or%20later-brightgreen.svg" alt="License" /></a>
</p>

> **Admission pre-check (age, 15-digit transponder code, handler certificate) and score calculator for the companion dog trial (Begleithundeprüfung BH/VT) — as shortcodes, REST routes and a WP-CLI command**  
> **Stránka pluginu:** [https://arnoldwender.com/en/wordpress-plugins/wm-k9-bh-exam-planner/](https://arnoldwender.com/en/wordpress-plugins/wm-k9-bh-exam-planner/)  
> **Vyvinuto pomocí agentního workflow:** vytvořeno pomocí programovacích agentů AI. Před nasazením do produkce jej zkontrolujte a otestujte jako jakýkoli kód třetích stran.  
> **Support & Liability:** Zero support — reproducible bugs on [GitHub Issues](https://github.com/arnoldwender/wm-wp-k9-bh-exam-planner/issues) only · no private support · AS IS / no liability ([SUPPORT.md](../../SUPPORT.md))

---

## 🟢 ÚROVEŇ 1: STRUČNÝ PRŮVODCE PRO ZAČÁTEČNÍKY A TVŮRCE WEBU (NO-CODE)

### Co tento plugin dělá pro váš web?
Admission pre-check (age, 15-digit transponder code, handler certificate) and score calculator for the companion dog trial (Begleithundeprüfung BH/VT) — as shortcodes, REST routes and a WP-CLI command

### 🚀 Jak začít za 2 minuty (Krok za krokem):
1. Install and activate the plugin (WordPress 6.4+, PHP 8.1+).
2. Place `[wm_k9_zulassungscheck]` and / or `[wm_k9_richterbogen]` on a page. Each form posts back to the same page and prints its verdict below the form.
3. From a club form or an app, call `POST /wp-json/wm-k9/v1/zulassung` or `/bewertung`; booleans as `true`/`false` or `1`/`0`, points with a decimal dot. Anything else gets `400 rest_invalid_param`.
4. On the server: `wp k9 check-eligibility --dob=2024-05-01 --exam=2025-10-01 --chip=276098100123456 --skn=true`.

### 💡 Často kladené otázky pro začátečníky:
#### ❓ What is not included?
No trial scheduling, event calendar, dog or handler records, score archive, custom database tables or stored examination data; no audit export; no microchip checksum or registry lookup; no external requests; no frontend stylesheet or script (the forms carry inline styles); no check of vaccination records, association membership or starter limits.

---

## 🟡 ÚROVEŇ 2: PŘÍRUČKA PRO WEBMASTERY A SPRÁVCE WEBU

### What the plugin does
1. **Admission pre-check.** `WM_K9_Admission_Validator::check_eligibility( $birth_date, $exam_date, $microchip, $has_skn )` runs three checks with values fixed in the code and returns `eligible`, `age_months` and every reason for rejection. Age: at least 15 full calendar months on the trial date. A month is complete on the day with the birth day's number, or on the first day of the next month when the target month is shorter (a dog born on 30 November 2023 is 15 months old on 1 March 2025); this is the reading of § 187 Abs. 2 and § 188 Abs. 2, 3 BGB, as the Prüfungsordnung gives no counting rule. Both dates must exist in the form `YYYY-MM-DD` (`2024-02-30` or `2024-00-15` are refused), and a trial date before the date of birth is its own reason. Transponder: exactly 15 digits after removing everything else (a format check — ISO 11784 numbers carry no checksum, and whether the chip exists or belongs to the dog is not checked). Handler: the Sachkundenachweis (SKN) as declared by the caller. Front doors: `[wm_k9_zulassungscheck]`, `POST /wp-json/wm-k9/v1/zulassung` (`birth_date`, `exam_date`, `microchip`, `has_skn`) and `wp k9 check-eligibility --dob=… --exam=… --chip=… --skn=true|false`.
2. **Score calculator.** `WM_K9_Scoring_Engine::evaluate( $teil_a_scores, $teil_b_passed, $disqualified, $reason )` clamps the five Teil A exercises — Leinenführigkeit (15), Freifolge (15), Sitz aus der Bewegung (10), Ablegen mit Herankommen (10), Ablegen unter Ablenkung (10), the layout of the FCI-Prüfungsordnung 2019 — sums them (60), compares the sum with 42 before rounding it for display (41.96 fails and is shown as 41,96), takes the Teil B result as given and returns `BESTANDEN` only when both passed, or `DISQUALIFIZIERT` when a disqualification is set (without a reason: "kein Grund angegeben"). Front doors: `[wm_k9_richterbogen]` (no disqualification field) and `POST /wp-json/wm-k9/v1/bewertung` (the five exercise keys, `teil_b_passed`, `disqualified`, `disqualification_reason`).
3. **REST validation and throttling.** Both routes declare argument schemas and string sanitization callbacks, so WordPress answers `400 rest_invalid_param` before the calculators run when a boolean is anything but `true`/`false`/`1`/`0`, when points are not a number with a dot or lie outside 0 to the exercise maximum (`14,5`, `16`), or when `birth_date`, `exam_date` or `microchip` is missing or not a string. Public calls are rate-limited to 60 requests per minute per anonymised client digest (`429 wm_k9_rate_limited` when exceeded), without storing raw IP addresses.
4. **Forms.** Both shortcodes post back to the same page, keep what was entered, and print the verdict below the form: "VORABPRÜFUNG BESTANDEN" / "NICHT BESTANDEN" for the admission check, "RECHNERISCH BESTANDEN" / "NICHT BESTANDEN" with points for the score calculator. The exercise fields start empty and are required, Teil B starts unticked, the trial date starts at today's date in the site's time zone. Inline colours reach at least 4.5:1.
5. **Admin screen & opt-in uninstall.** Menu *BH Exam Planner* (position 84), page `wm-k9-planner` with four tabs: an overview of the two calculators and their entry points, one tab each describing what the admission check and the score calculator compute (including the sources of the values and the 2025 layout), and a *Settings* tab exposing the opt-in `wm_k9_delete_data_on_uninstall` flag (default `0`, so `uninstall.php` preserves options unless explicitly enabled). Rendered from the WM Suite admin family sheets.
6. **WP-CLI.** `wp k9 status` prints the version and the fixed values; `wp k9 check-eligibility` runs the admission check (trial date defaults to today in the site's time zone) and exits non-zero when the dog is not eligible.
7. **Block Bindings, GenerateBlocks & developer hooks.** Registers WordPress 6.5+ Block Bindings source `wm-k9/exam-info` and GenerateBlocks 2.x Dynamic Tag `{{wm_k9_info}}` exposing the single-source trial parameters (`summary`, `rule_edition`, `min_age_months`, `chip_digits`, `max_points_teil_a`, `passing_points_teil_a`, `site_today`), plus filters (`wm_k9_zulassung_result`, `wm_k9_bewertung_result`, `wm_k9_rest_rate_limit`, `wm_k9_exam_info_value`) and actions (`wm_k9_ready`, `wm_k9_zulassung_checked`, `wm_k9_bewertung_evaluated`).
8. **WM Suite Hub spoke & i18n.** `includes/class-k9-spoke-adapter.php` registers through `wm_register_suite_module`: health `HEALTHY` while both calculators return their known values and `ERROR` otherwise, CycloneDX 1.6 SBOM (`bom.json` and spoke component), settings URL `admin.php?page=wm-k9-planner`, telemetry (two shortcodes, two routes) and a `self_test` action. Translations load on `init` across 29 compiled `.po` / `.mo` catalogs in `languages/`.

### Not shipped
No trial scheduling, event calendar, dog or handler records, score archive, custom database tables or stored examination data; no audit export; no microchip checksum or registry lookup; no external requests; no frontend stylesheet or script (the forms carry inline styles); no check of vaccination records, association membership or starter limits.

---

## 🔴 ÚROVEŇ 3: PŘÍRUČKA PRO VÝVOJÁŘE A SOFTWAROVÉ ARCHITEKTY

Classes, hooks, options, the admin screen and the WM Suite Hub adapter are documented in the English manual: **[DEVELOPERS.md](../../DEVELOPERS.md)**.

---

## ⚖️ Evropské právní předpisy a shoda

- **Zero external requests:** dates, chip numbers and scores are computed on the site and never sent anywhere.
- **Privacy:** no dog, handler or score data is written to the database; dates of birth and chip numbers exist only in the request, and REST rate-limiting hashes client addresses into transient digests rather than storing raw IPs.
- **Scope:** the plugin gives a pre-check and a sum. It is not an official judge's sheet and does not replace the organising club's admission decision or the judge's verdict; under the FCI-PO the judge announces no points, only "Bestanden" or "Nicht bestanden" and the rating.
- **Sources of the values:** FCI-Prüfungsordnung 2019 (exercise layout), FCI-Prüfungsordnung 2025 (70 % rule, age left to the national organisation, Sachkundeprüfung), VDH (15 months), §§ 187, 188 BGB (month counting). No conformity with any of them is claimed or tested.

---

## 🔒 Licence a vlastnická práva

Distributed under the **GNU General Public License v2 or later**.  
Copyright (C) 2026 Arnold Wender.  
