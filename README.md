# Attendance Tracker CRM

Laravel 12 par bana hua attendance-management CRM. Is mein departments aur reporting hierarchy, employee accounts, effective-date wali shift/break settings, clock-in/out, leaves, attendance corrections, team status, timesheets, analytics, notifications aur audit history shamil hain.

> Yeh guide current codebase ke mutabiq hai. Seeder sirf initial Super Admin account banata hai; demo employees, departments ya attendance records seed nahi hotay.

## Table of contents

1. [Local setup](#local-setup)
2. [First-time setup: recommended order](#first-time-setup-recommended-order)
3. [Roles aur access](#roles-aur-access)
4. [Attendance configuration](#attendance-configuration)
5. [Rozana attendance aur breaks](#rozana-attendance-aur-breaks)
6. [Leave workflow](#leave-workflow)
7. [Attendance/break correction workflow](#attendancebreak-correction-workflow)
8. [Team Status aur employee detail](#team-status-aur-employee-detail)
9. [Admin reports aur baqi pages](#admin-reports-aur-baqi-pages)
10. [Operations, tests aur troubleshooting](#operations-tests-aur-troubleshooting)

## Local setup

Requirements: PHP **8.2+**, Composer, PostgreSQL, aur Node.js/npm. Database, sessions, cache aur queue `.env` defaults mein PostgreSQL/database drivers use karte hain. PHP ke relevant PostgreSQL aur common Laravel extensions enabled hon.

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
```

Windows PowerShell mein `.env` copy karne ke liye `Copy-Item .env.example .env` use karein. `.env` mein apne PostgreSQL server ke `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` aur local `APP_URL` set karein. App ka default timezone `Asia/Karachi` hai; deployment ke timezone ke mutabiq `APP_TIMEZONE` verify karein.

```bash
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve
```

Browser mein `http://127.0.0.1:8000/login` kholein. Fresh seed account:

| Email | Password | Role |
| --- | --- | --- |
| `admin@example.com` | `password` | Super Admin |

**Seed password ko pehli login ke baad Profile & password se zaroor change karein.** Seeder ko dobara chalane par same admin account update hota hai; isliye password change ke baad `db:seed` dobara chalane se pehle seeder behavior ka khayal rakhein. Production mein `.env` ko commit na karein, `APP_DEBUG=false` rakhein aur secure credentials use karein.

Frontend ke liye `npm run dev` (development) ya `npm run build` (deployment) chalayein. Application ke kuch CRM styles/scripts `public/` se bhi serve hote hain. Notifications/other queued jobs ke liye alag process mein `php artisan queue:work` chalayein. Automatic clock-out scheduler ke liye `php artisan schedule:work` local environment mein chalayein; production mein Laravel scheduler ka cron har minute run karna hoga.

## First-time setup: recommended order

Fresh installation mein data ko is sequence mein banayein:

1. **Super Admin login aur branding:** Login karke Profile & password se password badlein. Account menu → **CRM customization** se organization name, tagline, colors aur optional logo set karein. Branding login page aur CRM navigation par apply hoti hai.
2. **Departments:** **Departments** (`/admin/departments`) par department name aur unique short code banayein; maslan `Sales / SALES`, `Web Development / WEB`. Department ko active rakhein. Abhi HOD account na bana ho to HOD selection khali chhor sakte hain.
3. **Leadership accounts:** **Employees → Add new user** (`/admin/employees/create`) se Director, HOD aur Manager accounts banayein. Har account ka role, department, reporting Manager/HOD, initial shift time, password aur **Currently employed** set karein. HOD ke liye **HOD departments** multi-select se uske managed departments assign karein. Department edit screen par bhi HOD assign kiya ja sakta hai.
4. **Employees:** Isi form se Executive/Employee accounts banayein. Har employee ko sahi department aur **Reports to manager/HOD** assign karein. Direct Manager select karne se team visibility aur approval workflow sahi kaam karta hai. Manager na ho to relevant HOD ka reporting relation/department assignment verify karein.
5. **Working days aur shift:** **Configurations** (`/admin/configurations`) mein pehle working weekdays, phir shift start/end, grace period aur early clock-in window set karein. `Effective from` date select karein. Organization-wide rule pehle banayein; department/employee exceptions baad mein.
6. **Break options:** Isi Configurations page mein **Break options** type select karein. Har break ka title, optional minutes aur Active state set karein; phir save karein. Naye title ka icon employee dashboard par title ke mutabiq automatically show hota hai; unknown title par default icon use hota hai.
7. **Attendance rule:** Zarurat ho to minimum full-day minutes configure karein. Auto clock-out effective shift ke end time ko follow karta hai.
8. **Verify:** Employee account se login karke Dashboard par current shift/break options check karein. Manager/HOD se Team Status aur Leave Approvals visibility check karein. Super Admin se Timesheets/Analytics aur Audit History dekh kar setup verify karein.

### Department aur reporting hierarchy ka practical example

```text
Director
└── Sales HOD (Sales department ka head)
    └── Sales Manager
        └── Sales Executive
```

Department ek organizational group hai; `Reports to manager/HOD` direct reporting relation hai. Dono ko set karna zaroori hai. **Departments** page par Managers us department ke Manager-role users se derive hote hain; HODs department-head assignment se aate hain. Employees assigned hon to department delete nahi ho sakta; pehle employees ko move karein.

## Roles aur access

| Role | Typical use |
| --- | --- |
| Super Admin | CRM setup, departments/employees, configurations, permissions, audits, company reports. Is account ki employee attendance record nahi hoti. Leaves ko monitor kar sakta hai, approve/reject nahi. |
| Admin / Manage Admin | Employee/report management aur attendance correction ka final stage, assigned permissions ke mutabiq. Leave approval ka final approver nahi. |
| QA Admin | Attendance reports aur request viewing, default permissions ke mutabiq. |
| Director | Team Status mein active employees ki company-wide detail dekh sakta hai; reporting relation ki basis par leave decision kar sakta hai. |
| HOD | Apni detail aur managed department ke Manager/Executive records dekh sakta hai; assigned department ki leaves approve/reject kar sakta hai. |
| Manager | Apni detail aur direct Executive reports dekh sakta hai; apne reports ki leaves approve/reject kar sakta hai. Apne HOD/peer Manager ki detail nahi dekh sakta. |
| Executive/Employee | Apni dashboard, leaves, edit requests, calendar aur profile use karta hai. |

Super Admin **Role Permissions** (`/admin/permissions`) se role permissions configure kar sakta hai. Role-specific access ke liye UI ke saath backend authorization bhi use hoti hai. `Currently employed` off karne par account naye login ke liye eligible nahi rehta. Super Admin employee directory mein show nahi hota aur us par Unemploy/Delete actions available nahi hain.

## Attendance configuration

**Configurations** sirf Super Admin change karta hai. Har version ki `Effective from` date hoti hai; purani attendance ke applied configuration snapshot ko badalne ke bajaye naya effective rule future dates par use hota hai. Audit History mein previous/new values, date aur actor milte hain. Affected users ko in-app notification jati hai.

Configuration precedence: **individual employee → department → entire organization → built-in default**. Kisi employee ke liye explicit rule ho to woh department/global rule se pehle apply hota hai.

### Shift timing

`Shift starts`, `Shift ends`, `Grace period (minutes)` aur `Early clock-in (minutes)` set karein. Example: start `09:00`, end `17:00`, grace `10`, early window `30`. Employee 08:30 se active shift window mein clock-in kar sakta hai; 09:10 ke baad clock-in late mark ho sakta hai. Overnight shift mein end time start se pehle ho sakta hai, maslan 18:00 → 03:00. Shift ke bahar ya same shift mein dobara clock-in reject hota hai.

Employee form ka **Shift time** initial/fallback value hai. Effective shift configuration available ho to woh use hoti hai. Built-in fallback working days Monday–Friday hain aur fallback shift user ke Shift time se start hoti hai; production ke liye explicit organization/department shift configure karna behtar hai.

### Working days

Monday–Sunday checkboxes se working weekdays select karein. Non-working day par normal clock-in allowed nahi hota. Different departments ke weekends alag hon to department-scoped version banayein.

### Break options

Break title, optional duration aur Active toggle set karein. Sirf Active options employee ke **Take a break** dialog mein dikhte hain. Duration optional hai; blank ka matlab koi configured fixed limit nahi. Current backend selected duration par automatic break end enforce nahi karta—employee ko **End break** press karna hota hai. `Meeting` break worked duration se subtract nahi hoti; baqi non-Meeting breaks subtract hoti hain. Break start/end timestamps attendance record ke saath save hote hain.

### Attendance rules aur auto clock-out

`Minimum full-day duration` field attendance configuration ka hissa hai. Open shifts ko close karne ke liye `attendance:auto-clock-out` scheduled command effective shift end time use karti hai. Scheduler band ho to automatic close nahi chalega. Manual Clock out ke waqt active break close ho jati hai.

## Rozana attendance aur breaks

Employee login ke baad **Dashboard** (`/`) par:

1. Shift window mein **Clock in now** karein. System effective working day, shift window aur duplicate clock-in check karta hai. Status `On time` ya `Late` set hota hai.
2. Worked timer `HH:MM:SS` mein har second update hota hai. Non-Meeting break ke dauran worked timer pause hota hai.
3. Zarurat par **Take a break** → configured break title select karein. Ek waqt mein ek hi active break allowed hai. **End break** se working state resume hoti hai.
4. Shift ke end par **Clock out** karein. Net worked time aur break duration save hoti hain. Agar break active ho to clock-out usko bhi close karta hai.
5. **This week's activity**, **My Calendar** (`/my-calendar`) ya daily attendance detail se apne records dekhein.

Worked time clock-in se clock-out tak ka elapsed time minus non-Meeting breaks hai. Missing attendance aur Late status ko reports mein alag dikhaya jata hai. Clock-in ya break mein galti ho to direct attendance record edit karne ke bajaye correction request use karein.

## Leave workflow

Employee Dashboard se **Request leave** khole:

1. `Casual`, `Medical`, `Half day` ya `Emergency` select karein.
2. Start/end dates aur optional reason enter karein. End date start se pehle nahi ho sakti.
3. Sirf **Medical** select karne par Medical Certificate field show hoti hai. Certificate **optional** hai; JPG, JPEG, PNG ya PDF, maximum 5 MB.
4. Submit karne ke baad request **My Leaves** (`/my-leaves`) mein `pending_manager` status ke saath show hoti hai.
5. Employee ka direct Manager ya assigned department HOD **Leave Approvals** (`/leaves`) se **Approve** ya **Reject** karta hai. Decision **final** hai: seedha `approved` ya `rejected`; Admin approval ka extra stage nahi.
6. History mein approver/rejector ka role, naam aur waqt show hota hai. Employee ko decision notification milti hai. Super Admin/Admin request aur outcome dekhte hain, lekin leave decision buttons/permission nahi hoti.

**Leave Summary** (`/admin/leave-summary`) approved casual, medical aur half-day counts dikhata hai. Team member attendance detail mein selected period ke approved leave days ka card bhi hai. Old manager-approved `pending_admin` records migration se approved state mein convert kiye gaye hain.

## Attendance/break correction workflow

**Edit Requests** (`/my-edit-requests`) se employee clock-in, missed break start, missed break end ya clock tower problem ki correction request submit karta hai. Requested time current week mein hona chahiye; maximum **3 edit requests per month** hain. Reason required hai. Break-related type par break start/end aur type/reason fields milte hain.

Yeh workflow **leave se alag** hai:

1. Request `pending_manager` state mein create hoti hai.
2. Manager/HOD **Edit Approvals** (`/edit-requests`) se approve/reject karta hai. Manager approval ke baad `pending_admin` hota hai; Manager rejection final hoti hai.
3. Admin/finalizer approve/reject karta hai. Final approval par requested clock-in ya break correction attendance record par apply hoti hai. Clock-in correction ke baad late/on-time status aur worked/break duration dobara calculate ho sakti hai.
4. Decision history mein actor ka naam aur stage show hota hai. Already processed request par dobara action karne par friendly error milta hai.

Approval tables mein sirf Approve/Reject buttons hain; optional note boxes UI mein nahi hain. Manager-stage correction requests ek week se purani hon to action reject hota hai.

## Team Status aur employee detail

**Team Status** (`/team-status`) live state (`Working`, `On break`, `Clocked out`, `Not in`) aur search, department, status filters deta hai. Employee card click karne par uski attendance detail khulti hai. Access hierarchy ke mutabiq restricted hai: Manager apne direct Executive reports aur khud; HOD apne assigned departments ke lower roles aur khud; Director/Super Admin company-wide active employees. Restricted URL direct open karne par `403` milta hai.

Detail page par Month, Last 7 days, Last 30 days ya Custom dates select karein. Status filter aur 20/50/100 per-page options bhi hain. Selected period ke cards attendance days, net working hours, on-time, late aur approved leave days dikhate hain; neeche paginated daily rows clock-in/out, breaks, break duration, worked time aur status dikhati hain.

## Admin reports aur baqi pages

| Page | Kya karta hai |
| --- | --- |
| Admin Dashboard (`/admin`) | Active employees, working now, pending leaves/edits aur live attendance snapshot. Super Admin ko employee count mein include nahi karta. |
| Employees (`/admin/employees`) | Search, department aur Manager/HOD filters; create/edit, employ/unemploy, delete. |
| Departments (`/admin/departments`) | Department code/name, HOD assignment, active state, employee/manager counts. |
| Timesheets (`/admin/timesheets`) | Date, employee, department, status filters; 20/50/100 per-page; daily records. |
| Analytics (`/admin/analytics`) | Today/week/month/custom range, employee, department, status; matching KPI cards, daily visual aur filtered CSV export. |
| Missing Attendance (`/admin/missing-attendance`) | Selected date par active employees jin ki clock-in nahi hai; late/absent CSV. |
| Leave Summary (`/admin/leave-summary`) | Approved leave usage by employee. |
| Configurations (`/admin/configurations`) | Super Admin ka effective-date shift, working days, breaks, attendance setup. |
| Audit History (`/admin/audits`) | Configuration changes ka who/when/old/new record. |
| Role Permissions (`/admin/permissions`) | Super Admin ka role-permission matrix. |
| Notifications (`/notifications`) | Request/configuration updates; unread count, read all aur delete. |
| Profile (`/profile`) | Name, phone/address, image aur password update. |

Date/month/datetime fields Flatpickr use karte hain. Success/error messages SweetAlert toast ke through dikhte hain. Browser push service ke liye VAPID keys optional hain; keys na hon to in-app notifications phir bhi save hoti hain.

## Operations, tests aur troubleshooting

```bash
php artisan test
php artisan route:list
php artisan schedule:list
php artisan attendance:auto-clock-out
```

`attendance:auto-clock-out` ko manual command ki tarah bhi run kar sakte hain, magar normal operation mein scheduler har minute chalna chahiye. Uploaded logos, avatars aur medical certificates ke browser access ke liye `php artisan storage:link` zaroori hai. `.env` mein `APP_URL`, database, timezone aur production mail/queue settings sahi rakhein.

Common checks:

- **Login nahi ho raha:** Email/password aur employee account ka `Currently employed` status check karein. Seeder sirf initial Super Admin banata hai.
- **Clock in button error deta hai:** Effective working weekday, start/end, early window aur same-shift existing record check karein.
- **Break option nahi dikhta:** Effective break configuration, scope, date aur Active toggle verify karein.
- **Manager ko request/team member nahi dikhta:** Employee ka `Reports to manager/HOD`, department aur HOD department assignment check karein.
- **Leave Admin ke paas approve ke liye ja rahi lagti hai:** Current code mein leave ka Manager/HOD decision final hai; pending_admin sirf attendance correction workflow ka stage hai.
- **Automatic clock-out nahi ho raha:** `schedule:work`/production cron aur effective shift end time check karein.
- **Uploads nahi khul rahe:** `storage:link`, `storage/app/public` permissions aur `APP_URL` check karein.

Optional legacy Rails data migration ke liye `.env.example` mein `LEGACY_*` variables aur `php artisan legacy:import` command available hain. Isse existing CRM data modify hota hai; backup aur staging verification ke baad hi use karein.
