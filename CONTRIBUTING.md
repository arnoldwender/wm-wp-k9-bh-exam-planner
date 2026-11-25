# Contributing to WM K9 BH Exam Planner

Thank you for your interest in contributing to **WM K9 BH Exam Planner**.

---

## Zero Support & Issue Scope

**Zero technical, configuration, or private support is provided.** See [SUPPORT.md](SUPPORT.md):

- Report reproducible code defects **exclusively** via public [GitHub Issues](https://github.com/arnoldwender/wm-wp-k9-bh-exam-planner/issues).
- **Do not** contact the author privately via email, `arnoldwender.com`, or direct messages — private inquiries are never answered.
- Issues and pull requests carry no response or maintenance SLA, and the software is provided strictly **"AS IS"** without warranty or liability.

---

## Core Development Standards

1. **PHP 8.1+ strict typing:** every PHP file under `includes/` starts with `declare(strict_types=1);` and a direct-access guard. The runtime check runs through `wp eval-file`, where neither applies.
2. **Self-hosted assets:** no CDNs, Google Fonts or external telemetry loaded by the plugin.
3. **Docs measured against the code:** `README.md` and `DEVELOPERS.md` describe what the plugin registers. A new shortcode, route, command or table is documented in the same pull request; nothing is documented that does not exist.
4. **Rule values:** a changed threshold, maximum or exercise cites the edition of the Prüfungsordnung it comes from in the commit and in `DEVELOPERS.md`, and is changed in one constant; no text claims conformity or grants admission.
5. **Interface:** frontend colour pairs at least 4.5:1 (test group 16); icons are dashicons (admin) or inline SVG (frontend), never emoji; missing input is never a pass.

---

## Development Workflow

```bash
# 1. Clone the repository
git clone https://github.com/arnoldwender/wm-wp-k9-bh-exam-planner.git
cd wm-k9-bh-exam-planner

# 2. Run the standalone suites (no WordPress needed)
php tests/test-suite.php
bash tests/run-mutations.sh

# 3. Inside WordPress (read-only) and Plugin Check before a pull request
wp eval-file wp-content/plugins/wm-k9-bh-exam-planner/tests/wp-runtime-k9.php --user=1
wp plugin check wm-k9-bh-exam-planner
```

---

## Commit Conventions

- Format: `[Feature] ...`, `[Fix] ...`, `[Security] ...`, `[Docs] ...`.
- A pull request that changes a calculation, a route or a form adds an assertion to `tests/test-suite.php` that fails without it, an entry in `tests/mutations.php`, and updates README.md, DEVELOPERS.md and CHANGELOG.md.

For support and liability rules, read [SUPPORT.md](SUPPORT.md).
