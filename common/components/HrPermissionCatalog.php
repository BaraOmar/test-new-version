<?php

namespace common\components;

/**
 * Canonical HR portal permission keys grouped for the Manage HR UI.
 *
 * Module-root keys (e.g. "employees") grant every feature beneath that module.
 * Feature keys can be granted individually for fine-grained access.
 */
class HrPermissionCatalog
{
  /** @var array<string, string>|null */
  private static ?array $labels = null;

  /** @var array<string, string|null>|null */
  private static ?array $parents = null;

  /** @var array<string, bool>|null */
  private static ?array $moduleRoots = null;

  /**
   * @return string[]
   */
  public static function allKeys(): array
  {
    return array_keys(self::labels());
  }

  /**
   * @return array<string, string>
   */
  public static function labels(): array
  {
    if (self::$labels !== null) {
      return self::$labels;
    }

    self::$labels = [
      'dashboard' => 'Dashboard',
      'employees' => 'Employees Module (full access)',
      'employees_directory' => 'Employee Directory',
      'school_departments' => 'School Departments',
      'school_departments_hierarchy' => 'Infrastructure Hierarchy',
      'school_departments_create' => 'Add New Department',
      'subjects' => 'Subjects Catalog',
      'surveys' => 'Surveys',
      'evaluations' => 'Evaluations',
      'timetable' => 'Timetable',
      'student_affairs' => 'Student Affairs (full access)',
      'student_affairs_registry' => 'Student Registry',
      'student_affairs_create' => 'Add New Student',
      'student_health' => 'Student Health (full access)',
      'student_health_files' => 'Health Files',
      'student_health_clinic' => 'Record Clinic Visit',
      'student_health_records' => 'Visit Records',
      'parents_directory' => 'Parents Directory',
      'transportation' => 'Transportations (full access)',
      'transportation_areas' => 'Areas',
      'transportation_buses' => 'Create Buses',
      'transportation_assign_buses' => 'Assign Buses',
      'recruitment' => 'Recruit Management (full access)',
      'recruitment_jobs' => 'Create a New Job',
      'recruitment_reports' => 'Report Generating',
      'recruitment_applications' => 'Applications & Interviews',
      'recruitment_application_status' => 'Application Status',
      'change_requests' => 'Change Requests',
      'manage_hr' => 'Manage HR & Permissions',
      'lms' => 'LMS Administration',
    ];

    return self::$labels;
  }

  /**
   * @return array<string, string|null> child => parent
   */
  public static function parents(): array
  {
    if (self::$parents !== null) {
      return self::$parents;
    }

    self::$parents = [
      'employees_directory' => 'employees',
      'school_departments' => 'employees',
      'school_departments_hierarchy' => 'school_departments',
      'school_departments_create' => 'school_departments',
      'subjects' => 'employees',
      'student_affairs' => 'employees',
      'student_affairs_registry' => 'student_affairs',
      'student_affairs_create' => 'student_affairs',
      'parents_directory' => 'employees',
      'student_health_files' => 'student_health',
      'student_health_clinic' => 'student_health',
      'student_health_records' => 'student_health',
      'transportation_areas' => 'transportation',
      'transportation_buses' => 'transportation',
      'transportation_assign_buses' => 'transportation',
      'recruitment_jobs' => 'recruitment',
      'recruitment_reports' => 'recruitment',
      'recruitment_applications' => 'recruitment',
      'recruitment_application_status' => 'recruitment',
    ];

    return self::$parents;
  }

  /**
   * @return array<string, bool>
   */
  public static function moduleRoots(): array
  {
    if (self::$moduleRoots !== null) {
      return self::$moduleRoots;
    }

    self::$moduleRoots = array_fill_keys([
      'dashboard',
      'employees',
      'surveys',
      'evaluations',
      'timetable',
      'student_affairs',
      'student_health',
      'transportation',
      'recruitment',
      'change_requests',
      'manage_hr',
      'lms',
    ], true);

    return self::$moduleRoots;
  }

  public static function normalizeKey(string $permission): string
  {
    $permission = trim($permission);
    $aliases = [
      'requests' => 'change_requests',
    ];

    return $aliases[$permission] ?? $permission;
  }

  /**
   * @param string[] $permissions
   * @return string[]
   */
  public static function normalizeList(array $permissions): array
  {
    $normalized = [];
    foreach ($permissions as $permission) {
      $key = self::normalizeKey((string)$permission);
      if ($key !== '' && isset(self::labels()[$key])) {
        $normalized[] = $key;
      }
    }

    return array_values(array_unique($normalized));
  }

  /**
   * @return string[]
   */
  public static function ancestorKeys(string $permission): array
  {
    $permission = self::normalizeKey($permission);
    $ancestors = [];
    $parent = self::parents()[$permission] ?? null;

    while ($parent !== null) {
      $ancestors[] = $parent;
      $parent = self::parents()[$parent] ?? null;
    }

    return $ancestors;
  }

  public static function isDescendantOf(string $child, string $ancestor): bool
  {
    $child = self::normalizeKey($child);
    $ancestor = self::normalizeKey($ancestor);

    if ($child === $ancestor) {
      return true;
    }

    return in_array($ancestor, self::ancestorKeys($child), true);
  }

  /**
   * @param string[] $stored
   */
  public static function isGranted(string $requested, array $stored): bool
  {
    $requested = self::normalizeKey($requested);
    $stored = self::normalizeList($stored);

    if (in_array($requested, $stored, true)) {
      return true;
    }

    foreach ($stored as $granted) {
      if (in_array($granted, self::ancestorKeys($requested), true)) {
        return true;
      }
    }

    if (isset(self::moduleRoots()[$requested])) {
      foreach ($stored as $granted) {
        if (self::isDescendantOf($granted, $requested)) {
          return true;
        }
      }
    }

    return false;
  }

  /**
   * Expand stored permissions so the UI can pre-check feature boxes when a module root is stored.
   *
   * @param string[] $stored
   * @return string[]
   */
  public static function expandForDisplay(array $stored): array
  {
    $stored = self::normalizeList($stored);
    $expanded = $stored;

    foreach ($stored as $granted) {
      if (!isset(self::moduleRoots()[$granted])) {
        continue;
      }

      foreach (self::labels() as $key => $_label) {
        if (self::isDescendantOf($key, $granted)) {
          $expanded[] = $key;
        }
      }
    }

    return array_values(array_unique($expanded));
  }

  /**
   * @return array<string, array{title:string,icon:string,items:array<string,string>}>
   */
  public static function permissionGroups(): array
  {
    return [
      'core' => [
        'title' => 'Core & People',
        'icon' => 'grid_view',
        'items' => self::pick([
          'dashboard',
          'employees',
          'employees_directory',
          'school_departments',
          'school_departments_hierarchy',
          'school_departments_create',
          'subjects',
        ]),
      ],
      'analytics' => [
        'title' => 'Analytics',
        'icon' => 'monitoring',
        'items' => self::pick([
          'surveys',
          'evaluations',
          'timetable',
        ]),
      ],
      'students' => [
        'title' => 'Student Services',
        'icon' => 'school',
        'items' => self::pick([
          'student_affairs',
          'student_affairs_registry',
          'student_affairs_create',
          'student_health',
          'student_health_files',
          'student_health_clinic',
          'student_health_records',
          'parents_directory',
        ]),
      ],
      'transportation' => [
        'title' => 'Transportations',
        'icon' => 'directions_bus',
        'items' => self::pick([
          'transportation',
          'transportation_areas',
          'transportation_buses',
          'transportation_assign_buses',
        ]),
      ],
      'operations' => [
        'title' => 'Operations',
        'icon' => 'work',
        'items' => self::pick([
          'recruitment',
          'recruitment_jobs',
          'recruitment_reports',
          'recruitment_applications',
          'recruitment_application_status',
          'change_requests',
        ]),
      ],
      'administration' => [
        'title' => 'Administration',
        'icon' => 'admin_panel_settings',
        'items' => self::pick([
          'manage_hr',
          'lms',
        ]),
      ],
    ];
  }

  /**
   * @return array<string, string[]>
   */
  public static function presets(): array
  {
    return [
      'full' => self::normalizeList([
        'dashboard',
        'employees',
        'surveys',
        'evaluations',
        'timetable',
        'student_affairs',
        'student_health',
        'parents_directory',
        'transportation',
        'recruitment',
        'change_requests',
      ]),
      'limited' => ['dashboard', 'employees_directory'],
    ];
  }

  /**
   * Ordered permission keys mapped to the first HR portal page users should land on.
   *
   * @return array<string, array<int, string>>
   */
  public static function portalHomeRoutes(): array
  {
    return [
      'dashboard' => ['/hr/hr/dashboard'],
      'employees' => ['/hr/hr/employees'],
      'employees_directory' => ['/hr/hr/employees'],
      'school_departments' => ['/hr/school-department/index'],
      'school_departments_hierarchy' => ['/hr/school-department/hierarchy'],
      'school_departments_create' => ['/hr/school-department/create'],
      'subjects' => ['/hr/school-department/subjects'],
      'surveys' => ['/hr/survey/index'],
      'evaluations' => ['/hr/evaluation/index'],
      'timetable' => ['/lms/timetable/index'],
      'student_affairs' => ['/hr/student-affairs/students'],
      'student_affairs_registry' => ['/hr/student-affairs/students'],
      'student_affairs_create' => ['/hr/student-affairs/create'],
      'student_health' => ['/hr/student-health/index'],
      'student_health_files' => ['/hr/student-health/files'],
      'student_health_clinic' => ['/hr/student-health/clinic-visits'],
      'student_health_records' => ['/hr/student-health/clinic-visit-records'],
      'parents_directory' => ['/hr/parents/index'],
      'transportation' => ['/hr/transportation/hub'],
      'transportation_areas' => ['/hr/transportation/areas'],
      'transportation_buses' => ['/hr/transportation/buses'],
      'transportation_assign_buses' => ['/hr/transportation/assign-buses'],
      'recruitment' => ['/hr/recruitment/index'],
      'recruitment_jobs' => ['/hr/recruitment/job-form'],
      'recruitment_reports' => ['/hr/recruitment/reports'],
      'recruitment_applications' => ['/hr/recruitment/applications'],
      'recruitment_application_status' => ['/hr/recruitment/application-status'],
      'change_requests' => ['/hr/hr/requests'],
      'manage_hr' => ['/hr/hr/manage-hr'],
      'lms' => ['/lms/portal/teacher'],
    ];
  }

  /**
   * @param string[] $keys
   * @return array<string, string>
   */
  private static function pick(array $keys): array
  {
    $labels = self::labels();
    $items = [];
    foreach ($keys as $key) {
      if (isset($labels[$key])) {
        $items[$key] = $labels[$key];
      }
    }

    return $items;
  }
}
