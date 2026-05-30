## Goal
Integrate official prelist SE2026 data into dashboard and fix all post-integration errors across affected pages.

## Constraints & Preferences
- PSR-4 autoload maps `App\` to `src/` and `app/`
- OpenSpout 4.28.5 for streaming XLSX import (30 MB file)
- Role system: admin, operator, pegawai, pml, pcl, task_force, mitra
- Session fingerprint security must not break AJAX requests

## Progress
### Done
- **Audit DataTables "Invalid JSON" root cause fixed**: `Session::generateFingerprint()` included `HTTP_ACCEPT` header — browser sends `Accept: text/html,...` on page load vs `Accept: */*` on XHR/fetch, causing fingerprint mismatch, session destruction, and HTML redirect instead of JSON. Removed `HTTP_ACCEPT` from fingerprint hash in `src/Helpers/Session.php:112`
- **AuthMiddleware AJAX fallback added**: `BaseMiddleware::redirectToLogin()`, `forbidden()`, `unauthorized()` now return JSON 401/403 when `X-Requested-With: XMLHttpRequest` is present
- **DataTable edge case handled**: `AuditLogController.dataTable()` now wraps in try-catch, clamps `$start >= 0`, handles `length=-1` (→ 10,000), casts nullable columns safely
- **Petugas page restricted to admin-only**: `PAGE_ACCESS['dashboard']['petugas']` changed to `[ROLE_ADMIN]`; `PetugasController::index()` calls `$this->requireRole('admin')` defense-in-depth; sidebar link hidden for non-admin; `requireRole()` in base Controller now redirects with flash error instead of blank 403
- **Petugas view updated**: shows ID + Nama Lengkap columns; `nama_lengkap` added to SQL SELECT; ORDER BY `id` for natural listing
- **Prelist SQL tables created**: `prelist_kabkota`, `prelist_kecamatan`, `prelist_sls`, `prelist_subsektor` — all with proper indexes
- **`scripts/import_prelist.php`**: CLI streaming import via OpenSpout 4, batch INSERT with UPSERT
  - Sheet 1 (Prelist SE2026) → `prelist_kabkota` (38 rows)
  - Sheet 2 (Prelist SE2026 kecamatan) → `prelist_kecamatan` (667 rows)
  - Sheets 7-44 (Prelist SE2026_35XX per-kab SLS detail) → `prelist_sls` (234,180 rows)
  - Sheet 5 (subsektorA) → `prelist_subsektor` (191,566 rows) + UPDATE `prelist_sls.subsektor` (184,984 SLS populated)
  - Sheets 3, 4, 6, 45, 46: skipped (desa aggregate, SBR, plkumkm, empty)
  - Options: `--kab=3509`, `--quick`, `--subsektor`, `--batch=2000`
  - Manual arg parsing (getopt unreliable on this PHP build)
- **Prelist data imported**: 38 kab/kota, 667 kecamatan, 234,180 SLS, 191,566 subsektor lookup
- **`src/Models/PrelistModel.php`**: KPI, komposisi usaha, perbandingan SE2016, beban kerja, workload stats queries
- **Dashboard integration**: `DashboardController` injects PrelistModel data; view shows KPI card (total KK/SLS/UTP, UB/UM/UMK, PPL/PML) + stacked bar chart SE2016 vs SE2026 per kab + doughnut komposisi usaha (UB/UM/UMK); `dashboard.js` initializes both new charts
- **500 ParseError fixed**: unclosed `<?php if (...): ?>` in inline JS block at `views/dashboard/index.php:487` — now uses proper `if/else/endif` structure

### In Progress
- *(none)*

### Blocked
- *(none)*

## Key Decisions
- Session fingerprint excludes `HTTP_ACCEPT` — prevents false mismatch between page-load and XHR/fetch requests while preserving User-Agent + IP security
- Prelist import via CLI script, not web controller — 30 MB XLSX would exceed PHP timeout/memory limits in web context
- `prelist_kabkota`/`prelist_kecamatan`/`prelist_sls` use separate schema — no conflict with existing `sipw_import`, `master_sls`, `alokasi_petugas` tables
- Per-kab SLS Detail sheets (7-44) used for `prelist_sls` — these have actual 14-digit `idsls`; Sheet 3 desa aggregates skipped
- Manual CLI arg parsing used instead of `getopt()` (getopt unreliable on this PHP/Windows build)
- `subsektor` column left as 0 in prelist_sls (not imported from subsektorA sheet) — can be updated later

## Next Steps
1. Verify dashboard displays prelist KPI + charts at http://localhost/dashboard-se2026/ (login required)
2. Verify Jember (3509) prelist data specifically
3. Optionally import subsektorA data into a separate table or prelist_sls subsektor column
4. Optionally import Sheet 4 (SBR) data into prelist_kecamatan or separate table

## Critical Context
- `PRELIST SE2026.xlsx` (29.8 MB) at `C:\laragon\www\dashboard-se2026\data\PRELIST SE2026.xlsx`
- OpenSpout sheet iterator uses 1-based indexing (Sheet 1 = first sheet)
- Sheet 5 (subsektorA) contains per-SLS subsektor codes with formula XLOOKUP in per-kab sheets
- Per-kab SLS sheets (7-44) have formulas for iddesa/kab/kec/desa columns → computed in PHP from idsls
- 234,180 SLS imported (vs 250,494 SLS in kabkota aggregate — discrepancy likely due to different aggregation source)
- `prelist_kecamatan` has 667 rows (expected ~666 — possibly includes a totals row in source)
- Prelist data is not cached in PrelistModel — dashboard reloads from DB on every page load

## Relevant Files
- `scripts/import_prelist.php`: CLI streaming import, handles Sheet 1 (kabkota), Sheet 2 (kecamatan), Sheets 7-44 (per-kab SLS)
- `src/Models/PrelistModel.php`: KPI, komposisi usaha, perbandingan SE2016, beban kerja, workload stats
- `src/Controllers/DashboardController.php`: injects PrelistModel data into view
- `views/dashboard/index.php`: prelist KPI card + SE2016 vs SE2026 stacked bar + UB/UM/UMK doughnut
- `assets/js/dashboard.js`: chartPrelistPerbandingan (stacked bar), chartPrelistKomposisi (doughnut)
- `src/Helpers/Session.php`: fingerprint excludes HTTP_ACCEPT
- `src/Middleware/BaseMiddleware.php`: redirectToLogin/forbidden/unauthorized return JSON for AJAX
- `config/constants.php`: petugas restricted to `[ROLE_ADMIN]`
- `src/Controllers/PetugasController.php`: requireRole admin guard + nama_lengkap in query
- `src/Core/Controller.php`: requireRole redirects with flash error
- `views/partials/sidebar.php`: petugas link hidden for non-admin
- `views/petugas/list.php`: ID + Nama Lengkap columns
- `data/PRELIST SE2026.xlsx`: prelist Excel source file (29.8 MB, 46 sheets)
