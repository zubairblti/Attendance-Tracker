# Attendance Tracker CRM

An attendance-management CRM built with Laravel 12. It covers departments and reporting lines, employee accounts, versioned shift and break settings, clock-in/out, leave requests, attendance corrections, team status, timesheets, analytics, notifications, and configuration audits.

> This guide describes the current codebase. The database seeder creates only the initial Super Admin account; it does not create sample departments, employees, or attendance records.

## Contents

1. [Local installation](#local-installation)
2. [First-time setup: recommended order](#first-time-setup-recommended-order)
3. [Roles and access](#roles-and-access)
4. [Attendance configuration](#attendance-configuration)
5. [Daily attendance and breaks](#daily-attendance-and-breaks)
6. [Leave workflow](#leave-workflow)
7. [Attendance and break corrections](#attendance-and-break-corrections)
8. [Team Status and employee details](#team-status-and-employee-details)
9. [Admin pages and reports](#admin-pages-and-reports)
10. [Operations, tests, and troubleshooting](#operations-tests-and-troubleshooting)

## Local installation

Requirements: **PHP 8.2+**, Composer, **MySQL**, and Node.js/npm. Enable the PHP MySQL/PDO extension and the standard extensions required by Laravel. The provided `.env.example` uses MySQL; database-backed sessions, cache, and queues also require the migrations.

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
```

On Windows PowerShell, use `Copy-Item .env.example .env` instead of `cp`. Create an empty MySQL database, then set the following values in `.env` to match your local installation:

```dotenv
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Karachi
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=quadroots_tracker
DB_USERNAME=your_mysql_user
DB_PASSWORD=your_mysql_password
```

The database name and credentials above are examples; use the values for your own MySQL server. Then run:

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve
```

Open `http://127.0.0.1:8000/login`. On a fresh database, the seeded account is:

| Email | Password | Role |
| --- | --- | --- |
| `admin@example.com` | `password` | Super Admin |

**Change this password immediately** from **Profile & password**. The seeder uses `updateOrCreate`, so running `php artisan db:seed` again will reset this account's password to the seeded value. Do not commit `.env`; use secure credentials and `APP_DEBUG=false` in production.

Run `npm run dev` for development or `npm run build` for deployment. Some CRM assets are also served directly from `public/`. In separate processes, run `php artisan queue:work` for queued jobs and `php artisan schedule:work` for the automatic clock-out scheduler during local development. In production, configure Laravel's scheduler to run every minute.

## First-time setup: recommended order

Set up a fresh CRM in this order:

1. **Sign in and secure the Super Admin account.** Change its password. From the account menu, open **CRM customization** to set the organization name, tagline, colors, and optional logo. The branding appears on the login page and in the CRM navigation.
2. **Create departments.** Open **Departments** (`/admin/departments`) and add a name and unique short code, such as `Sales / SALES` or `Web Development / WEB`. Keep the department active. You can leave the HOD selection empty until HOD accounts exist.
3. **Create leadership accounts.** Open **Employees → Add new user** (`/admin/employees/create`) and create Director, HOD, and Manager users. Set each person's role, department, reporting Manager/HOD, initial shift time, password, and **Currently employed** status. For HOD users, select their managed departments in **HOD departments**. You can also assign HODs while editing a department.
4. **Create employee accounts.** Use the same form for Executive/Employee users. Assign each employee to the correct department and select **Reports to manager/HOD**. A correct direct manager is important for team visibility and leave approval. If an employee reports to an HOD instead, verify that reporting relationship and the HOD's department assignment.
5. **Configure working days and shifts.** In **Configurations** (`/admin/configurations`), set working weekdays, shift start/end times, grace period, and early clock-in window. Choose an **Effective from** date. Start with organization-wide rules, then add department or individual exceptions where needed.
6. **Configure breaks.** Select **Break options** on the Configurations page. Add each break's title, optional duration in minutes, and Active status, then save. The employee dashboard automatically chooses an icon from the title; unfamiliar titles receive a default icon.
7. **Review attendance rules.** Configure minimum full-day minutes if required. Automatic clock-out follows the effective shift end time.
8. **Verify the setup.** Sign in as an employee and check the Dashboard's shift and break options. Sign in as a Manager/HOD and check Team Status and Leave Approvals. Use the Super Admin account to review Timesheets, Analytics, and Audit History.

### Example reporting hierarchy

```text
Director
  Sales HOD (assigned to the Sales department)
    Sales Manager
      Sales Executive
```

A **department** is an organizational group; **Reports to manager/HOD** is the direct reporting relationship. Set both. The Departments page derives its Manager list from Manager-role users in the department, while HODs come from department-head assignments. A department with assigned employees cannot be deleted until those employees are moved.

## Roles and access

| Role | Typical access |
| --- | --- |
| Super Admin | CRM setup, employees, departments, configurations, permissions, audits, and company reports. This account does not record employee attendance. It can monitor leaves but cannot approve or reject them. |
| Admin / Manage Admin | Employee and report management, plus the final stage of attendance corrections under the default permissions. Not a final leave approver. |
| QA Admin | Attendance reports and request viewing under the default permissions. |
| Director | Company-wide active-employee details in Team Status; may decide a leave when it is within the Director's reporting relationship. |
| HOD | Their own details and the Manager/Executive records in managed departments; may decide leaves for employees they manage. |
| Manager | Their own details and direct Executive reports; may decide their reports' leaves. Cannot view their HOD's or a peer Manager's details. |
| Executive/Employee | Personal dashboard, leaves, edit requests, calendar, notifications, and profile. |

The Super Admin can edit the role-permission matrix at **Role Permissions** (`/admin/permissions`). Backend authorization also protects restricted actions. Turning off **Currently employed** prevents the account from signing in again. Super Admin accounts are excluded from the employee directory and its Unemploy/Delete actions.

## Attendance configuration

Only the Super Admin can change **Configurations**. Each version has an **Effective from** date. New effective rules are versioned rather than rewriting the configuration snapshot stored with historical attendance. **Audit History** shows the actor, date, and old/new values; affected users receive in-app notifications.

Configuration precedence is **individual employee → department → entire organization → built-in default**. An explicit employee rule takes priority over a department or global rule.

### Shift timing

Set **Shift starts**, **Shift ends**, **Grace period (minutes)**, and **Early clock-in (minutes)**. For example, with a 09:00 start, 17:00 end, 10-minute grace period, and 30-minute early window, an employee can clock in from 08:30; a clock-in after 09:10 is late. Overnight shifts can have an end time earlier than the start time, such as 18:00 to 03:00. Clock-ins outside the active shift window or repeated within the same shift are rejected.

The employee form's **Shift time** is an initial/fallback value. An effective shift configuration takes precedence. Built-in defaults use Monday–Friday working days and a shift starting at the user's Shift time. Explicit global or department rules are recommended for real use.

### Working days

Select working weekdays from Monday through Sunday. Normal clock-in is not available on a non-working day. Create department-scoped versions if teams have different weekends.

### Break options

Each break has a title, optional duration, and Active toggle. Only Active breaks appear under **Take a break**. An empty duration means no configured fixed limit. The current backend does **not** automatically end a break when that duration passes; the employee must select **End break**. A break titled exactly `Meeting` is not subtracted from worked time; other breaks are. Break start/end timestamps are saved with the attendance record.

### Attendance rules and automatic clock-out

**Minimum full-day duration** is part of attendance configuration. The scheduled `attendance:auto-clock-out` command closes open shifts at their effective shift end time. It will not run automatically unless the scheduler is running. A manual clock-out also closes any active break.

## Daily attendance and breaks

After signing in, employees use the **Dashboard** (`/`):

1. Select **Clock in now** during the active shift window. The system checks the effective working day, shift window, and duplicate clock-ins, then marks the record `On time` or `Late`.
2. The worked-time timer updates every second in `HH:MM:SS` format. It pauses during a non-Meeting break.
3. Select **Take a break**, choose an active configured break, and later select **End break**. Only one break can be active at a time.
4. Select **Clock out** at the end of the shift. Net worked time and break duration are saved; any active break is closed.
5. Review **This week's activity**, **My Calendar** (`/my-calendar`), or an individual daily attendance record.

Worked time is elapsed time from clock-in to clock-out minus non-Meeting breaks. Reports distinguish late arrivals and missing attendance. Employees should submit a correction request rather than directly editing a clock-in or break record.

## Leave workflow

From the employee Dashboard, select **Request leave**:

1. Choose `Casual`, `Medical`, `Half day`, or `Emergency`.
2. Enter the start and end dates and an optional reason. The end date cannot precede the start date.
3. **Medical certificate** appears only for Medical leave. It is **optional** and accepts JPG, JPEG, PNG, or PDF files up to 5 MB.
4. Submit the request. It appears in **My Leaves** (`/my-leaves`) with a `pending_manager` status.
5. The employee's direct Manager or assigned department HOD uses **Leave Approvals** (`/leaves`) to **Approve** or **Reject**. This is the **final** decision: the status becomes `approved` or `rejected` immediately. There is no Admin approval stage for leaves.
6. The history shows who approved/rejected the request and when. The employee receives a decision notification. Admin and Super Admin users can monitor requests and outcomes, but cannot make leave decisions.

**Leave Summary** (`/admin/leave-summary`) shows approved casual, medical, and half-day usage. An employee's Team Status detail page also shows approved leave days for the selected period. A migration converts older manager-approved `pending_admin` leave records to `approved`.

## Attendance and break corrections

Employees submit correction requests through **Edit Requests** (`/my-edit-requests`) for an incorrect clock-in, forgotten break start/end, or a clock-tower problem. The requested time must fall within the current week, and each employee can submit at most **three requests per month**. A reason is required. Break-related request types expose break start/end and break-reason fields.

This workflow is **different from leave approval**:

1. A request starts as `pending_manager`.
2. A Manager/HOD reviews it in **Edit Approvals** (`/edit-requests`). Manager approval moves it to `pending_admin`; Manager rejection is final.
3. An Admin/finalizer makes the final decision. On final approval, the clock-in or break correction is applied to the attendance record. Correcting a clock-in can recalculate late/on-time status and worked/break duration.
4. Decision history shows the actor and stage. Repeating an action on an already processed request produces a friendly error.

Approval tables show only **Approve** and **Reject** buttons; they do not display optional-note inputs. Manager-stage correction requests older than one week cannot be changed.

## Team Status and employee details

**Team Status** (`/team-status`) shows live states (`Working`, `On break`, `Clocked out`, `Not in`) and supports employee search, department, and status filters. Clicking an employee card opens their attendance detail page.

Access follows the reporting hierarchy: a Manager can see themselves and direct Executive reports; an HOD can see themselves and lower roles in managed departments; Directors and Super Admins can see active employees company-wide. Opening an unauthorized employee URL directly returns `403`.

On the detail page, choose a **Month**, **Last 7 days**, **Last 30 days**, or **Custom dates**. There is also a status filter and a 20/50/100 records-per-page selector. Cards reflect the selected period's attendance days, net working hours, on-time arrivals, late arrivals, and approved leave days. Paginated daily rows show clock-in/out, break count/duration, worked time, and status.

## Admin pages and reports

| Page | Purpose |
| --- | --- |
| Admin Dashboard (`/admin`) | Active employee count, working now, pending leaves/edits, and live attendance. Excludes Super Admin from employee counts. |
| Employees (`/admin/employees`) | Search and filter by department or Manager/HOD; create, edit, employ/unemploy, or delete users. |
| Departments (`/admin/departments`) | Manage department names/codes, HODs, active state, and employee/Manager counts. |
| Timesheets (`/admin/timesheets`) | Filter daily records by date, employee, department, or status; show 20/50/100 rows per page. |
| Analytics (`/admin/analytics`) | Today/week/month/custom periods, employee/department/status filters, matching KPI cards, daily visual, and filtered CSV export. |
| Missing Attendance (`/admin/missing-attendance`) | Active employees without a clock-in on the selected date, plus a late/absent CSV export. |
| Leave Summary (`/admin/leave-summary`) | Approved leave usage by employee. |
| Configurations (`/admin/configurations`) | Super Admin setup for effective-dated shifts, working days, breaks, and attendance rules. |
| Audit History (`/admin/audits`) | Who changed a configuration, when, and its previous/new values. |
| Role Permissions (`/admin/permissions`) | Super Admin role-permission matrix. |
| Notifications (`/notifications`) | Request/configuration updates, unread count, mark-as-read, and deletion. |
| Profile (`/profile`) | Update name, phone/address, profile image, and password. |

Date, month, and date/time fields use Flatpickr. Success and error messages appear as SweetAlert notifications. VAPID keys for browser push are optional; in-app notifications are still stored without them.

## Operations, tests, and troubleshooting

```bash
php artisan test
php artisan route:list
php artisan schedule:list
php artisan attendance:auto-clock-out
```

The auto-clock-out command can be run manually, but its normal operation depends on a scheduler running every minute. Run `php artisan storage:link` so uploaded logos, avatars, and medical certificates can be served. Check `APP_URL`, MySQL credentials, timezone, and production mail/queue settings in `.env`.

Common checks:

- **Cannot sign in:** Verify the email/password and the account's **Currently employed** setting. The seeder creates only the initial Super Admin.
- **Cannot clock in:** Check the effective working day, shift start/end, early window, and whether a record already exists for that shift.
- **A break option is missing:** Check the effective break configuration, its scope/date, and the option's Active toggle.
- **A Manager cannot see a request or team member:** Check the employee's **Reports to manager/HOD** setting, department, and HOD department assignment.
- **Leave appears to need Admin approval:** Leave approval ends with the Manager/HOD decision. `pending_admin` belongs to the attendance-correction workflow, not the current leave workflow.
- **Automatic clock-out is not running:** Check the scheduler and the effective shift end time.
- **Uploads cannot be opened:** Check `php artisan storage:link`, `storage/app/public` permissions, and `APP_URL`.

An optional legacy Rails import command, `php artisan legacy:import`, is available with separate `LEGACY_*` settings in `.env.example`. It modifies CRM data; back up the database and verify the import in a staging environment first.
