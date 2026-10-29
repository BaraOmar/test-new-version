# ERP System Requirements

> **Source:** Extracted from the CultureHR (HRMS-NEW) codebase — Yii 2 Advanced application.  
> **Scope:** `frontend/`, `common/`, `backend/` (minimal), database entities, routes, controllers, services, and permissions.  
> **Rule:** Requirements below reflect implemented or partially implemented behavior only. Nothing is invented.

---

## 1. System Overview

CultureHR is an integrated **Human Resources and School Operations** platform. It is not a generic manufacturing or retail ERP; the codebase implements HR administration, employee self-service, school infrastructure, student affairs, student health, finance/accounting, recruitment, surveys, evaluations, and a Learning Management System (LMS).

### What the system is for

- Managing **employees and teachers** (profiles, departments, onboarding, attendance, leave).
- Operating **school academic structure** (departments → grades → sections → subjects → students).
- Running **student enrollment, health records, and parent/guardian finance profiles**.
- Recording **financial journal entries** and generating accounting reports.
- Publishing **jobs**, processing **applications**, and converting hires to employees.
- Distributing **surveys and evaluations** to employees and collecting responses.
- Delivering **LMS content**, section attendance, marks, and timetables for students and teachers.
- Controlling **HR dashboard access** through granular module permissions.

### Who uses it

| User type | Portal / entry |
|-----------|----------------|
| HR administrators | HR Portal (`/hr/*`) |
| Employees & teachers | Employee Portal (`/employee/*`) and optionally LMS |
| Students | LMS Student Portal (`/lms/*`) |
| Job applicants | Recruitment Applicant Portal (`/recruitment/applicant/*`) |
| Recruiters (session-based) | Recruitment Recruiter Portal (`/recruitment/recruiter/*`) |
| Super HR Admin | Full HR access (`is_super_admin = 1`) |
| External HR users | HR Portal without linked employee record |
| All authenticated users | Site login, password reset, email verification |

### Main modules found in the codebase

| Module | Location | Business domain |
|--------|----------|-----------------|
| HR Portal | `frontend/modules/hr` | Administration hub |
| Employee Portal | `frontend/modules/employee` | Self-service |
| LMS | `common/modules/lms` | Learning & school ops for students/teachers |
| Recruitment | `common/modules/recruitment` | Hiring pipeline |
| Survey | `common/modules/survey` | Employee surveys |
| Evaluation | `common/modules/evaluation` | Employee evaluations |
| Employee (shared) | `common/modules/employee` | Core HR & school entities |
| Finance (shared) | `common/modules/finance` | Accounting & parent profiles |
| Document | `common/modules/document` | File storage |
| Dashboard | `common/modules/dashboard` | Alternate analytics view |
| Auth / Site | `frontend/controllers/SiteController` | Login & public pages |
| Backend | `backend/` | Stock Yii admin shell only |

### General business purpose

The system supports an organization that employs staff (including teachers), enrolls students in a school structure, tracks employee attendance and leave, manages finances with double-entry journal entries, recruits new hires, gathers employee feedback through surveys/evaluations, and provides digital learning and classroom operations through the LMS.

**Not found in codebase:** Sales, purchases, inventory, manufacturing, payroll processing, or general CRM beyond recruitment.

---

## 2. User Roles

| Role | Description | Main Permissions | Related Modules |
|------|-------------|------------------|-----------------|
| `employee` | Default staff user linked to an `employees` record | Access Employee Portal: profile, attendance, leave, surveys/evaluations, change requests; teachers may access LMS | Employee Portal, LMS (teachers) |
| `hr` | HR administrator with JSON `permissions` on `user` record | Per-module keys from `HrPermissionCatalog` (38 keys); e.g. `dashboard`, `employees`, `finance`, `manage_hr` | HR Portal |
| `admin` | Treated same as `hr` for portal access (`User::isHr()`) | Same permission model as `hr` | HR Portal |
| `student` | Student linked to `school_student.user_id` | LMS student features: library, profile, health record, attendance log, marks, schedule | LMS Portal |
| Super HR Admin | `user.is_super_admin = 1` with `role` hr/admin | Bypasses all permission checks; protected from edit/delete on Manage HR page | HR Portal, LMS admin redirect |
| External HR user | `role = hr`, no linked `employees` record | Same permission keys as HR; created via Manage HR → External User | HR Portal |
| Applicant | `recruitment.users` with `role_id = 2` | Register, apply to jobs, manage profile/resume, track application status | Recruitment Applicant Portal |
| Recruiter (session) | `recruitment.users` with `role_id = 1` or HR user in recruiter session | Manage jobs, review applications, convert applicant to employee | Recruitment / HR Recruitment |
| Guest | Unauthenticated | Login, signup, password reset, contact, about | Site / Auth |
| Backend user | Yii backend app user | Login to stock backend index only | Backend (no business modules) |

**Permission model:** Stored as JSON array on `user.permissions`. Module-root keys (e.g. `finance`) grant all child feature keys. Checked via `User::hasPermission()` and `HrPermissionCatalog::isGranted()`.

---

## 3. Extracted Requirements by Module

---

## Module: Authentication & Site Access

### Module Purpose
Provide secure login, portal routing, password recovery, and optional switching between HR and Employee portals for users with both access types.

### Requirements

#### REQ-AUTH-001: User Login

- **Description:** Authenticated users sign in with email and password and are routed to the correct portal home page.

- **Logical Explanation:**
  - User submits credentials via `LoginForm`.
  - On success, system sets Yii session identity.
  - If user is HR-capable (`LoginPortal::canAccessHr`) and did not choose employee portal mode, redirect to `User::getHrHomeRoute()` (first permitted module).
  - Otherwise redirect to Employee Portal dashboard.
  - HR users may check "access employee portal" on login to set session flag and land on employee dashboard instead.

- **Actors:** Guest → any authenticated role.

- **Inputs:** Email, password; optional `access_employee` checkbox.

- **Outputs:** Active session; redirect to HR or Employee home route.

- **Validation Rules:** Valid credentials; inactive/deleted users cannot authenticate (`findIdentity` filters `status = active`).

- **Related Code:** `frontend/controllers/SiteController::actionLogin`, `common/models/LoginForm`, `common/components/LoginPortal`, `common/models/User::getHrHomeRoute`.

- **Implementation Status:** Implemented

---

#### REQ-AUTH-002: HR Access Check API

- **Description:** Login UI can check whether an email belongs to an HR-capable account before showing HR-specific options.

- **Logical Explanation:** AJAX endpoint returns `{ is_hr: boolean }` based on `LoginPortal::canAccessHr()`. Rate-limited when rate limiter is configured.

- **Actors:** Guest (pre-login UI).

- **Inputs:** Email query parameter.

- **Outputs:** JSON boolean.

- **Validation Rules:** Rate limit: 30 requests per 60 seconds per IP (when enabled).

- **Related Code:** `SiteController::actionCheckHr`, `AuthController`.

- **Implementation Status:** Implemented

---

#### REQ-AUTH-003: Switch HR User to Employee Portal

- **Description:** HR users with employee portal access can switch to the employee self-service view without logging out.

- **Logical Explanation:** Sets session flag `LoginPortal::SESSION_EMPLOYEE_PORTAL` and redirects to employee dashboard. Requires `LoginPortal::canAccessHr()`.

- **Actors:** HR user (with employee portal eligibility).

- **Inputs:** None (GET action).

- **Outputs:** Employee portal session mode.

- **Validation Rules:** Must be authenticated HR user.

- **Related Code:** `SiteController::actionEmployeePortal`, HR navbar footer link.

- **Implementation Status:** Implemented

---

#### REQ-AUTH-004: Password Reset and Email Verification

- **Description:** Users can request password reset and verify email addresses through token-based flows.

- **Actors:** Guest / authenticated user.

- **Inputs:** Email, reset token, new password, verification token.

- **Outputs:** Updated password or verified email status.

- **Validation Rules:** Token validity checks on `User` model.

- **Related Code:** `SiteController::actionRequestPasswordReset`, `actionResetPassword`, `actionVerifyEmail`, `actionResendVerificationEmail`.

- **Implementation Status:** Implemented (standard Yii2 user flows)

---

## Module: HR Dashboard

### Module Purpose
Give HR users an at-a-glance view of organization metrics and quick access to permitted modules.

### Requirements

#### REQ-HR-DASH-001: View HR Dashboard

- **Description:** Display aggregate stats and module access cards for the logged-in HR user.

- **Logical Explanation:**
  - Requires `dashboard` permission (or super admin).
  - Loads counts: total employees, surveys, survey responses, evaluations, evaluation responses, change requests.
  - Computes survey/evaluation participation rates.
  - Builds `moduleAccess` flags per module key for UI cards.

- **Actors:** HR user with `dashboard` permission; Super HR Admin.

- **Inputs:** None.

- **Outputs:** Dashboard view with stats and navigation cards.

- **Validation Rules:** `requireHr()` + `requirePermission('dashboard')`.

- **Related Code:** `HrController::actionDashboard`, `HrController::buildModuleAccess`.

- **Implementation Status:** Implemented

---

## Module: Employee Management

### Module Purpose
Maintain the employee/teacher directory, onboard new staff, view profiles, and manage employment status.

### Requirements

#### REQ-HR-EMP-001: Employee Directory

- **Description:** HR can search and filter all employees and teachers in a paginated directory.

- **Logical Explanation:**
  - Lists `Employee` records with filters: search text, org department, school department, status, joining date range.
  - Displays name, job title, email, department, manager, status.
  - Links to view profile and onboarding edit.

- **Actors:** HR with `employees` or `employees_directory` permission.

- **Inputs:** Filter query parameters.

- **Outputs:** Filtered employee list.

- **Validation Rules:** `requirePermission` for employees module access.

- **Related Code:** `HrController::actionEmployees`, `Employee` model, view `employees.php`.

- **Implementation Status:** Implemented

---

#### REQ-HR-EMP-002: Employee Onboarding (Create/Update)

- **Description:** HR creates a new employee (or edits existing) with profile data, org assignment, optional teacher flag, and login credentials.

- **Logical Explanation:**
  - Form loads `OnboardingEmployeeForm`.
  - On save (transaction): create/update `User` (`role = employee`, active), create/update `Employee`.
  - Teachers: assign `school_department_id` and `EmployeeSchoolSubject` links.
  - Non-teachers: assign `department_id` and optional `manager_id`.
  - New users require password (min 8 chars) or auto-generated temp password.
  - Default employee `status` is `Active` if not set.

- **Actors:** HR with `employees` permission.

- **Inputs:** `full_name`, `email`, `job_title`, `joining_date`, `phone`, `marital_status`, `position_level`, `employment_type`, `location`, `teacher`, `department_id` or `school_department_id`, `subject_ids[]`, `password`.

- **Outputs:** New/updated `Employee` + linked `User`; redirect to employee list.

- **Validation Rules:**
  - Required: `full_name`, `email`, `job_title`, `joining_date` (if column exists).
  - Name/job title: letters and spaces only.
  - Email format; phone unique on `employees` table.
  - `location` in `remote|hybrid|onsite`.
  - Password min 8 characters for new accounts.
  - Teacher subjects must belong to selected school department.
  - Managed HR admin employees may be blocked from modification.

- **Related Code:** `HrController::actionOnboarding`, `OnboardingEmployeeForm`, `Employee`, `User`.

- **Implementation Status:** Implemented

---

#### REQ-HR-EMP-003: View Employee Profile

- **Description:** HR views read-only detailed profile for a single employee.

- **Actors:** HR with employees permission.

- **Inputs:** Employee ID.

- **Outputs:** Profile page with department, manager, contact, status.

- **Related Code:** `HrController::actionViewEmployee`, view `view-employee.php`.

- **Implementation Status:** Implemented

---

#### REQ-HR-EMP-004: Change Employee Status / Delete

- **Description:** HR can change employee status or delete an employee via POST action.

- **Logical Explanation:**
  - `change_status` action sets `Employee.status` to one of: `Active`, `Onboarding`, `Leave`, `Terminated`.
  - `delete` removes employee record (per controller logic).
  - Terminated employees lose portal data access except profile/dashboard.

- **Actors:** HR with employees permission.

- **Inputs:** `employee_id`, `action`, optional `status`.

- **Outputs:** Updated or deleted employee; flash message.

- **Validation Rules:** Employee must exist; action must be recognized.

- **Related Code:** `HrController::actionEmployeeAction`, `Employee::isTerminated()`, `Employee::canReceiveEmployeePortalData()`.

- **Implementation Status:** Implemented

---

## Module: Organization Departments

### Module Purpose
Manage organizational (non-school) departments and assign department managers.

### Requirements

#### REQ-HR-DEPT-001: CRUD Organization Departments

- **Description:** HR manages `departments` table: list, view with members, create, update, delete.

- **Logical Explanation:**
  - Department has `name`, `manager_id` (→ `Employee`), `status` (`active`/`inactive`).
  - Delete blocked if employees are assigned.
  - Manager picker lists employees.

- **Actors:** HR with employees permission.

- **Inputs:** Department name, manager ID, status.

- **Outputs:** CRUD operations on `Department` records.

- **Validation Rules:** Name required; delete requires zero employees.

- **Related Code:** `DepartmentController`, `Department` model.

- **Implementation Status:** Implemented

---

## Module: School Departments & Infrastructure

### Module Purpose
Define school academic hierarchy: departments → grades → sections → subjects, assign teachers and materials, enroll students in sections.

### Requirements

#### REQ-HR-SCH-001: Manage School Departments

- **Description:** Create, view, update, delete school academic departments with head and deputy head teachers.

- **Logical Explanation:**
  - `SchoolDepartment` stores `name`, `head_id`, `deputy_head_id`, `status`.
  - On create, head/deputy cleared; assigned on update from active teachers in department.
  - Requires `SchoolDepartment::isEnabled()`.

- **Actors:** HR with `school_departments` or related permission.

- **Inputs:** Name, head employee ID, deputy head employee ID.

- **Outputs:** School department records.

- **Related Code:** `SchoolDepartmentController`, `SchoolDepartment` model.

- **Implementation Status:** Implemented

---

#### REQ-HR-SCH-002: Infrastructure Hierarchy Browser

- **Description:** HR browses the tree of school department → grade → section.

- **Actors:** HR with `school_departments_hierarchy` permission.

- **Outputs:** Hierarchy view.

- **Related Code:** `SchoolDepartmentController::actionHierarchy`.

- **Implementation Status:** Implemented

---

#### REQ-HR-SCH-003: Manage Grades and Sections

- **Description:** Add grades to departments; create sections within grades; assign class teachers, capacity, room numbers.

- **Logical Explanation:**
  - `SchoolGrade` belongs to `SchoolDepartment`.
  - `SchoolSection` belongs to `SchoolGrade`; has `lead_teacher_id`, `capacity_max`, `current_enrollment`, `room_number`.
  - Section detail page manages materials and student roster.
  - Printable section roster available.

- **Actors:** HR with school department permissions.

- **Inputs:** Grade name, section name, teacher ID, capacity, room.

- **Outputs:** Grade and section records; updated enrollment counts.

- **Validation Rules:** Class teacher cannot be double-assigned to multiple sections (filtered in UI). Student grade/department must match section on enrollment.

- **Related Code:** `SchoolDepartmentController` (add-grade, create-section, section, section-print, assign-class-teacher), `SchoolGrade`, `SchoolSection`.

- **Implementation Status:** Implemented

---

#### REQ-HR-SCH-004: Subjects Catalog

- **Description:** Manage subjects per school department with grade-level credit and max score assignments.

- **Logical Explanation:**
  - `SchoolSubject` linked to department via `school_department_id`.
  - `SchoolSubjectGrade` junction stores `credits` and `max_score` per grade.
  - Teachers linked via `EmployeeSchoolSubject`.

- **Actors:** HR with `subjects` permission.

- **Inputs:** Subject name, code, grade assignments with credits.

- **Outputs:** Subject catalog records.

- **Validation Rules:** Credits must be > 0 per grade assignment.

- **Related Code:** `SchoolDepartmentController` (subjects, create-subject, update-subject), `SchoolSubject`, `SchoolSubjectGrade`.

- **Implementation Status:** Implemented

---

#### REQ-HR-SCH-005: Section Materials and Student Roster

- **Description:** Assign course materials and instructors to sections; enroll/remove students.

- **Logical Explanation:**
  - `SchoolSectionMaterial` links subject, instructor (`Employee`), schedule, status (`active`/`pending`).
  - `assignStudentToSection` sets `SchoolStudent.school_section_id` when grade/department match.
  - `removeStudent` clears section assignment and decrements `current_enrollment`.

- **Actors:** HR with school department / employees permission.

- **Inputs:** Section ID, material details, student ID.

- **Outputs:** Updated section materials and roster.

- **Validation Rules:** Student must match section grade and department; student not already in another section.

- **Related Code:** `SchoolDepartmentController` (assign-material, assign-student, remove-student), `SchoolSectionMaterial`.

- **Implementation Status:** Implemented

---

## Module: Employee Attendance

### Module Purpose
Track employee clock-in/out, configure shift rules, review org-wide attendance, and export reports.

### Requirements

#### REQ-HR-ATT-001: Attendance Configuration

- **Description:** HR configures global attendance settings: shift times, grace windows, and off-day policies.

- **Logical Explanation:**
  - Saves `AttendanceSettings` (shift start/end, grace window).
  - Saves `AttendanceOffDayPolicy` records (off days JSON, specific dates, duration).
  - Delegates to `AttendanceConfigurationService`.

- **Actors:** HR with `attendance_configuration` or `attendance` permission.

- **Inputs:** Shift times, grace window, policy name, off days, holiday dates.

- **Outputs:** Updated attendance configuration.

- **Related Code:** `HrController::actionAttendanceConfiguration`, `AttendanceConfigurationService`, `AttendanceSettings`, `AttendanceOffDayPolicy`.

- **Implementation Status:** Implemented

---

#### REQ-HR-ATT-002: Attendance Overview and Management

- **Description:** HR reviews organization-wide daily attendance and per-employee monthly logs.

- **Logical Explanation:**
  - Overview page links to management, configuration, leave requests.
  - Management view filters attendance by date/department/status.
  - Employee log shows monthly `EmployeeAttendance` records for one employee.
  - CSV export available.

- **Actors:** HR with `attendance`, `attendance_overview`, or `attendance_management` permission (also `employees` grants attendance access in `requireAttendanceAccess`).

- **Inputs:** Date filters, employee ID.

- **Outputs:** Attendance reports and exports.

- **Related Code:** `HrController::actionAttendance`, `actionAttendanceManagement`, `actionAttendanceEmployeeLog`, `actionAttendanceExport`, `EmployeeAttendance`.

- **Implementation Status:** Implemented

---

#### REQ-EMP-ATT-001: Employee Clock In / Clock Out

- **Description:** Employees record work arrival and departure times.

- **Logical Explanation:**
  - POST to `clock-in` or `clock-out`.
  - `EmployeeAttendanceService` creates `EmployeeAttendance` with `clock_in_at`; closes with `clock_out_at`.
  - Blocked if employee terminated or attendance disabled.
  - Cannot clock in twice without clocking out; cannot clock out without open record.

- **Actors:** Employee with linked `user_id`; not terminated.

- **Inputs:** None (timestamp captured server-side).

- **Outputs:** `EmployeeAttendance` record; flash message.

- **Validation Rules:** Attendance feature enabled; no duplicate open session; clock-out after clock-in.

- **Related Code:** `EmployeeController::actionClockIn`, `actionClockOut`, `EmployeeAttendanceService`, `EmployeeAttendance`.

- **Implementation Status:** Implemented

---

## Module: Employee Leave

### Module Purpose
Allow employees to submit leave requests and HR to approve or reject them.

### Requirements

#### REQ-HR-LEV-001: Review Leave Requests

- **Description:** HR views pending leave requests and approves or rejects with optional note.

- **Logical Explanation:**
  - Lists `EmployeeLeaveRequest` with `status = pending`.
  - POST review sets `status` to `approved` or `rejected`, records `reviewed_by`, `reviewed_at`, `hr_note`.
  - `EmployeeLeaveService::review` validates request is still pending.

- **Actors:** HR with `attendance_leave_requests` or attendance access.

- **Inputs:** `request_id`, `decision`, `hr_note`.

- **Outputs:** Updated leave request status.

- **Validation Rules:** Request must exist and be pending; decision must be `approved` or `rejected`.

- **Related Code:** `HrController::actionAttendanceLeaveRequests`, `actionAttendanceLeaveReview`, `EmployeeLeaveService`, `EmployeeLeaveRequest`.

- **Implementation Status:** Implemented

---

#### REQ-EMP-LEV-001: Submit Leave Request

- **Description:** Employee submits a leave request with type, dates, reason, and optional attachment.

- **Logical Explanation:**
  - `LeaveRequestForm` validated and passed to `EmployeeLeaveService::submit`.
  - Creates `EmployeeLeaveRequest` with `status = pending`.
  - Service checks leave balance and valid date range.

- **Actors:** Active employee (not terminated).

- **Inputs:** `leave_type` (annual/sick/personal/maternity/bereavement), `start_date`, `end_date`, `reason`, optional attachment.

- **Outputs:** Pending leave request record.

- **Validation Rules:** Valid date range; sufficient leave balance; leave feature enabled.

- **Related Code:** `EmployeeController::actionRequestLeave`, `LeaveRequestForm`, `EmployeeLeaveService`.

- **Implementation Status:** Implemented

---

## Module: Surveys

### Module Purpose
HR creates and distributes surveys to employees; employees submit responses; HR views results.

### Requirements

#### REQ-HR-SRV-001: Create and Launch Survey

- **Description:** HR builds a survey with questions/options, targets departments or individuals, and launches it.

- **Logical Explanation:**
  - Transaction: save `Survey`, `SurveyQuestion` + options, `SurveyTarget` (departments) or `SurveyEmployeeTarget` (individuals).
  - Audience types: `department` (syncs all dept employees/managers) or `employee` (direct targets).
  - Schedule: `start_at`, `end_at`, `duration_days` → status becomes `active`, `scheduled`, `closed`, or `draft`.
  - Optional `is_anonymous` flag.

- **Actors:** HR with `surveys` permission.

- **Inputs:** Title, description, questions, target departments/employees, schedule, anonymous flag.

- **Outputs:** Survey record with targets and questions.

- **Validation Rules:** At least one question; valid target selection; HR admin access required.

- **Related Code:** `SurveyController` (HR extends common), `Survey`, `SurveyQuestion`, `SurveyTarget`, `SurveyEmployeeTarget`, `SurveyLaunchService`.

- **Implementation Status:** Implemented

---

#### REQ-HR-SRV-002: View Survey Results

- **Description:** HR views aggregated responses and analytics for a completed survey.

- **Actors:** HR with surveys permission.

- **Inputs:** Survey ID.

- **Outputs:** Results view with response breakdown.

- **Related Code:** `SurveyController::actionResults`, `SurveyResultsService`.

- **Implementation Status:** Implemented

---

#### REQ-EMP-SRV-001: Respond to Survey

- **Description:** Assigned employee completes and submits a survey once.

- **Logical Explanation:**
  - `SurveyAccessService::canRespond` checks assignment, schedule window, not already submitted, survey not draft/closed.
  - Creates `SurveyResponse` + `SurveyAnswer` per question.
  - Required questions must be answered.

- **Actors:** Assigned employee (not terminated).

- **Inputs:** Answer values per question.

- **Outputs:** `SurveyResponse` and `SurveyAnswer` records.

- **Validation Rules:** Single submission per employee per survey; within schedule; assigned target.

- **Related Code:** `EmployeeController::actionSurveyDisplay`, `employee/SurveyController::actionRespond`, `SurveyAccessService`.

- **Implementation Status:** Implemented

---

## Module: Evaluations

### Module Purpose
Same workflow as surveys but for performance evaluations.

### Requirements

#### REQ-HR-EVL-001: Create and Launch Evaluation

- **Description:** HR creates evaluations with questions, targets, and schedule — mirrors survey module.

- **Actors:** HR with `evaluations` permission.

- **Related Code:** `EvaluationController`, `Evaluation*` models (parallel to Survey*).

- **Implementation Status:** Implemented

---

#### REQ-EMP-EVL-001: Respond to Evaluation

- **Description:** Assigned employee submits evaluation responses once.

- **Actors:** Assigned employee.

- **Related Code:** `employee/EvaluationController::actionRespond`, `EvaluationAccessService`.

- **Implementation Status:** Implemented

---

## Module: Student Affairs

### Module Purpose
Enroll and manage students, maintain registry, export data, and provision student portal accounts.

### Requirements

#### REQ-HR-STU-001: Student Registry

- **Description:** HR lists and filters all enrolled students.

- **Logical Explanation:**
  - Filters: school department, grade, section, national ID, status, academic year, search text.
  - Requires `SchoolStudent::isEnabled()` and `SchoolDepartment::isEnabled()`.

- **Actors:** HR with `student_affairs` or `student_affairs_registry` permission.

- **Outputs:** Filtered student list.

- **Related Code:** `StudentAffairsController::actionStudents`, `SchoolStudent`.

- **Implementation Status:** Implemented

---

#### REQ-HR-STU-002: Enroll New Student

- **Description:** HR enrolls a student with personal data, guardian info, documents, portal credentials, and school placement.

- **Logical Explanation:**
  - Transaction: save `SchoolStudent` (`status = active`), create `User` (`role = student`) via `savePortalCredentials`, upload documents to `SchoolStudentDocument`.
  - `applyEnrollmentAssignment` sets department and grade; `school_section_id` set separately via School Department module.
  - Optional finance parent profile link via enrollment services.

- **Actors:** HR with `student_affairs_create` or student affairs permission.

- **Inputs:** Name parts, national ID, gender, birth date, address, phone, guardian name/phone, emergency contact, medical notes, academic year, department, grade, portal email/password, document uploads.

- **Outputs:** `SchoolStudent` + `User` + documents.

- **Validation Rules:**
  - Department and grade required; grade must belong to department.
  - Portal password required for new student user.
  - Documents max 10MB.
  - Status in `active|inactive|suspended|graduated`.

- **Related Code:** `StudentAffairsController::actionCreate`, `StudentPortalForm`, `SchoolStudent`, `SchoolStudentDocument`.

- **Implementation Status:** Implemented

---

#### REQ-HR-STU-003: Update and Delete Student

- **Description:** HR edits student record or hard-deletes student.

- **Actors:** HR with student affairs permission.

- **Related Code:** `StudentAffairsController::actionUpdate`, `actionDelete`, `actionView`.

- **Implementation Status:** Implemented

---

#### REQ-HR-STU-004: Export Student Registry

- **Description:** Export student list to Excel or PDF.

- **Actors:** HR with student affairs permission.

- **Related Code:** `StudentAffairsController::actionExportExcel`, `actionExportPdf`.

- **Implementation Status:** Implemented

---

## Module: Student Health

### Module Purpose
Maintain per-student health files and clinic visit records; notify parents when needed.

### Requirements

#### REQ-HR-HLT-001: Health File Management

- **Description:** Create, view, update, delete one health file per student (allergies, chronic conditions, medical history).

- **Logical Explanation:**
  - `SchoolStudentHealthFile` is 1:1 with `SchoolStudent`.
  - Stores allergy status/detail, chronic conditions, surgical history, prior illnesses, notes.

- **Actors:** HR with `student_health` or `student_health_files` permission.

- **Inputs:** Health risk fields, allergy details, medical history, notes.

- **Outputs:** Health file record.

- **Validation Rules:** `SchoolStudentHealthFile::isEnabled()`; student must exist.

- **Related Code:** `StudentHealthController`, `SchoolStudentHealthFile`.

- **Implementation Status:** Implemented

---

#### REQ-HR-HLT-002: Record Clinic Visit

- **Description:** Log a clinic visit for a student with reason, treatment, and outcome.

- **Logical Explanation:**
  - Creates `SchoolStudentClinicVisit` with defaults: `visit_date=today`, `visit_type=routine_check`, `status=in_progress`.
  - On save, updates health file `last_visit_date`.
  - Status values include `completed`, `referred`, `sent_home`.

- **Actors:** HR with `student_health_clinic` permission.

- **Inputs:** Student ID, visit date/time, primary reason, visit type, medicine given, action taken, clinical notes.

- **Outputs:** Clinic visit record.

- **Related Code:** `StudentHealthController::actionClinicVisits`, `SchoolStudentClinicVisit`.

- **Implementation Status:** Implemented

---

#### REQ-HR-HLT-003: Clinic Visit Records and Parent Notification

- **Description:** View visit history; send parent notification for specific outcomes.

- **Logical Explanation:**
  - Visit records list all clinic visits.
  - `notify-parents` JSON endpoint only for outcomes: `parent_contacted`, `sent_home`, `referred_hospital`; requires parent email on file.

- **Actors:** HR with `student_health_records` permission.

- **Related Code:** `StudentHealthController::actionClinicVisitRecords`, `actionNotifyParents`.

- **Implementation Status:** Implemented

---

## Module: Parents Directory

### Module Purpose
Manage parent/guardian finance profiles linked to chart of accounts and children records.

### Requirements

#### REQ-HR-PAR-001: Parents Directory

- **Description:** Search and manage parent profiles used for finance billing and family records.

- **Logical Explanation:**
  - Lists `FinanceAccountParentProfile` records via `FinanceParentProfileService`.
  - View shows linked children (`FinanceAccountParentChild`).
  - Update edits parent contact and children file paths.
  - Requires `FinanceAccountParentProfile::isEnabled()`.

- **Actors:** HR with `parents_directory` permission.

- **Inputs:** Search text; parent name, phone, email, children data on update.

- **Outputs:** Parent profile list and detail views.

- **Related Code:** `ParentsController`, `FinanceAccountParentProfile`, `FinanceAccountParentChild`, `FinanceParentProfileService`.

- **Implementation Status:** Implemented

---

## Module: Financial Resources

### Module Purpose
Double-entry accounting: chart of accounts, journal entries, account statements, and financial reports.

### Requirements

#### REQ-HR-FIN-001: Chart of Accounts

- **Description:** Maintain hierarchical ledger accounts with types (asset, liability, equity, revenue, expense).

- **Logical Explanation:**
  - `FinanceAccount` supports `parent_id` self-reference, `code`, `name`, `account_type`, `is_active`, `is_analytical`, `is_vat`.
  - Create account form with parent picker and auto-suggested codes.

- **Actors:** HR with `finance_chart_of_accounts` or `finance` permission.

- **Inputs:** Code, name, type, parent account, description, flags.

- **Outputs:** Account records.

- **Validation Rules:** `FinanceJournalEntryService::isEnabled()`; service-level account validation.

- **Related Code:** `FinanceController::actionAccounts`, `actionCreateAccount`, `actionSaveAccount`, `FinanceAccountService`, `FinanceAccount`.

- **Implementation Status:** Implemented

---

#### REQ-HR-FIN-002: Create Parent Finance Account

- **Description:** When creating an account marked as parent (`is_parent=true`), system also creates `FinanceAccountParentProfile` with children records.

- **Actors:** HR with finance permission.

- **Related Code:** `FinanceController::actionSaveAccount`, `FinanceParentProfileService`.

- **Implementation Status:** Implemented

---

#### REQ-HR-FIN-003: Journal Entry Management

- **Description:** Create, edit, and approve double-entry journal entries with multiple debit/credit lines.

- **Logical Explanation:**
  - Entry has `entry_number`, `entry_date`, `description`, `status` (`draft` → `pending_approval` → `approved`).
  - Lines: `account_id`, `debit_amount`, `credit_amount`; total debits must equal credits for non-draft saves.
  - Approved entries are immutable (edit blocked in controller).
  - Records `created_by_user_id`, `approved_by_user_id`, `approved_at`.

- **Actors:** HR with `finance_journal_entries` or `finance` permission.

- **Inputs:** Entry header, line items, `save_mode` (draft/pending_approval/approved).

- **Outputs:** `FinanceJournalEntry` + `FinanceJournalEntryLine` records.

- **Validation Rules:** Description required; balanced debits/credits; cannot edit approved entries.

- **Related Code:** `FinanceController::actionCreate`, `actionSaveEntry`, `actionView`, `FinanceJournalEntryService`, `FinanceJournalEntry`, `FinanceJournalEntryLine`.

- **Implementation Status:** Implemented

---

#### REQ-HR-FIN-004: Account Statement

- **Description:** Generate running balance statement for a selected account over a date range.

- **Actors:** HR with `finance_account_statement` permission.

- **Related Code:** `FinanceController::actionAccountStatement`, `FinanceReportService`.

- **Implementation Status:** Implemented

---

#### REQ-HR-FIN-005: Financial Reports

- **Description:** Generate Trial Balance, Profit & Loss, and Balance Sheet reports.

- **Actors:** HR with `finance_reports` permission.

- **Inputs:** `report_type`, date range filters.

- **Outputs:** Report views.

- **Related Code:** `FinanceController::actionReports`, `FinanceReportService`.

- **Implementation Status:** Implemented

---

## Module: Recruitment

### Module Purpose
Publish job openings, collect applications, conduct interview workflow, and convert hired applicants to employees.

### Requirements

#### REQ-HR-REC-001: Job Posting Lifecycle

- **Description:** HR/recruiter creates, publishes, closes, reopens, and deletes job postings.

- **Logical Explanation:**
  - `Job` status: `draft` → `published` → `closed`.
  - `open-job` restores `published` from `closed`.
  - Close job also closes active applications.

- **Actors:** HR with `recruitment_jobs` or `recruitment` permission; recruiter session.

- **Inputs:** Job title, department, location, description.

- **Outputs:** `Job` records in `jobs` table.

- **Related Code:** `RecruitmentController` / `ManageController`, `Job` model.

- **Implementation Status:** Implemented

---

#### REQ-HR-REC-002: Application Review and Interview

- **Description:** Review applications, update status, schedule interviews, save feedback.

- **Logical Explanation:**
  - `Application.application_status`: `Pending`, `Screened`, `Interviewing`, `Offered`, `Rejected`, `Hired`, `Closed`.
  - Schedule interview sets `interview_at`, `interview_type`, status → `Interviewing`.
  - Feedback: `feedback_rating`, `feedback_notes`.

- **Actors:** HR/recruiter with recruitment permission.

- **Related Code:** `ManageController::actionApplications`, `Application` model.

- **Implementation Status:** Implemented

---

#### REQ-HR-REC-003: Convert Applicant to Employee

- **Description:** Hire an applicant by creating `Employee` and optional `User`, marking application `Hired`.

- **Logical Explanation:**
  - Resolves org `Department` from job department name.
  - `ApplicantConversionService::convert` creates employee (`status=Active`, `joining_date=today`, `employment_type=full-time`).
  - Fails if applicant email already exists as employee.

- **Actors:** HR/recruiter.

- **Inputs:** Application ID.

- **Outputs:** New `Employee` (+ optional `User` with temp password); application status `Hired`.

- **Validation Rules:** Department must exist; email not duplicate on employees.

- **Related Code:** `ManageController::actionConvertApplicant`, `ApplicantConversionService`.

- **Implementation Status:** Implemented

---

#### REQ-REC-APP-001: Applicant Self-Service

- **Description:** Applicants register, log in, apply to jobs, manage profile/resume, track status, withdraw applications.

- **Actors:** Applicant (`recruitment.users`, `role_id=2`).

- **Related Code:** `ApplicantController`, `Applicant` model.

- **Implementation Status:** Implemented

---

#### REQ-HR-REC-004: Recruitment Reports

- **Description:** Generate and export recruitment pipeline reports.

- **Actors:** HR with `recruitment_reports` permission.

- **Related Code:** `RecruitmentController::actionReports`, `actionExportReport`.

- **Implementation Status:** Implemented

---

## Module: Change Requests

### Module Purpose
Employees request profile changes; HR approves or rejects without automatic application to profile.

### Requirements

#### REQ-HR-CHG-001: Review Change Requests

- **Description:** HR views inbox of employee change requests and sets status to Approved or Rejected.

- **Logical Explanation:**
  - Lists `EmployeeChangeRequest` filtered by status.
  - POST sets `status` to `Approved` or `Rejected`.
  - **Does not** apply `new_value` to employee record (status update only).

- **Actors:** HR with `change_requests` permission.

- **Inputs:** `request_id`, `action` (approve/reject).

- **Outputs:** Updated request status.

- **Related Code:** `HrController::actionRequests`, `EmployeeChangeRequest`.

- **Implementation Status:** Partially implemented — approval workflow exists; automatic profile update on approve is **not** implemented in controller.

---

#### REQ-EMP-CHG-001: Submit Change Request

- **Description:** Employee submits a request to change a profile field.

- **Logical Explanation:**
  - Creates `EmployeeChangeRequest` with `title`, `old_value`, `new_value`, `urgency` (`Normal`/`Urgent`), default `status=Pending`.

- **Actors:** Employee (not terminated).

- **Inputs:** Title, old value, new value, urgency.

- **Outputs:** Pending change request.

- **Validation Rules:** Title and new value required.

- **Related Code:** `EmployeeController::actionRequest`, `EmployeeChangeRequest`.

- **Implementation Status:** Implemented

---

## Module: Career Paths

### Module Purpose
Provide career development views for HR and employees.

### Requirements

#### REQ-HR-CRR-001: HR Career Paths View

- **Description:** HR page for career path management.

- **Actors:** HR with `career_paths` permission.

- **Related Code:** `HrController::actionCareer`.

- **Implementation Status:** Partially implemented — UI placeholder; full career path logic not found in controllers/models.

---

#### REQ-EMP-CRR-001: Employee My Development View

- **Description:** Employee self-service career development page.

- **Actors:** Employee.

- **Related Code:** `EmployeeController::actionCareer`.

- **Implementation Status:** Partially implemented — UI placeholder.

---

## Module: User Permissions (Manage HR)

### Module Purpose
Grant HR dashboard access and granular module permissions to employees or external users.

### Requirements

#### REQ-HR-MGT-001: Grant Permissions to Existing Employee

- **Description:** Select an employee, view read-only profile, set dashboard credentials and module permissions.

- **Logical Explanation:**
  - Does **not** modify employee profile fields (name, phone, job title, etc.).
  - Creates or updates linked `User`: `role=hr`, `email=dashboard_email`, `permissions` JSON, optional password.
  - Links `employees.user_id` and sets `is_hr_admin=1`.
  - Permissions filtered to what acting user may assign.

- **Actors:** HR with `manage_hr` permission.

- **Inputs:** `employee_id`, `dashboard_email`, `password` (required for new account), `permissions[]`.

- **Outputs:** Updated/created `User` linked to employee.

- **Validation Rules:**
  - Dashboard email required, valid, unique on `user.email`.
  - Password required for new accounts; min 8 chars.
  - At least one permission.
  - Cannot modify super admin accounts.

- **Related Code:** `GrantEmployeePermissionsForm`, `HrController::actionManageHr`, `actionEmployeePermissionProfile`.

- **Implementation Status:** Implemented

---

#### REQ-HR-MGT-002: Create External HR User

- **Description:** Create HR dashboard user who is not an employee record.

- **Logical Explanation:**
  - Creates new `User` with `full_name` → `username`, `phone`, `dashboard_email`, `password`, `role=hr`, permissions.
  - No `Employee` record created.

- **Actors:** HR with `manage_hr` permission.

- **Inputs:** `full_name`, `phone`, `dashboard_email`, `password`, `permissions[]`.

- **Outputs:** New external HR `User`.

- **Validation Rules:** Name letters/spaces only; phone required and unique on `user.phone`; password required min 8.

- **Related Code:** `GrantEmployeePermissionsForm::saveExternalUser`, `user.phone` column.

- **Implementation Status:** Implemented

---

#### REQ-HR-MGT-003: Manage HR User Accounts

- **Description:** List, view, update, and soft-delete HR administrator accounts.

- **Logical Explanation:**
  - Lists users with `role` hr/admin.
  - Update via `UpdateHrUserForm` (employee-linked or external paths).
  - Delete sets `User.status = STATUS_DELETED` (soft delete).
  - Super admin accounts protected from edit/delete.

- **Actors:** HR with `manage_hr` permission.

- **Related Code:** `HrController::actionViewHr`, `actionUpdateHr`, `actionManageHrAction`, `UpdateHrUserForm`.

- **Implementation Status:** Implemented

---

## Module: Employee Portal (Self-Service)

### Module Purpose
Allow employees to view profile, track attendance, request leave, complete surveys/evaluations, and submit change requests.

### Requirements

#### REQ-EMP-001: Employee Dashboard

- **Description:** Show pending surveys/evaluations and quick links.

- **Actors:** Employee (or HR in employee portal mode).

- **Related Code:** `EmployeeController::actionDashboard`.

- **Implementation Status:** Implemented

---

#### REQ-EMP-002: View My Profile

- **Description:** Employee views own profile, department, manager, job details.

- **Actors:** Employee (including terminated — profile only).

- **Related Code:** `EmployeeController::actionProfile`.

- **Implementation Status:** Implemented

---

#### REQ-EMP-003: My Attendance and Leave History

- **Description:** Employee views attendance history and leave request status.

- **Actors:** Active employee.

- **Related Code:** `EmployeeController::actionMyAttendance`, `actionMyLeaveRequests`.

- **Implementation Status:** Implemented

---

#### REQ-EMP-004: Survey and Evaluation Inbox

- **Description:** Combined list of pending surveys and evaluations.

- **Actors:** Active employee.

- **Related Code:** `EmployeeController::actionSurveyDisplay`.

- **Implementation Status:** Implemented

---

#### REQ-EMP-005: Terminated Employee Access Restriction

- **Description:** Terminated employees may only access dashboard and profile; all other portal data blocked.

- **Logical Explanation:** `Employee::canReceiveEmployeePortalData()` returns false when `status = Terminated`.

- **Actors:** Terminated employee.

- **Related Code:** `Employee::isTerminated()`, `Employee::canReceiveEmployeePortalData()`.

- **Implementation Status:** Implemented

---

## Module: LMS (Learning Management System)

### Module Purpose
Deliver learning content, manage class sections, student attendance, marks, and timetables for students and teachers.

### Requirements

#### REQ-LMS-001: LMS Portal Login and Role Routing

- **Description:** Students and teachers authenticate into LMS and land on role-appropriate dashboard.

- **Actors:** Student (`role=student`); teacher (employee with teacher category).

- **Related Code:** `PortalController`, `LmsPortal` component.

- **Implementation Status:** Implemented

---

#### REQ-LMS-002: Content Library and Upload

- **Description:** Teachers upload and browse learning materials (`Course`/`CourseMaterial` in `lms_content` table).

- **Actors:** Teachers; HR with LMS access.

- **Related Code:** `LMSController::actionIndex`, `actionUpload`, `actionViewCourse`, `Course`, `CourseMaterial`.

- **Implementation Status:** Implemented

---

#### REQ-LMS-003: Student Self-Service (LMS)

- **Description:** Students view profile, health record, attendance log, marks, subjects, and personal schedule.

- **Actors:** Student.

- **Related Code:** `lms/StudentAffairsController` (profile, health-record, attendance-log, marks-log, marks).

- **Implementation Status:** Implemented

---

#### REQ-LMS-004: Section Attendance (Student)

- **Description:** Teachers take daily attendance per school section; records present/absent/late/excused per student.

- **Logical Explanation:**
  - Creates `SchoolSectionAttendanceSession` per section per date.
  - `SchoolStudentAttendanceRecord` per student with status.

- **Actors:** Teacher with section attendance permission (`LmsPortal::canTakeAttendanceForSection`).

- **Related Code:** `lms/AttendanceController`, `SchoolStudentAttendanceService`, `SchoolSectionAttendanceSession`, `SchoolStudentAttendanceRecord`.

- **Implementation Status:** Implemented

---

#### REQ-LMS-005: Section Marks

- **Description:** Teachers enter and save marks per student per section material (first/second/third/final components).

- **Logical Explanation:**
  - `SchoolStudentSectionMark` stores mark components and `total`, `status` (pass/failed).
  - Validated against `subjectMaxScore` from `SchoolSubjectGrade`.

- **Actors:** Teacher with marks permission.

- **Related Code:** `MyClassesController::actionMarks`, `actionSaveMarks`, `SchoolSectionMarksService`, `SchoolStudentSectionMark`.

- **Implementation Status:** Implemented

---

#### REQ-LMS-006: Timetable Management

- **Description:** Build section timetables with rows (days), columns (time slots), and slot assignments (material + instructor).

- **Logical Explanation:**
  - `SchoolSectionTimetableRow`, `SchoolSectionTimetableColumn`, `SchoolSectionTimetableSlot`.
  - Instructors view personal schedule via `my-schedule`.
  - Requires `LmsPortal::isTimetableEnabled()`.

- **Actors:** HR/dept managers (manage); instructors (view own schedule).

- **Related Code:** `TimetableController`, `TimetableService`.

- **Implementation Status:** Implemented

---

#### REQ-LMS-007: My Classes (Marks, Exams, Operations)

- **Description:** Teachers access class sections hub for marks entry, exams view, and course operations.

- **Actors:** Teacher with `LmsPortal::canAccessMyClasses`.

- **Related Code:** `MyClassesController`.

- **Implementation Status:** Implemented

---

#### REQ-LMS-008: Teacher-Scoped Student Registry and Hierarchy

- **Description:** Teachers with department leadership or class teacher role manage sections, students, and infrastructure within their scope.

- **Actors:** Department leaders, class teachers.

- **Related Code:** `lms/StudentAffairsController`, `lms/HierarchyController`, `lms/SubjectsController`.

- **Implementation Status:** Implemented

---

## Module: Document Storage

### Module Purpose
Secure upload and retrieval of files.

### Requirements

#### REQ-DOC-001: Upload and View Documents

- **Description:** Authenticated users upload documents and view/download by ID.

- **Logical Explanation:**
  - `Document` stores metadata: title, stored path, SHA256 hash, mime type, `uploaded_by`.

- **Actors:** Authenticated user.

- **Related Code:** `DocumentController`, `Document` model.

- **Implementation Status:** Implemented

---

## Module: Alternate Dashboard

### Module Purpose
Provide a secondary analytics dashboard with navigation cards.

### Requirements

#### REQ-DASH-001: Analytics Dashboard

- **Description:** Alternate dashboard view with organization snapshot.

- **Actors:** User passing HR admin check in controller.

- **Related Code:** `common/modules/dashboard/controllers/DashboardController`, `SiteController::actionAdminDashboard`.

- **Implementation Status:** Implemented (parallel to HR dashboard; relationship to main HR hub **Needs confirmation**)

---

## 4. Main Business Workflows

### Workflow 1: User Registration and Portal Routing

| Step | Actor | Action |
|------|-------|--------|
| 1 | Guest | Submits login credentials |
| 2 | System | Validates user, checks role and permissions |
| 3 | System | Routes to HR home (first permitted module) or Employee dashboard |
| 4 | HR user (optional) | Chooses employee portal mode at login |

**Final result:** Authenticated session in correct portal.  
**Related modules:** Auth, HR Portal, Employee Portal

---

### Workflow 2: Employee Onboarding

| Step | Actor | Action |
|------|-------|--------|
| 1 | HR | Opens onboarding form |
| 2 | HR | Enters profile, department/school assignment, credentials |
| 3 | System | Validates form, begins transaction |
| 4 | System | Creates/updates `User` (employee role) and `Employee` |
| 5 | System | Assigns teacher subjects if applicable |
| 6 | System | Commits; employee appears in directory |

**Final result:** Active employee with login credentials.  
**Related modules:** Employee Management, Organization/School Departments

---

### Workflow 3: HR Permission Grant

| Step | Actor | Action |
|------|-------|--------|
| 1 | HR (manage_hr) | Selects employee OR external user mode |
| 2 | HR | Sets dashboard email, password, permission checkboxes |
| 3 | System | Validates, filters permissions to actor's assignable set |
| 4 | System | Creates/updates `User` (hr role, permissions JSON) |
| 5 | System | Links employee `user_id` (employee path only) |

**Final result:** User can log into HR portal with assigned module access.  
**Related modules:** User Permissions

---

### Workflow 4: Employee Attendance Day

| Step | Actor | Action |
|------|-------|--------|
| 1 | Employee | Clocks in (POST) |
| 2 | System | Creates `EmployeeAttendance` with `clock_in_at` |
| 3 | Employee | Clocks out (POST) |
| 4 | System | Sets `clock_out_at` |
| 5 | HR | Reviews attendance in management view or exports CSV |

**Final result:** Complete attendance record for work day.  
**Related modules:** Employee Portal, HR Attendance

---

### Workflow 5: Leave Request Approval

| Step | Actor | Action |
|------|-------|--------|
| 1 | Employee | Submits leave request with dates and type |
| 2 | System | Validates balance and dates; creates pending request |
| 3 | HR | Reviews in leave requests inbox |
| 4 | HR | Approves or rejects with optional note |
| 5 | System | Updates status, records reviewer and timestamp |

**Final result:** Leave request resolved (approved/rejected).  
**Related modules:** Employee Portal, HR Attendance/Leave

---

### Workflow 6: Student Admission

| Step | Actor | Action |
|------|-------|--------|
| 1 | HR | Opens student enrollment form |
| 2 | HR | Enters personal, guardian, academic, and portal data |
| 3 | System | Validates, saves `SchoolStudent`, creates `User` (student role) |
| 4 | System | Uploads documents |
| 5 | HR (separate step) | Assigns student to section via School Department module |

**Final result:** Enrolled student with portal login.  
**Related modules:** Student Affairs, School Departments, LMS

---

### Workflow 7: Student Clinic Visit

| Step | Actor | Action |
|------|-------|--------|
| 1 | HR | Opens clinic visit form for student |
| 2 | HR | Records visit details, treatment, outcome |
| 3 | System | Saves visit, updates health file last visit date |
| 4 | HR (optional) | Sends parent notification for specific outcomes |

**Final result:** Clinic visit recorded in student health history.  
**Related modules:** Student Health

---

### Workflow 8: Journal Entry Approval

| Step | Actor | Action |
|------|-------|--------|
| 1 | HR | Creates journal entry with debit/credit lines |
| 2 | HR | Saves as draft or submits for approval |
| 3 | System | Validates balanced amounts |
| 4 | HR | Approves entry (`status = approved`) |
| 5 | System | Locks entry from further edits |

**Final result:** Approved accounting entry in ledger.  
**Related modules:** Financial Resources

---

### Workflow 9: Recruitment to Hire

| Step | Actor | Action |
|------|-------|--------|
| 1 | HR/Recruiter | Creates and publishes job |
| 2 | Applicant | Registers and applies |
| 3 | HR/Recruiter | Reviews application, schedules interview, updates status |
| 4 | HR/Recruiter | Converts applicant to employee |
| 5 | System | Creates `Employee` (+ `User`), sets application `Hired` |

**Final result:** New employee record from hiring pipeline.  
**Related modules:** Recruitment, Employee Management

---

### Workflow 10: Survey Distribution and Response

| Step | Actor | Action |
|------|-------|--------|
| 1 | HR | Creates survey with questions and targets |
| 2 | HR | Sets schedule and launches |
| 3 | System | Makes survey available to targeted employees |
| 4 | Employee | Completes and submits survey |
| 5 | HR | Views aggregated results |

**Final result:** Survey responses collected and reportable.  
**Related modules:** Surveys (same pattern for Evaluations)

---

### Workflow 11: LMS Section Attendance and Marks

| Step | Actor | Action |
|------|-------|--------|
| 1 | Teacher | Opens section attendance for date |
| 2 | Teacher | Marks each student present/absent/late/excused |
| 3 | System | Saves attendance session and records |
| 4 | Teacher | Opens marks for section material |
| 5 | Teacher | Enters and saves student marks |

**Final result:** Student attendance and academic marks recorded.  
**Related modules:** LMS, School Infrastructure

---

### Workflow 12: Change Request (Partial)

| Step | Actor | Action |
|------|-------|--------|
| 1 | Employee | Submits change request with old/new values |
| 2 | HR | Approves or rejects |
| 3 | System | Updates request status only |

**Final result:** Request marked approved/rejected; employee profile **not** auto-updated.  
**Related modules:** Change Requests, Employee Portal

---

## 5. Data and Entity Summary

| Entity / Model | Purpose | Important Fields | Related Module | Relationships |
|----------------|---------|------------------|----------------|---------------|
| `User` | System login identity | `email`, `role`, `permissions`, `status`, `is_super_admin`, `phone` | All portals | Linked by `Employee.user_id`, `SchoolStudent.user_id` |
| `Employee` | Staff/teacher record | `full_name`, `email`, `phone`, `job_title`, `status`, `teacher`, `department_id`, `school_department_id`, `manager_id`, `user_id` | HR, Employee Portal | → Department, SchoolDepartment, Manager (Employee), User |
| `Department` | Org HR department | `name`, `manager_id`, `status` | HR Departments | → Employee (manager); has many Employees |
| `EmployeeChangeRequest` | Profile change request | `title`, `old_value`, `new_value`, `urgency`, `status` | Change Requests | → Employee |
| `EmployeeAttendance` | Clock in/out record | `employee_id`, `work_date`, `clock_in_at`, `clock_out_at` | Attendance | → Employee |
| `AttendanceSettings` | Global shift config | `shift_start_time`, `shift_end_time`, grace windows | Attendance | Singleton config |
| `AttendanceOffDayPolicy` | Off-day/holiday rules | `off_days`, `specific_off_date`, `status` | Attendance | — |
| `EmployeeLeaveRequest` | Leave application | `leave_type`, `start_date`, `end_date`, `status`, `reviewed_by` | Leave | → Employee |
| `SchoolDepartment` | Academic department | `name`, `head_id`, `deputy_head_id`, `status` | School Infrastructure | → Employee (head/deputy); has Grades, Subjects |
| `SchoolGrade` | Grade level | `name`, `school_department_id`, `sort_order` | School Infrastructure | → SchoolDepartment; has Sections |
| `SchoolSection` | Class section | `name`, `lead_teacher_id`, `capacity_max`, `current_enrollment` | School Infrastructure | → SchoolGrade, Employee; has Students, Materials |
| `SchoolSubject` | Subject catalog | `name`, `code`, `school_department_id` | School Infrastructure | → SchoolDepartment |
| `SchoolSubjectGrade` | Subject-grade config | `credits`, `max_score` | School Infrastructure | → Subject, Grade |
| `EmployeeSchoolSubject` | Teacher-subject assignment | `employee_id`, `school_subject_id` | School Infrastructure | → Employee, SchoolSubject |
| `SchoolStudent` | Enrolled student | `full_name`, `student_code`, `national_id`, `guardian_*`, `status`, `school_section_id`, `user_id` | Student Affairs | → Section, Grade, User; has HealthFile, Documents |
| `SchoolStudentHealthFile` | Student health profile | Allergy, chronic, medical history fields | Student Health | 1:1 → SchoolStudent |
| `SchoolStudentClinicVisit` | Clinic visit log | `visit_date`, `primary_reason`, `status`, `action_taken` | Student Health | → SchoolStudent |
| `SchoolStudentDocument` | Enrollment document | `file_path`, `mime_type` | Student Affairs | → SchoolStudent |
| `SchoolSectionMaterial` | Section course assignment | `instructor_id`, `schedule`, `status` | School Infrastructure | → Section, Subject, Employee |
| `SchoolStudentSectionMark` | Student marks | `mark_first/second/third/final`, `total`, `status` | LMS Marks | → Student, SectionMaterial |
| `SchoolSectionAttendanceSession` | Daily section attendance | `attendance_date`, `taken_by_user_id` | LMS Attendance | → Section; has Records |
| `SchoolStudentAttendanceRecord` | Per-student attendance | `status` (present/absent/late/excused) | LMS Attendance | → Session, Student |
| `SchoolSectionTimetableRow/Column/Slot` | Timetable grid | `day_of_week`, times, material, instructor | LMS Timetable | → Section |
| `FinanceAccount` | Ledger account | `code`, `name`, `account_type`, `parent_id` | Finance | Self-referential hierarchy |
| `FinanceJournalEntry` | Journal header | `entry_date`, `status`, `total_debit/credit` | Finance | has Lines; → User (created/approved by) |
| `FinanceJournalEntryLine` | Journal line | `debit_amount`, `credit_amount`, `account_id` | Finance | → Entry, Account |
| `FinanceAccountParentProfile` | Parent/guardian profile | Name parts, `phone`, `email`, `kids_count` | Parents / Finance | → FinanceAccount; has Children |
| `FinanceAccountParentChild` | Parent's child record | Name parts, document paths | Parents / Finance | → ParentProfile |
| `Job` | Job posting | `job_title`, `department`, `status` (draft/published/closed) | Recruitment | has Applications |
| `Application` | Job application | `application_status`, `interview_at`, `feedback_*` | Recruitment | → Job, Applicant |
| `Applicant` | Recruitment user | `email`, `resume_path`, `role_id` | Recruitment | has Applications; separate `users` table |
| `Survey` / `Evaluation` | Survey/eval header | `title`, `status`, `start_at`, `end_at`, `is_anonymous` | Surveys / Evaluations | has Questions, Targets, Responses |
| `SurveyResponse` / `EvaluationResponse` | Submitted response | `employee_id`, `content_version` | Surveys / Evaluations | → Survey/Evaluation, Employee; has Answers |
| `Course` / `CourseMaterial` | LMS content | `title`, `file_path`, `file_type` | LMS | Shared `lms_content` table |
| `Document` | Generic file storage | `stored_name`, `sha256_hash`, `uploaded_by` | Document | — |

---

## 6. Requirements Summary Table

| Requirement ID | Requirement Name | Module | Actors | Status |
|----------------|------------------|--------|--------|--------|
| REQ-AUTH-001 | User Login | Authentication | All users | Implemented |
| REQ-AUTH-002 | HR Access Check API | Authentication | Guest | Implemented |
| REQ-AUTH-003 | Switch to Employee Portal | Authentication | HR user | Implemented |
| REQ-AUTH-004 | Password Reset & Email Verify | Authentication | Guest/User | Implemented |
| REQ-HR-DASH-001 | View HR Dashboard | HR Dashboard | HR | Implemented |
| REQ-HR-EMP-001 | Employee Directory | Employees | HR | Implemented |
| REQ-HR-EMP-002 | Employee Onboarding | Employees | HR | Implemented |
| REQ-HR-EMP-003 | View Employee Profile | Employees | HR | Implemented |
| REQ-HR-EMP-004 | Change Employee Status / Delete | Employees | HR | Implemented |
| REQ-HR-DEPT-001 | CRUD Organization Departments | Org Departments | HR | Implemented |
| REQ-HR-SCH-001 | Manage School Departments | School Infrastructure | HR | Implemented |
| REQ-HR-SCH-002 | Infrastructure Hierarchy Browser | School Infrastructure | HR | Implemented |
| REQ-HR-SCH-003 | Manage Grades and Sections | School Infrastructure | HR | Implemented |
| REQ-HR-SCH-004 | Subjects Catalog | School Infrastructure | HR | Implemented |
| REQ-HR-SCH-005 | Section Materials & Student Roster | School Infrastructure | HR | Implemented |
| REQ-HR-ATT-001 | Attendance Configuration | Attendance | HR | Implemented |
| REQ-HR-ATT-002 | Attendance Overview & Management | Attendance | HR | Implemented |
| REQ-EMP-ATT-001 | Employee Clock In/Out | Attendance | Employee | Implemented |
| REQ-HR-LEV-001 | Review Leave Requests | Leave | HR | Implemented |
| REQ-EMP-LEV-001 | Submit Leave Request | Leave | Employee | Implemented |
| REQ-HR-SRV-001 | Create and Launch Survey | Surveys | HR | Implemented |
| REQ-HR-SRV-002 | View Survey Results | Surveys | HR | Implemented |
| REQ-EMP-SRV-001 | Respond to Survey | Surveys | Employee | Implemented |
| REQ-HR-EVL-001 | Create and Launch Evaluation | Evaluations | HR | Implemented |
| REQ-EMP-EVL-001 | Respond to Evaluation | Evaluations | Employee | Implemented |
| REQ-HR-STU-001 | Student Registry | Student Affairs | HR | Implemented |
| REQ-HR-STU-002 | Enroll New Student | Student Affairs | HR | Implemented |
| REQ-HR-STU-003 | Update and Delete Student | Student Affairs | HR | Implemented |
| REQ-HR-STU-004 | Export Student Registry | Student Affairs | HR | Implemented |
| REQ-HR-HLT-001 | Health File Management | Student Health | HR | Implemented |
| REQ-HR-HLT-002 | Record Clinic Visit | Student Health | HR | Implemented |
| REQ-HR-HLT-003 | Visit Records & Parent Notify | Student Health | HR | Implemented |
| REQ-HR-PAR-001 | Parents Directory | Parents | HR | Implemented |
| REQ-HR-FIN-001 | Chart of Accounts | Finance | HR | Implemented |
| REQ-HR-FIN-002 | Create Parent Finance Account | Finance | HR | Implemented |
| REQ-HR-FIN-003 | Journal Entry Management | Finance | HR | Implemented |
| REQ-HR-FIN-004 | Account Statement | Finance | HR | Implemented |
| REQ-HR-FIN-005 | Financial Reports | Finance | HR | Implemented |
| REQ-HR-REC-001 | Job Posting Lifecycle | Recruitment | HR/Recruiter | Implemented |
| REQ-HR-REC-002 | Application Review & Interview | Recruitment | HR/Recruiter | Implemented |
| REQ-HR-REC-003 | Convert Applicant to Employee | Recruitment | HR/Recruiter | Implemented |
| REQ-HR-REC-004 | Recruitment Reports | Recruitment | HR | Implemented |
| REQ-REC-APP-001 | Applicant Self-Service | Recruitment | Applicant | Implemented |
| REQ-HR-CHG-001 | Review Change Requests | Change Requests | HR | Partially implemented |
| REQ-EMP-CHG-001 | Submit Change Request | Change Requests | Employee | Implemented |
| REQ-HR-CRR-001 | HR Career Paths View | Career Paths | HR | Partially implemented |
| REQ-EMP-CRR-001 | Employee My Development | Career Paths | Employee | Partially implemented |
| REQ-HR-MGT-001 | Grant Permissions to Employee | User Permissions | HR (manage_hr) | Implemented |
| REQ-HR-MGT-002 | Create External HR User | User Permissions | HR (manage_hr) | Implemented |
| REQ-HR-MGT-003 | Manage HR User Accounts | User Permissions | HR (manage_hr) | Implemented |
| REQ-EMP-001 | Employee Dashboard | Employee Portal | Employee | Implemented |
| REQ-EMP-002 | View My Profile | Employee Portal | Employee | Implemented |
| REQ-EMP-003 | My Attendance & Leave History | Employee Portal | Employee | Implemented |
| REQ-EMP-004 | Survey & Evaluation Inbox | Employee Portal | Employee | Implemented |
| REQ-EMP-005 | Terminated Employee Restriction | Employee Portal | Terminated employee | Implemented |
| REQ-LMS-001 | LMS Portal Login & Routing | LMS | Student/Teacher | Implemented |
| REQ-LMS-002 | Content Library & Upload | LMS | Teacher | Implemented |
| REQ-LMS-003 | Student Self-Service (LMS) | LMS | Student | Implemented |
| REQ-LMS-004 | Section Attendance (Student) | LMS | Teacher | Implemented |
| REQ-LMS-005 | Section Marks | LMS | Teacher | Implemented |
| REQ-LMS-006 | Timetable Management | LMS | HR/Teacher | Implemented |
| REQ-LMS-007 | My Classes Hub | LMS | Teacher | Implemented |
| REQ-LMS-008 | Teacher-Scoped Registry & Hierarchy | LMS | Teacher/Leader | Implemented |
| REQ-DOC-001 | Upload & View Documents | Document | Authenticated user | Implemented |
| REQ-DASH-001 | Analytics Dashboard | Dashboard | HR admin | Needs confirmation |

---

*End of requirements document. Generated from CultureHR (HRMS-NEW) codebase analysis.*
