# NorthCare

**NorthCare** is a modern, secure, and feature rich healthcare practitioner directory and appointment booking platform for Northern Cyprus. It connects patients with doctors, specialists, clinics, and hospitals through a searchable public directory, real-time availability management, regulated provider onboarding, and moderated patient reviews.

Built with a mature, type-safe stack, NorthCare ships with role-based access control, privacy-aware field visibility, server-rendered Blade UI, multi-language support, and an integrated administration console — all production-ready and fully test-covered.

## Key Capabilities

- 🔍 **Searchable public directory** — filter by city, region, specialty, service, and care type with field-level privacy enforcement
- 📅 **Real-time booking & slot scheduling** — 15-minute-aligned availability, vacation overrides, double-booking prevention, and status lifecycle
- ⭐ **Dual-ratings with moderation** — doctor + service ratings, post-completion-only commenting, admin approval queue, and practitioner reporting
- 🏢 **Regulated provider onboarding** — private document uploads, admin review workflow, profile matching, and per-field public/registered/private visibility
- 🌐 **Multi-language UI** — database-backed translations, language management console, and visitor-level locale switching
- 👥 **Four-role access model** — Patients, Practitioners, Institutions, and Administrators each have isolated dashboards and permissions

## Tech Stack

| Layer        | Technology                          |
|--------------|-------------------------------------|
| Backend      | PHP 8.4+, Laravel 13                |
| Frontend     | Blade templates, Tailwind CSS 4, Vite |
| Database     | MySQL (via Eloquent ORM)            |
| Testing      | PHPUnit 12 + `RefreshDatabase`      |
| Dev Tools    | Laravel Pint (code style), Pail (logs), Pao (AI helpers), Boost |

## Features

### Multi-Role User System

Four distinct user roles with separate authentication flows and dashboards:

| Role          | Registration       | Approval Required | Dashboard Prefix |
|---------------|--------------------|-------------------|------------------|
| Patient       | Public form        | No (auto-active)  | `/dashboard`     |
| Practitioner  | Public form + docs | Yes (admin review)| `/practitioner`  |
| Institution   | Public form + docs | Yes (admin review)| `/institution`   |
| Administrator | CLI command only   | N/A               | `/admin`         |

- **Separate admin login** at `/admin/login` prevents regular accounts from escalating.
- Pending practitioners/institutions are redirected to `/account/pending-approval` until approved.
- Existing admins can create additional admin accounts from the admin dashboard.

### Provider Application & Admin Review

- Practitioners and institutions submit applications with **private document uploads** (stored in `storage/app/private`, not web-accessible).
- Administrators review each application with:
  - Full edit of all profile fields (name, category, location, contact info, description)
  - **Granular visibility control** per field: `public` / `registered-users-only` / `private`
  - **Profile matching** — assign the application to an existing published `PractitionerProfile` instead of creating a duplicate
  - Approve (creates/links profile + activates user) or reject (no profile created)
  - Download the applicant's private documents

### Admin-Managed Reference Data

The admin catalog page lets admins manage all reference data without raw SQL:

- **Locations**: Cities → Regions (one city has many regions, two-level only)
- **Categories**: Two-level hierarchy with kinds (`doctor` / `specialist` / `hospital`) — third-level children are rejected
- **Services**: Attachable to practitioner profiles with custom durations; can be deactivated
- **Service Suggestions**: Practitioners propose missing services; admins approve/reject each suggestion
- **Languages & Translations**: Full UI localization system — add languages, edit per-locale strings, and visitors switch locale via POST `/language`

Admins can also create **practitioner profiles directly** (with no linked user account) and **institution profiles with multiple physical locations**.

### Public Directory

The homepage `/` is a searchable directory. Results come **only from published database profiles** — no practitioner personal data is seeded.

**Filters available for any visitor:**
- City or Region
- Free-text search by name
- Specialty (Category)
- Specific Service offered
- Care type: `doctor` / `specialist` / `hospital` / `practitioner` (both) / `all`

**Per-field visibility rules are enforced:**
- A profile with `name: registered` visibility will not appear to unauthenticated visitors at all.
- Fields visible to `registered` users are hidden on the public listing but shown after login.

Each directory card also shows:
- Offered services with their durations (e.g., "Dental examination (45 min)")
- Weekly working schedule summary
- Upcoming leave notices

### Practitioner Availability & Scheduling

Practitioners set up their availability on their profile page. All time math uses **15-minute intervals** and is validated server-side.

**1. Weekly working windows**
- 7 days × enabled/disabled × `starts_at` / `ends_at` (HH:MM in 15-min steps)
- Stored in `availability_windows` table

**2. Availability overrides**
- Full-day leave (no times given)
- Partial-day block (e.g., 13:00–15:30 for a seminar)
- Multi-day date range (expands into one override row per day internally)
- Stored in `availability_overrides` table; practitioners can add and delete overrides

**3. Slot generation**
The model method `PractitionerProfile::generateSlotsForDate(Carbon $date, int $durationMinutes)` returns an array of `{start, end}` slots by combining:
- Weekly windows for that day-of-week
- Service duration (stepped correctly so last slot ends exactly at window end)
- Full-day vacation blocks (clears all slots)
- Partial-day blocks (removes overlapping slots only)
- Existing approved appointments (conflict removal)

### Appointment Booking

Any logged-in patient can book a practitioner:

1. Pick a practitioner → opens `/practitioners/{id}/book`
2. Select a service (changes slot duration) and a date via a 14-day preview strip
3. Choose from available time slots
4. Add optional patient notes (max 500 chars)

**Booking validations** run server-side:
- The selected slot must still be in the freshly-regenerated slot list
- The patient must not have another approved appointment overlapping the same window
- Appointment date must be today or later

**Appointment status lifecycle:**

```
pending ──approve──► approved ──(time passes end time)──► completed
   │                    │
   └──reject────►reject.└──cancel (by either party or admin)──► cancelled
```

Practitioners see three columns on their appointments page: **Pending requests**, **Approved upcoming**, and **Past appointments**. They add doctor notes when approving or rejecting.

### Ratings & Moderated Reviews

After an appointment reaches `completed` status (its end time has passed), the **patient alone** can leave exactly one comment.

**Ratings:**
- `doctor_rating` (1–5) and `service_rating` (1–5) — either or both can be left
- `getAverageRating()` returns the mean of non-null ratings
- `getRatingStars()` renders ★/☆ characters for views
- Comment text required (5–2000 chars)

**Moderation pipeline:**
1. Every new comment lands in `pending` status and shows in the admin moderation queue
2. Admin approves (`approved`, visible publicly) or rejects (`rejected`, stays hidden)
3. If a practitioner sees an approved comment they disagree with, they **report it** with a reason → goes back to admin as `is_reported = true`
4. Admin then either:
   - `dismiss_report` — clears the flag, comment stays approved
   - `take_down` — changes status to `removed`, no longer visible
5. All moderation actions record `reviewed_by`, `reviewed_at`, `reported_at`, and free-text `admin_notes`

## Project Structure

```
app/
├── Actions/ReviewProfileApplication.php       # Admin approval action (pure logic)
├── Console/Commands/CreateAdministrator.php   # CLI: northcare:admin:create
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                             # 7 admin controllers (applications, catalog, comments, languages, sessions, users, profiles)
│   │   ├── Auth/                              # Registration, login, approval status
│   │   ├── Practitioner/                      # Profile, appointments, service suggestions
│   │   ├── Institution/ProfileController.php  # Institution profile & locations
│   │   ├── AppointmentBookingController.php   # Patient booking flow
│   │   ├── AppointmentCommentController.php   # Post-appointment comments
│   │   ├── DashboardController.php
│   │   └── DirectoryController.php            # Homepage search & filter
│   ├── Middleware/
│   │   ├── EnsureAdministrator.php
│   │   ├── EnsureActivePractitioner.php
│   │   ├── EnsureActiveInstitution.php
│   │   └── SetApplicationLocale.php
│   └── Requests/StoreRegistrationRequest.php
├── Models/                                     # 18 Eloquent models
│   ├── User.php  (UserRole enum relation)
│   ├── PractitionerProfile.php  (services, windows, overrides, appointments, comments, slot generators)
│   ├── Institution.php  (locations, categories)
│   ├── ProfileApplication.php + ApplicationDocument.php
│   ├── Appointment.php + AppointmentComment.php  (completion + rating logic)
│   ├── AvailabilityWindow.php + AvailabilityOverride.php
│   ├── Service.php + ServiceSuggestion.php
│   ├── Category.php (nested 2-level), City.php, Region.php
│   └── Language.php, UiTranslation.php, ContentTranslation.php
└── UserRole.php                                # Backed enum: Patient / Practitioner / Institution / Admin

database/
├── migrations/                                 # 6 migration files (dated 0001 → 2026-09-28)
└── seeders/                                    # 7 seeders + DatabaseSeeder orchestrator

resources/views/
├── welcome.blade.php, directory.blade.php
├── auth/ (login, register, dashboard, pending-approval)
├── admin/ (dashboard, login, applications/*, catalog, comments/*, languages, profiles/*)
├── practitioner/ (profile/edit, appointments/index)
├── institution/profile/edit.blade.php
└── appointments/create.blade.php

routes/web.php                                  # 45+ named routes in 5 middleware groups
tests/Feature/
├── AccountAccessTest.php                       # 17 tests (registration → approvals → catalog → permissions)
└── PublicDirectoryAndVacancySchedulingTest.php # 8 tests  (filters → slots → availability → overrides)
```

## Getting the Code

First, get a copy of this repository onto your machine.

**Option A — Clone with Git (recommended):**

```bash
# If the repo is hosted on GitHub / GitLab / Bitbucket, replace the URL below
git clone https://github.com/your-username/northcare.git
cd northcare
```

**Option B — Download a ZIP:**
1. On the repository's web page, click the green **Code** button → **Download ZIP**
2. Extract the ZIP to a folder like `C:\Projects\northcare`
3. Open a terminal and `cd` into that folder

Once inside the project folder, continue with Local Setup below.

## Local Setup

**Requirements:** PHP 8.4+, Composer, Node.js/npm, and a running MySQL server.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
npm install
npm run build
```

Create a MySQL database named `northcare`, then set `DB_PASSWORD` (and any other local DB credentials) directly in your local `.env` file. Keep `.env` private; Git ignores it.

```powershell
php artisan migrate --seed
php artisan northcare:admin:create owner@example.com "System Owner"
php artisan serve
```

The admin command prompts for a password without echoing it. Existing administrators can create additional admin accounts from the protected dashboard.

Run `npm run dev` instead of `npm run build` when you want Vite to watch frontend changes during development.

There is also a one-shot Composer script for fresh environments:

```bash
composer run setup
```

## First Steps After Setup

Once `php artisan serve` is running (default: http://localhost:8000), try the full flow end-to-end:

1. **Public directory** — visit http://localhost:8000 . The filters (city, specialty, search, etc.) will initially show no results because no practitioner profiles are seeded.

2. **Create an admin directly from the admin dashboard** — log in at http://localhost:8000/admin/login using the account you created with `php artisan northcare:admin:create`. Then:
   - Create a practitioner profile from `/admin/profiles/create` to see it appear on the directory homepage.
   - Or browse `/admin/catalog` to explore pre-seeded cities, categories, and services.

3. **Register as a practitioner** — go to `/register`, select account type "Practitioner", fill in details, and upload a sample document. You will land on `/account/pending-approval`.

4. **Approve the application as admin** — back in the admin panel at `/admin/applications`, open the new application, fill in visibility fields, pick **decision: approve** → submit. The practitioner's account is now active and their profile is published.

5. **Book an appointment** — register a separate Patient account (no approval needed). From the directory homepage, click a practitioner → **Book**. Pick a service and available slot (the practitioner must first have availability windows set on their `/practitioner/profile` page for slots to show).

6. **Approve & rate** — log in as the practitioner, visit `/practitioner/appointments`, approve the booking. After the appointment end-time has passed (or manually set the status to `completed` via DB for testing), the patient can leave ratings and a comment from their dashboard.

7. **Moderate the review** — log back in as admin and go to `/admin/comments`. Approve the comment, or if the practitioner reports it, use the dismiss/take-down actions.

## Seeded Data

`DatabaseSeeder` runs these seeders in order:

1. `LanguageSeeder` — English (default active) + Turkish
2. `UiTranslationSeeder` — Core UI strings for both locales
3. `LocationSeeder` — Nicosia (Central), Kyrenia, Famagusta (Old Town), Lefke
4. `CategorySeeder` — Doctors (Dentist, Ophthalmologist), Specialists (Dietitian, Psychologist, Physiotherapist), Hospitals (General hospital, Private clinic)
5. `ServiceSeeder` — Service per category (Dental exam, Teeth cleaning, Eye check-up, Nutrition consultation, etc.)
6. `AdminSeeder` — Optional initial admin via `config('auth.initial_admin')`; repairs a matching existing account if the user email was registered under the wrong role

**No practitioner or institution profiles are seeded** — this is intentional; directory results are admin-created data only.

## Running Checks

```powershell
php artisan test --compact
npm run build
```

Code style:
```powershell
vendor/bin/pint
```

## Database Schema Highlights

| Table                        | Purpose                                                  |
|------------------------------|----------------------------------------------------------|
| `users`                      | + `role` (enum string), `account_status` (pending/active/rejected), `preferred_locale` |
| `profile_applications`       | Provider sign-up payload + decision fields               |
| `application_documents`      | Private file uploads linked to an application            |
| `practitioner_profiles`      | Published directory entries for practitioners            |
| `institutions` + `institution_locations` | Healthcare facilities + branch addresses |
| `services` + `practitioner_profile_service` (pivot with `duration_minutes`) | Offered services per practitioner |
| `service_suggestions`        | Practitioner-proposed new services pending admin review  |
| `availability_windows`       | Weekly recurring hours (per practitioner)                |
| `availability_overrides`     | Exceptions: full/partial day-offs, date range support    |
| `appointments`               | Bookings with status lifecycle + conflict detection      |
| `appointment_comments`       | Comments with `doctor_rating`, `service_rating`, moderation & reporting flags |
| `cities` → `regions`         | Two-level locations (FK)                                 |
| `categories` (self `parent_id`) | Two-level max (enforced in controller)                 |
| `languages` + `ui_translations` + `content_translations` | Full i18n system |

## License

MIT. Uses Laravel framework components; see the [Laravel documentation](https://laravel.com/docs) for framework licensing terms.
