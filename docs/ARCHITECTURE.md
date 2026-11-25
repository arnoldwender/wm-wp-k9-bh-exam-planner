# Architecture — WM K9 BH Exam Planner
`wm-k9-bh-exam-planner` · version `1.1.2` from the plugin header · WordPress 6.4+ · PHP 8.1+ · text domain `wm-k9-bh-exam-planner`

Measured against `0bb6d9d`. Every figure below comes from the code, not from any document — the commands are given so they can be re-run.

```bash
# the inventory in this file
python3 the fleet's architecture inventory tool wm-k9-bh-exam-planner
```

---

## What runs where

```
WM_K9_Admission_Validator   the admission pre-check
                              shortcode · POST wm-k9/v1/zulassung · wp k9 check-eligibility
WM_K9_Scoring_Engine        the score calculator
                              shortcode · POST wm-k9/v1/bewertung
WM_K9_Ecosystem_Bridges     Block Bindings (wm-k9/exam-info) & GenerateBlocks ({{wm_k9_info}})
admin screen                calculator reference + opt-in uninstall flag (wm_k9_delete_data_on_uninstall)
hub adapter                 added through the wm_register_suite_module filter
```

Plain classes under `includes/` (`class-k9-*.php`) — **no namespace and no autoloader**; the main
file `require_once`s them. **No trial, dog or handler records are stored.**

---

## Wiring — what to touch for a given change

| Change | Files, in order | Trap |
|---|---|---|
| Change an age, a digit count, an exercise, a maximum or the threshold | **only** `WM_K9_Admission_Validator` (age, digits) or `WM_K9_Scoring_Engine::EXERCISES` and its constants | UI, CLI and adapter text format these with `sprintf()`, so they follow automatically. A changed value **cites its source** in the commit and in `DEVELOPERS.md`. The layout is **FCI-PO 2019**; FCI-PO 2025 differs and switching awaits a maintainer decision |
| Add a REST parameter | the schema in `route_args()` | booleans are `boolean`, points are `number` with that exercise's maximum, dates and chip are `string`, required where required. **Never parse a form boolean with a `(bool)` cast** — test group 9 pins this, because `(bool) "false"` is `true` |
| Anything touching a date | `parse_date()` (`!Y-m-d` plus a round trip) and `age_in_months()` | defaults come from `current_datetime()` in the **site's** time zone, never `gmdate()` |
| Add a class | `includes/class-k9-<name>.php` → `require_once` in the main file | there is no autoloader; a file not required does not exist |

---

## Fragile spots

1. **Missing input is never a pass.** Form fields start empty, Teil B unticked, and an unreadable date still lists every other reason for refusal. A validator whose default is "eligible" is the failure mode here.
2. **One source per value.** See the wiring table — the same number in two files is what drifts.
3. **No conformity claims and no admission granted.** Results say *Vorabprüfung* and *rechnerisch*. "FCI / VDH 2025", "ISO checksum", "ZULASSUNG ERTEILT" and KPI tiles for data the plugin does not store have all been removed once; do not reintroduce them.
4. **Dates are calendar dates**, not timestamps. See the wiring table.
5. **Icons:** dashicons in the admin, inline SVG with `aria-hidden="true"` in the frontend, **never emoji** — test group 11 fails on one. Frontend colour pairs ≥ 4.5:1 (test group 16).
6. **Catalogs are UTF-8 once.** After touching `languages/`, run `wp-plugin-fleet-po-mojibake-repair.py wm-k9-bh-exam-planner`; test group 17 checks the files.

---

<!-- wm-measured-inventory -->

## Measured inventory

Regenerated from the code, never hand-edited. Everything above this line is written by hand and stays.

```bash
python3 the fleet's architecture inventory tool wm-k9-bh-exam-planner --append-to docs/ARCHITECTURE.md
```

## Inventory by layer

**10 PHP files, 1526 lines** (excluding `tests/`, `vendor/`, `node_modules/`, `languages/`).

| Layer | Files | Lines |
|---|---:|---:|
| `(raíz)` | 2 | 99 |
| `includes` | 8 | 1427 |

---

## Classes

| Class | File |
|---|---|
| `WM_K9_Admin_Dashboard` | [`includes/class-k9-admin-dashboard.php`](../includes/class-k9-admin-dashboard.php) |
| `WM_K9_Admission_Validator` | [`includes/class-k9-admission-validator.php`](../includes/class-k9-admission-validator.php) |
| `WM_K9_CLI` | [`includes/class-k9-cli.php`](../includes/class-k9-cli.php) |
| `WM_K9_Ecosystem_Bridges` | [`includes/class-k9-ecosystem-bridges.php`](../includes/class-k9-ecosystem-bridges.php) |
| `WM_K9_REST_Controller` | [`includes/class-k9-rest-controller.php`](../includes/class-k9-rest-controller.php) |
| `WM_K9_Scoring_Engine` | [`includes/class-k9-scoring-engine.php`](../includes/class-k9-scoring-engine.php) |
| `WM_K9_Shortcodes` | [`includes/class-k9-shortcodes.php`](../includes/class-k9-shortcodes.php) |
| `WM_K9_Spoke_Adapter` | [`includes/class-k9-spoke-adapter.php`](../includes/class-k9-spoke-adapter.php) |

---

## Entry points

**REST routes:** `/bewertung`, `/zulassung`

**WP-CLI:** `k9`

**Shortcodes:** `wm_k9_richterbogen`, `wm_k9_zulassungscheck`

---

## Persistence — who writes each key and who reads it

| Key | Written by | Read by |
|---|---|---|
| `wm_k9_delete_data_on_uninstall` | `includes/class-k9-admin-dashboard.php` | `includes/class-k9-admin-dashboard.php`, `uninstall.php` |

Every key has exactly one writing layer and at least one reader.


---

## Gates

- `tests/mutations.php`
- `tests/run-mutations.sh`
- `tests/test-pot-freshness.php`
- `tests/test-suite.php`
- `tests/test-ui-family.php`
- `tests/wp-runtime-k9.php`
- `.github/workflows/ci.yml`

What CI actually runs is in the workflow files above — a test file that no workflow invokes is not a gate.

---

## Drift detected against the documentation

Nothing measurable: versions agree and every class named in the documents exists.

