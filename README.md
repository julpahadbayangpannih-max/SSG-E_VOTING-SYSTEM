# JRMSU E-Voting System

<!-- TODO: replace <your-github-username> below with the actual GitHub org/user this repo is pushed to, so the badge resolves. The workflow it points to (.github/workflows/tests.yml) now exists in this repo. -->
![Tests](https://github.com/<your-github-username>/jrmsu-evoting/actions/workflows/tests.yml/badge.svg)

🔗 **Live Demo:** [jrmsu-ssg-evoting.onrender.com](https://jrmsu-ssg-evoting.onrender.com)

A web-based electronic voting system built for Jose Rizal Memorial State University (JRMSU) Siocon Campus SSG Elections. Built with Laravel 12, it handles the full election workflow — from voter registration and admin approval, to ballot casting and live results tabulation.

---

## Screenshots

> ⚠️ **TODO before publishing:** the images below are referenced but not yet present in this copy of the repo. Capture these 8 screenshots from a running instance and place them in a `screenshots/` folder at the project root (same filenames as below) before pushing to GitHub, or the table will render as broken images.

| Voter Login (Student ID step) | Admin Login |
|---|---|
| ![Voter Login](screenshots/ss1-voter-login.png) | ![Admin Login](screenshots/ss2-admin-login.png) |

| Admin Dashboard | Official Ballot |
|---|---|
| ![Admin Dashboard](screenshots/ss3-admin-dashboard.png) | ![Ballot](screenshots/ss4-ballot.png) |

| Ballot Confirmation | Live Results (Voter View) |
|---|---|
| ![Confirm Ballot](screenshots/ss5-confirm.png) | ![Live Results](screenshots/ss6-results.png) |

| Admin Results Page | Voters Management |
|---|---|
| ![Admin Results](screenshots/ss7-admin-results.png) | ![Voters Management](screenshots/ss8-voters-management.png) |

---

## Features

### Admin Panel
- Secure admin login (rate-limited to 5 attempts per minute), with optional TOTP two-factor authentication and one-time recovery codes
- Voter registration management — view pending registrations, approve or reject per voter
- Manually add voters directly from the admin panel, or bulk-import via CSV (auto-generates temp passwords, downloadable as credentials CSV)
- Multi-election support — create, edit, open/close, and switch between election cycles
- Manage election positions (with configurable max winners per position)
- Manage candidates with photo upload support
- Configure election schedule — set exact start and end datetime
- Live election results with auto-refresh every 10 seconds
- Export results as CSV or printable PDF
- White-label branding — customize school name, short name, tagline, and logo (reflected across admin panel, voter portal, and PDF export)
- License activation gate for distributing the system to other schools/clients
- Reset all votes (with audit trail)
- Full audit log — every action is recorded with actor, timestamp, IP, and a tamper-evident hash chain (`php artisan audit:verify`)
- Closing an election freezes a Merkle root over every ballot's cryptographic commitment, certifying the result set as of that moment — any later addition, removal, or edit to the ballots table is detectable by recomputing the root

### Voter Portal
- Self-registration with Student ID, name, and course — no password required at signup
- Admin approval required before a voter can log in — approval auto-generates a one-time temporary password, shown once to the admin to relay to the student
- Time-gated ballot — voting only opens within the configured election window
- One-person, one-vote — double-vote attempts are blocked and logged
- Ballot confirmation modal before final submission (irreversible)
- Anonymous voter receipt code issued after voting — proof of participation without revealing the ballot
- End-to-end verifiable: each ballot is bound to a public SHA-256 commitment (election + receipt code + choices) at cast time; the `/verify` page lets a voter re-enter their own receipt code and choices to prove their ballot was counted exactly as cast, without revealing it to anyone else
- Live results visible immediately after voting, updates every 10 seconds

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 (PHP 8.2+) |
| Database | MySQL |
| Frontend | Blade templates, Vite |
| Auth | Dual session guards — Admin + Voter (separate login flows), TOTP 2FA for admins |
| CI/CD | GitHub Actions — auto-runs Pint + full test suite on every push/PR |
| Deployment | Render (web app) + Railway (MySQL database) |

---

## Project Structure

```
app/
├── Console/
│   └── Commands/           # BackupDatabase, CleanupAuditLogs, GenerateLicenseKey,
│                            # SystemIntegrityCheck, VerifyAuditChain
├── Http/
│   ├── Controllers/
│   │   ├── Admin/          # AuthController, TwoFactorController, DashboardController,
│   │   │                   # VoterController, PositionController, CandidateController,
│   │   │                   # ElectionController, ResultController, SettingController,
│   │   │                   # AuditLogController
│   │   ├── Voter/          # AuthController (login, register, first-time password),
│   │   │                   # VoterDashboardController (ballot, vote submit, live results)
│   │   ├── LicenseController.php   # Self-hosted license activation gate
│   │   └── VerifyController.php    # Public "verify my vote" receipt/commitment check
│   ├── Middleware/         # AuthenticateAdmin, AuthenticateVoter, CheckLicense
│   └── Traits/
│       ├── LogsActivity.php            # Shared tamper-evident audit logging
│       └── ResolvesCurrentElection.php # Shared "which election is the admin managing" helper
├── Services/                # MerkleTree, SimpleCaptcha, TwoFactorAuthenticator
├── Support/                  # Branding (white-label settings), License
├── Models/                   # Admin, Voter, Candidate, Position, Election, Vote, Ballot,
│                              # Setting, AuditLog, AuditChainState, AuditChainCheckpoint
database/
├── migrations/               # voters, admins (+2FA), positions, candidates, votes, settings,
│                              # audit_logs (+tamper-evidence), elections, ballots
└── seeders/
    └── AdminSeeder.php       # Seeds the admin account from env vars — no default credentials
routes/
└── web.php                   # /admin/*, /voter/*, /license/*, /verify — root redirects to voter login
```

---

## Database Schema

- **admins** — username, bcrypt password, name, plus encrypted `two_factor_secret` / `two_factor_recovery_codes` and `two_factor_confirmed_at` (null = 2FA off)
- **voters** — student_id, name, course, password (nullable until admin approval sets one), `is_approved`. There is no `has_voted` column — voting status is per-election, answered by whether a `ballots` row exists (see below)
- **elections** — title, `start_time`, `end_time`, `status` (`draft` / `open` / `closed` — only one election may be `open` at a time), plus `merkle_root`, `merkle_leaf_count`, `results_locked_at` (set when the election is closed, see Security)
- **positions** — name, `max_votes` (supports multi-winner positions), `election_id` (FK)
- **candidates** — name, party_list, position_id (FK), image path, `election_id` (FK)
- **votes** — position_id, candidate_id, election_id. **No `voter_id` column** — this is intentional ballot secrecy, see Security below. Choices are anonymous by design; only the `ballots` table (below) proves *that* someone voted, never *what* they voted for
- **ballots** — voter_id, election_id, `voted_at`, `receipt_code` (shown to the voter once), `commitment` (SHA-256 hash binding election + receipt code + choices). Unique constraint on `(voter_id, election_id)` is what actually enforces one-vote-per-election at the DB level
- **settings** — generic key-value store (`setting_key` / `setting_value`) used for white-label branding (school name, short name, tagline, logo path) and license activation state
- **audit_logs** — actor type, actor ID, action, IP address, JSON metadata, timestamp, plus `prev_hash` / `hash` for the tamper-evident chain
- **audit_chain_state** — single-row table holding the latest chain hash, locked during writes so concurrent audit-log entries can't fork the chain
- **audit_chain_checkpoints** — records a resume-point hash whenever `audit-logs:cleanup` legitimately deletes old entries, so scheduled cleanup isn't mistaken for tampering by `audit:verify`

---

## Voter Login Flow

Voters never choose their own password at signup — this closes an account-takeover gap where the first person to type in someone else's Student ID could otherwise claim that account:

1. Student registers with Student ID, name, and course only — no password field at this step
2. Admin reviews and approves the registration (or adds/imports the voter directly, which auto-approves)
3. Approval generates a one-time temporary password server-side, returned once to the admin to relay to the actual student through an official channel
4. The voter logs in with their Student ID and that temporary password

---

## Setup (Local)

**Requirements:** PHP 8.2+, Composer, Node.js, MySQL

```bash
# 1. Clone the repo
git clone <repo-url>
cd jrmsu-evoting

# 2. Install dependencies
composer install
npm install

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Set DB credentials in .env
DB_HOST=127.0.0.1
DB_DATABASE=jrmsu_evoting
DB_USERNAME=root
DB_PASSWORD=

# 5. Run migrations and seed admin
php artisan migrate
php artisan db:seed

# 6. Link public storage (required for uploaded branding logos to be servable)
php artisan storage:link

# 7. Build assets and serve
npm run build
php artisan serve
```

There is no default admin account. `AdminSeeder` reads `ADMIN_USERNAME`, `ADMIN_PASSWORD` (min. 8 characters), and `ADMIN_NAME` from your `.env` and refuses to seed anything if they're missing — set these before running `php artisan db:seed`.

> **Note on local/non-Render setups:** unless you mount a persistent volume yourself, anything written to `storage/app/public` (candidate photos, the school logo) lives on local disk only and won't survive a container rebuild. The production `render.yaml` in this repo already mounts a persistent disk at that exact path — see the Deployment section below — so this only matters for custom/self-hosted deployments.

---

## Testing

The test suite covers ballot integrity, mass-assignment protection, 2FA, the tamper-evident audit chain, CRUD flows, and CAPTCHA — 1,200+ lines across 8 feature test files. Tests run against an in-memory SQLite database, so no separate test DB setup is needed.

```bash
php artisan test
```

Code style is enforced with [Laravel Pint](https://laravel.com/docs/pint):

```bash
vendor/bin/pint --test   # check only
vendor/bin/pint          # auto-fix
```

Both run automatically on every push and pull request via GitHub Actions — see [`.github/workflows/tests.yml`](.github/workflows/tests.yml).

---

## Deployment (Render + Railway)

```
APP_ENV=production
APP_KEY=<generated>
APP_URL=https://your-app.onrender.com

DB_HOST=<railway-internal-host>   # use internal host, not public TCP proxy
DB_PORT=3306
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=<railway-password>
```

> Use Railway's **internal hostname** when deploying on Render — the public TCP proxy causes connection issues.

> **Storage:** `render.yaml` mounts a 1GB persistent disk at `storage/app/public`, so candidate photos and the branding logo survive redeploys and restarts on Render. Nothing else under `storage/` needs to persist — sessions use `SESSION_DRIVER=database` and logs go to stderr.

---

## Maintenance / Scheduled Tasks

Three background jobs are defined in `routes/console.php` and run automatically inside the Docker image via `php artisan schedule:work` (see `docker/supervisord.conf`) — no separate cron setup needed:

| Task | Schedule | What it does |
|---|---|---|
| `audit-logs:cleanup --days=365` | Weekly, Sunday 2:00 AM | Deletes audit log entries older than the retention period, recording a chain checkpoint first so `audit:verify` can still tell "legitimate cleanup" apart from tampering |
| `audit:verify` | Daily, 3:00 AM | Re-checks the audit log hash chain; a failure here means a row was edited or deleted outside the normal app flow. Set `ADMIN_ALERT_EMAIL` in `.env` to get emailed on failure |
| `db:backup --keep=14` | Daily, 1:00 AM | Dumps the database to `storage/app/backups`, keeping the 14 most recent |

> **Note:** the persistent disk mounted by `render.yaml` covers `storage/app/public` only (uploads), not `storage/app/backups` — database dumps still live on Render's ephemeral container disk and are lost on redeploy/restart. For real disaster recovery, sync `storage/app/backups` to off-site storage (S3, another server, etc.) as well; the scheduled `db:backup` alone is not enough on its own on Render.

---

## Security

- All passwords hashed with bcrypt
- Login routes rate-limited (5 attempts/min for both admin and voter)
- Optional TOTP-based two-factor authentication for admin accounts, with single-use recovery codes
- Vote submission server-side validates that each candidate belongs to the correct position
- Overvote detection — aborts ballot if selected candidates exceed `max_votes` for any position
- Ballot secrecy by design — `voter_id` is not stored on the `votes` table, so a completed ballot cannot be traced back to the voter who cast it
- End-to-end verifiability — each ballot gets a SHA-256 commitment (election + secret receipt code + choices) at cast time; closing an election freezes a Merkle root over every commitment, so any post-certification tampering with the `ballots` table is detectable by recomputing the root. A voter can later confirm their own ballot was counted via `/verify`, without exposing it to anyone else
- Tamper-evident audit log — each entry is chained to the previous one via hash, so any retroactive edit or deletion breaks the chain and is detectable via `php artisan audit:verify`
- Every sensitive action is recorded in the audit log (logins, votes, resets, approvals)
- Double-voting is closed at the database level too — `ballots` has a unique constraint on `(voter_id, election_id)`, on top of the row-locked transaction that checks it server-side

---

## About This Project

Built independently as a personal project — for practicing Laravel and as a portfolio piece for freelance work. The goal was to go beyond a basic CRUD app: real dual-guard authentication, server-side vote integrity checks, audit logging, and actual deployment on live infrastructure.

---

## Author

**Julfahad** — Freelance Web Developer | Laravel / PHP
