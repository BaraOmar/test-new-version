<?php

namespace common\components;

use common\models\User;
use common\modules\employee\models\Employee;
use common\modules\employee\models\SchoolDepartment;
use common\modules\employee\models\SchoolGrade;
use common\modules\employee\models\SchoolSection;
use common\modules\employee\models\SchoolSectionAttendanceSession;
use common\modules\employee\models\SchoolSectionMaterial;
use common\modules\employee\models\SchoolSectionTimetableColumn;
use common\modules\employee\models\SchoolSectionTimetableRow;
use common\modules\employee\models\SchoolSectionTimetableSlot;
use common\modules\employee\models\SchoolStudent;
use common\modules\employee\models\SchoolStudentAttendanceRecord;
use common\modules\employee\models\SchoolStudentClinicVisit;
use common\modules\employee\models\SchoolStudentHealthFile;
use common\modules\employee\models\SchoolSubject;
use common\modules\employee\services\SchoolMyClassesService;
use common\modules\employee\services\SchoolSectionMarksService;
use common\modules\employee\services\SchoolStudentCertificateService;
use Yii;
use yii\db\ActiveQuery;

class LmsPortal
{
    public const TEACHER_PORTAL_TYPE_TEACHER = 'teacher';
    public const TEACHER_PORTAL_TYPE_MANAGER = 'manager';
    public const TEACHER_PORTAL_TYPE_DEPUTY = 'deputy';

    public const STUDENT_AFFAIRS_ACCESS_STUDENT = 'student';
    public const STUDENT_AFFAIRS_ACCESS_CLASS_TEACHER = 'class_teacher';
    public const STUDENT_AFFAIRS_ACCESS_DEPARTMENT_LEADER = 'department_leader';

    public const INFRASTRUCTURE_ACCESS_STUDENT = 'student';
    public const INFRASTRUCTURE_ACCESS_DEPARTMENT_LEADER = 'department_leader';
    public const INFRASTRUCTURE_ACCESS_CLASS_TEACHER = 'class_teacher';

    public const SUBJECTS_ACCESS_STUDENT = 'student';
    public const SUBJECTS_ACCESS_TEACHER = 'teacher';
    public const SUBJECTS_ACCESS_DEPARTMENT_LEADER = 'department_leader';

    public const TIMETABLE_ACCESS_STUDENT = 'student';
    public const TIMETABLE_ACCESS_DEPARTMENT_LEADER = 'department_leader';
    public const TIMETABLE_ACCESS_INSTRUCTOR = 'instructor';
    /** @deprecated Use TIMETABLE_ACCESS_INSTRUCTOR */
    public const TIMETABLE_ACCESS_CLASS_TEACHER = 'instructor';

    public static function findLinkedEmployee(User $user, bool $withSchoolLeadership = false): ?Employee
    {
        if (!$user->isEmployee()) {
            return null;
        }

        $query = Employee::find()->where(['user_id' => (int)$user->id]);
        if ($withSchoolLeadership) {
            $query->with(['managedSchoolDepartments', 'deputySchoolDepartmentRecord', 'schoolDepartment']);
        }

        return $query->one();
    }

    public static function isTeacherEmployee(User $user): bool
    {
        $employee = self::findLinkedEmployee($user);

        return $employee !== null && $employee->isTeacher();
    }

    public static function canAccessTeacherLms(User $user): bool
    {
        if ($user->isSuperHrAdmin()) {
            return true;
        }

        $employee = self::findLinkedEmployee($user);

        return $employee !== null && $employee->canAccessTeacherLms();
    }

    public static function canAccessLms(User $user): bool
    {
        if ($user->isStudent()) {
            return true;
        }

        if ($user->isParent()) {
            return true;
        }

        return self::canAccessTeacherLms($user);
    }

    public static function isTeacherPortalUser(User $user): bool
    {
        return self::canAccessTeacherLms($user);
    }

    public static function isStudentPortalUser(User $user): bool
    {
        return $user->isStudent();
    }

    public static function isParentPortalUser(User $user): bool
    {
        return $user->isParent();
    }

    /**
     * @return array<int, string>
     */
    public static function homeRoute(?User $user = null): array
    {
        $user = $user ?? Yii::$app->user->identity;
        if (!$user instanceof User) {
            return ['/lms/portal/login'];
        }

        if (self::isStudentPortalUser($user)) {
            return ['/lms/portal/student'];
        }

        if (self::isParentPortalUser($user)) {
            return ['/lms/portal/parent'];
        }

        if (self::isTeacherPortalUser($user)) {
            return ['/lms/portal/teacher'];
        }

        return ['/lms/portal/login'];
    }

    public static function displayName(User $user): string
    {
        if ($user->isStudent()) {
            $student = SchoolStudent::find()->where(['user_id' => (int)$user->id])->one();
            if ($student !== null) {
                return $student->getDisplayFullName();
            }
        }

        if ($user->isParent()) {
            $profile = \common\modules\finance\models\FinanceAccountParentProfile::find()
                ->where(['user_id' => (int)$user->id])
                ->one();
            if ($profile !== null) {
                return $profile->getFullName();
            }
        }

        $employee = self::findLinkedEmployee($user);
        if ($employee !== null && trim((string)$employee->full_name) !== '') {
            return (string)$employee->full_name;
        }

        $name = trim((string)($user->username ?? ''));

        return $name !== '' ? $name : 'User';
    }

    public static function roleLabel(User $user): string
    {
        if ($user->isStudent()) {
            return 'Student';
        }

        if ($user->isParent()) {
            return 'Parent';
        }

        if ($user->isSuperHrAdmin()) {
            return 'Super Admin';
        }

        if (self::isTeacherEmployee($user)) {
            return self::teacherPortalRoleLabel($user);
        }

        return 'User';
    }

    public static function teacherPortalType(User $user): string
    {
        if ($user->isSuperHrAdmin()) {
            return self::TEACHER_PORTAL_TYPE_TEACHER;
        }

        $employee = self::findLinkedEmployee($user, true);
        if ($employee === null || !$employee->isTeacher() || !SchoolDepartment::isEnabled()) {
            return self::TEACHER_PORTAL_TYPE_TEACHER;
        }

        if ($employee->isSchoolDepartmentHead()) {
            return self::TEACHER_PORTAL_TYPE_MANAGER;
        }

        if ($employee->isSchoolDepartmentDeputy()) {
            return self::TEACHER_PORTAL_TYPE_DEPUTY;
        }

        return self::TEACHER_PORTAL_TYPE_TEACHER;
    }

    public static function teacherPortalSubtitle(User $user): string
    {
        return match (self::teacherPortalType($user)) {
            self::TEACHER_PORTAL_TYPE_MANAGER => 'Manager Portal',
            self::TEACHER_PORTAL_TYPE_DEPUTY => 'Deputy Portal',
            default => 'Teacher Portal',
        };
    }

    public static function teacherPortalRoleLabel(User $user): string
    {
        return match (self::teacherPortalType($user)) {
            self::TEACHER_PORTAL_TYPE_MANAGER => 'Department Manager',
            self::TEACHER_PORTAL_TYPE_DEPUTY => 'Deputy Head',
            default => 'Teacher',
        };
    }

    public static function teacherPortalWelcomeMessage(User $user): string
    {
        $employee = self::findLinkedEmployee($user, true);
        $departmentName = $employee !== null ? $employee->getReportingDepartmentName() : '—';
        $departmentPhrase = $departmentName !== '—'
            ? 'the ' . $departmentName . ' department'
            : 'your assigned department';

        return match (self::teacherPortalType($user)) {
            self::TEACHER_PORTAL_TYPE_MANAGER => 'You are the manager of '
                . $departmentPhrase
                . '. Use this portal to oversee learning content and department development.',
            self::TEACHER_PORTAL_TYPE_DEPUTY => 'You are the deputy head of '
                . $departmentPhrase
                . '. Use this portal to support department leadership and professional development.',
            default => 'Manage learning content and track professional development.',
        };
    }

    public static function teacherPortalLeadershipTitle(User $user): ?string
    {
        $employee = self::findLinkedEmployee($user, true);
        if ($employee === null) {
            return null;
        }

        $departmentName = $employee->getReportingDepartmentName();
        if ($departmentName === '—') {
            return null;
        }

        return match (self::teacherPortalType($user)) {
            self::TEACHER_PORTAL_TYPE_MANAGER => 'Manager of ' . $departmentName,
            self::TEACHER_PORTAL_TYPE_DEPUTY => 'Deputy Head of ' . $departmentName,
            default => null,
        };
    }

    public static function isStudentAffairsEnabled(): bool
    {
        return SchoolStudent::isEnabled() && SchoolDepartment::isEnabled();
    }

    public static function findLinkedStudent(User $user): ?SchoolStudent
    {
        if (!$user->isStudent() || !self::isStudentAffairsEnabled()) {
            return null;
        }

        return SchoolStudent::find()
            ->with(['grade.department', 'section.grade.department', 'user', 'healthFile'])
            ->where(['user_id' => (int)$user->id])
            ->one();
    }

    /**
     * Section used for student schedule/subjects when only grade is set on enrollment.
     */
    public static function resolveStudentSectionId(SchoolStudent $student): int
    {
        $sectionId = (int)$student->school_section_id;
        if ($sectionId > 0) {
            return $sectionId;
        }

        $gradeId = (int)$student->school_grade_id;
        if ($gradeId <= 0 || !SchoolSection::isEnabled()) {
            return 0;
        }

        $sectionIds = SchoolSection::find()
            ->select(['id'])
            ->where(['school_grade_id' => $gradeId])
            ->orderBy(['name' => SORT_ASC])
            ->column();

        return count($sectionIds) === 1 ? (int)$sectionIds[0] : 0;
    }

    public static function resolveStudentSection(SchoolStudent $student): ?SchoolSection
    {
        $sectionId = self::resolveStudentSectionId($student);
        if ($sectionId <= 0) {
            return null;
        }

        if ($student->section !== null && (int)$student->section->id === $sectionId) {
            return $student->section;
        }

        return SchoolSection::find()
            ->with(['grade.department'])
            ->where(['id' => $sectionId])
            ->one();
    }

    public static function canAccessOwnStudentSchedule(User $user): bool
    {
        if (!$user->isStudent() || !self::isStudentAffairsEnabled()) {
            return false;
        }

        return self::findLinkedStudent($user) !== null;
    }

    public static function studentAffairsAccessType(User $user): ?string
    {
        if (!self::isStudentAffairsEnabled()) {
            return null;
        }

        if ($user->isStudent()) {
            return self::STUDENT_AFFAIRS_ACCESS_STUDENT;
        }

        if (!$user->isSuperHrAdmin() && self::isTeacherEmployee($user)) {
            $employee = self::findLinkedEmployee($user, true);
            if ($employee === null || !$employee->canAccessTeacherLms()) {
                return null;
            }

            if ($employee->isSchoolDepartmentHead() || $employee->isSchoolDepartmentDeputy()) {
                return self::STUDENT_AFFAIRS_ACCESS_DEPARTMENT_LEADER;
            }

            if (self::isClassTeacher($user)) {
                return self::STUDENT_AFFAIRS_ACCESS_CLASS_TEACHER;
            }
        }

        return null;
    }

    public static function isClassTeacher(User $user): bool
    {
        if (!self::isStudentAffairsEnabled()) {
            return false;
        }

        $employee = self::findLinkedEmployee($user);
        if ($employee === null || !SchoolSection::isEnabled()) {
            return false;
        }

        return self::buildClassTeacherSectionQuery($employee)->exists();
    }

    /**
     * @return int[]
     */
    public static function getAccessibleSectionIds(User $user): array
    {
        $employee = self::findLinkedEmployee($user);
        if ($employee === null || !SchoolSection::isEnabled()) {
            return [];
        }

        return array_values(array_unique(array_map(
            'intval',
            self::buildClassTeacherSectionQuery($employee)->column()
        )));
    }

    private static function buildClassTeacherSectionQuery(Employee $employee): \yii\db\ActiveQuery
    {
        $query = SchoolSection::find()
            ->alias('section')
            ->select('section.id')
            ->where(['section.lead_teacher_id' => (int)$employee->id]);

        if (Employee::hasColumn('school_department_id')) {
            $employeeDepartmentId = (int)($employee->school_department_id ?? 0);
            if ($employeeDepartmentId <= 0) {
                $query->andWhere('0=1');

                return $query;
            }

            $query->innerJoin(
                ['grade' => SchoolGrade::tableName()],
                'grade.id = section.school_grade_id'
            )->andWhere(['grade.school_department_id' => $employeeDepartmentId]);
        }

        return $query;
    }

    public static function getSectionDepartmentId(SchoolSection $section): int
    {
        return (int)($section->grade?->school_department_id ?? 0);
    }

    public static function getEmployeeSchoolDepartmentId(User $user): int
    {
        $employee = self::findLinkedEmployee($user, true);
        if ($employee === null || !Employee::hasColumn('school_department_id')) {
            return 0;
        }

        return (int)($employee->school_department_id ?? 0);
    }

    public static function isDepartmentLeaderFor(User $user, int $departmentId): bool
    {
        return $departmentId > 0
            && in_array($departmentId, self::getAccessibleDepartmentIds($user), true);
    }

    public static function sectionBelongsToAccessibleDepartment(User $user, SchoolSection $section): bool
    {
        $departmentId = self::getSectionDepartmentId($section);
        if ($departmentId <= 0) {
            return false;
        }

        $accessType = self::getInfrastructureAccessType($user);
        if ($accessType === self::INFRASTRUCTURE_ACCESS_DEPARTMENT_LEADER) {
            return self::isDepartmentLeaderFor($user, $departmentId);
        }

        if ($accessType === self::INFRASTRUCTURE_ACCESS_CLASS_TEACHER) {
            $employeeDepartmentId = self::getEmployeeSchoolDepartmentId($user);

            return $employeeDepartmentId > 0 && $employeeDepartmentId === $departmentId;
        }

        return false;
    }

    /**
     * @return int[]
     */
    public static function getAccessibleDepartmentIds(User $user): array
    {
        $employee = self::findLinkedEmployee($user, true);
        if ($employee === null) {
            return [];
        }

        $departmentIds = [];
        foreach ($employee->managedSchoolDepartments as $department) {
            $departmentIds[] = (int)$department->id;
        }

        if ($employee->deputySchoolDepartmentRecord !== null) {
            $departmentIds[] = (int)$employee->deputySchoolDepartmentRecord->id;
        }

        return array_values(array_unique(array_filter($departmentIds)));
    }

    public static function canAccessStudentRegistry(User $user): bool
    {
        $accessType = self::studentAffairsAccessType($user);

        return in_array($accessType, [
            self::STUDENT_AFFAIRS_ACCESS_CLASS_TEACHER,
            self::STUDENT_AFFAIRS_ACCESS_DEPARTMENT_LEADER,
        ], true) && self::buildAccessibleStudentsQuery($user) !== null;
    }

    public static function canAccessOwnStudentProfile(User $user): bool
    {
        return self::studentAffairsAccessType($user) === self::STUDENT_AFFAIRS_ACCESS_STUDENT
            && self::findLinkedStudent($user) !== null;
    }

    public static function canAccessOwnStudentHealthRecord(User $user): bool
    {
        if (!SchoolStudentHealthFile::isEnabled() || !self::canAccessOwnStudentProfile($user)) {
            return false;
        }

        $student = self::findLinkedStudent($user);

        return $student !== null && $student->healthFile instanceof SchoolStudentHealthFile;
    }

    public static function studentRegistryScopeLabel(User $user): string
    {
        return match (self::studentAffairsAccessType($user)) {
            self::STUDENT_AFFAIRS_ACCESS_CLASS_TEACHER => 'Your class only',
            self::STUDENT_AFFAIRS_ACCESS_DEPARTMENT_LEADER => 'Your department only',
            default => '',
        };
    }

    public static function buildAccessibleStudentsQuery(User $user): ?ActiveQuery
    {
        if (!self::isStudentAffairsEnabled()) {
            return null;
        }

        $accessType = self::studentAffairsAccessType($user);
        if (!in_array($accessType, [
            self::STUDENT_AFFAIRS_ACCESS_CLASS_TEACHER,
            self::STUDENT_AFFAIRS_ACCESS_DEPARTMENT_LEADER,
        ], true)) {
            return null;
        }

        $query = SchoolStudent::find()
            ->alias('student')
            ->joinWith(['grade grade', 'section section', 'section.grade sectionGrade'])
            ->orderBy(['student.full_name' => SORT_ASC]);

        if ($accessType === self::STUDENT_AFFAIRS_ACCESS_DEPARTMENT_LEADER) {
            $departmentIds = self::getAccessibleDepartmentIds($user);
            if ($departmentIds === []) {
                return null;
            }

            $query->andWhere([
                'or',
                ['grade.school_department_id' => $departmentIds],
                ['sectionGrade.school_department_id' => $departmentIds],
            ]);

            return $query;
        }

        $sectionIds = self::getAccessibleSectionIds($user);
        if ($sectionIds === []) {
            return null;
        }

        $query->andWhere(['student.school_section_id' => $sectionIds]);

        return $query;
    }

    public static function canViewStudent(User $user, SchoolStudent $student): bool
    {
        if (!self::isStudentAffairsEnabled()) {
            return false;
        }

        if ($user->isStudent()) {
            return (int)$student->user_id === (int)$user->id;
        }

        $query = self::buildAccessibleStudentsQuery($user);
        if ($query === null) {
            return false;
        }

        return $query->andWhere(['student.id' => (int)$student->id])->exists();
    }

    public static function findAccessibleStudent(User $user, int $studentId): ?SchoolStudent
    {
        if ($studentId <= 0) {
            return null;
        }

        if ($user->isStudent()) {
            $student = self::findLinkedStudent($user);

            return $student !== null && (int)$student->id === $studentId ? $student : null;
        }

        $query = self::buildAccessibleStudentsQuery($user);
        if ($query === null) {
            return null;
        }

        /** @var SchoolStudent|null $student */
        $student = $query
            ->andWhere(['student.id' => $studentId])
            ->with(['grade.department', 'section.grade.department', 'user'])
            ->one();

        return $student;
    }

    public static function canAccessInfrastructureHierarchy(User $user): bool
    {
        return self::getInfrastructureAccessType($user) !== null;
    }

    public static function getInfrastructureAccessType(User $user): ?string
    {
        if (!SchoolDepartment::isEnabled() || !SchoolSection::isEnabled()) {
            return null;
        }

        if ($user->isStudent()) {
            $student = self::findLinkedStudent($user);

            return $student !== null && (int)$student->school_section_id > 0
                ? self::INFRASTRUCTURE_ACCESS_STUDENT
                : null;
        }

        if (!$user->isSuperHrAdmin() && self::isTeacherEmployee($user)) {
            $employee = self::findLinkedEmployee($user, true);
            if ($employee === null || !$employee->canAccessTeacherLms()) {
                return null;
            }

            if ($employee->isSchoolDepartmentHead() || $employee->isSchoolDepartmentDeputy()) {
                return self::INFRASTRUCTURE_ACCESS_DEPARTMENT_LEADER;
            }

            if (self::isClassTeacher($user)) {
                return self::INFRASTRUCTURE_ACCESS_CLASS_TEACHER;
            }
        }

        return null;
    }

    public static function canViewSection(User $user, SchoolSection $section): bool
    {
        $accessType = self::getInfrastructureAccessType($user);
        if ($accessType === null) {
            return false;
        }

        if ($accessType === self::INFRASTRUCTURE_ACCESS_STUDENT) {
            $student = self::findLinkedStudent($user);

            return $student !== null && (int)$student->school_section_id === (int)$section->id;
        }

        if ($accessType === self::INFRASTRUCTURE_ACCESS_CLASS_TEACHER) {
            return in_array((int)$section->id, self::getAccessibleSectionIds($user), true)
                && self::sectionBelongsToAccessibleDepartment($user, $section);
        }

        return $accessType === self::INFRASTRUCTURE_ACCESS_DEPARTMENT_LEADER
            && self::sectionBelongsToAccessibleDepartment($user, $section);
    }

    public static function canManageSectionAsDepartmentLeader(User $user, SchoolSection $section): bool
    {
        return self::getInfrastructureAccessType($user) === self::INFRASTRUCTURE_ACCESS_DEPARTMENT_LEADER
            && self::canViewSection($user, $section);
    }

    public static function canManageSectionAsClassTeacher(User $user, SchoolSection $section): bool
    {
        return self::getInfrastructureAccessType($user) === self::INFRASTRUCTURE_ACCESS_CLASS_TEACHER
            && self::canViewSection($user, $section);
    }

    public static function canCreateSectionInDepartment(User $user, int $departmentId): bool
    {
        return self::getInfrastructureAccessType($user) === self::INFRASTRUCTURE_ACCESS_DEPARTMENT_LEADER
            && $departmentId > 0
            && in_array($departmentId, self::getAccessibleDepartmentIds($user), true);
    }

    public static function canCreateSectionInGrade(User $user, int $gradeId): bool
    {
        if ($gradeId <= 0) {
            return false;
        }

        $grade = SchoolGrade::find()->where(['id' => $gradeId])->one();
        if ($grade === null) {
            return false;
        }

        return self::canCreateSectionInDepartment($user, (int)$grade->school_department_id);
    }

    /**
     * @return SchoolDepartment[]
     */
    public static function getAccessibleDepartmentsForHierarchy(User $user): array
    {
        $accessType = self::getInfrastructureAccessType($user);
        if ($accessType !== self::INFRASTRUCTURE_ACCESS_DEPARTMENT_LEADER) {
            return [];
        }

        $departmentIds = self::getAccessibleDepartmentIds($user);
        if ($departmentIds === []) {
            return [];
        }

        return SchoolDepartment::find()
            ->with(['grades.sections.leadTeacher'])
            ->where(['id' => $departmentIds])
            ->orderBy(['name' => SORT_ASC])
            ->all();
    }

    public static function infrastructureHierarchyNavLabel(User $user): ?string
    {
        return match (self::getInfrastructureAccessType($user)) {
            self::INFRASTRUCTURE_ACCESS_DEPARTMENT_LEADER => 'Sections & Hierarchy',
            self::INFRASTRUCTURE_ACCESS_CLASS_TEACHER => 'My Section',
            default => null,
        };
    }

    public static function canToggleStudentProfileSection(User $user, SchoolStudent $student): bool
    {
        if ((int)$student->school_section_id <= 0 || !SchoolSection::isEnabled()) {
            return false;
        }

        if ($user->isStudent()) {
            $linked = self::findLinkedStudent($user);

            return $linked !== null
                && (int)$linked->id === (int)$student->id
                && self::getInfrastructureAccessType($user) === self::INFRASTRUCTURE_ACCESS_STUDENT;
        }

        $section = SchoolSection::findOne((int)$student->school_section_id);

        return $section !== null && self::canViewSection($user, $section);
    }

    /**
     * @return array{profile:array,health?:array,section:array}|null
     */
    public static function studentProfileSectionToggleUrls(User $user, SchoolStudent $student): ?array
    {
        if (!self::canToggleStudentProfileSection($user, $student)) {
            return null;
        }

        $sectionId = (int)$student->school_section_id;
        $profileUrl = $user->isStudent()
            ? ['/lms/student-affairs/profile']
            : ['/lms/student-affairs/view', 'id' => (int)$student->id];
        $sectionUrl = ['/lms/hierarchy/section', 'id' => $sectionId];
        if (!$user->isStudent()) {
            $sectionUrl['student_id'] = (int)$student->id;
        }

        $urls = [
            'profile' => $profileUrl,
            'section' => $sectionUrl,
        ];

        if ($user->isStudent() && self::canAccessOwnStudentHealthRecord($user)) {
            $urls['health'] = ['/lms/student-affairs/health-record'];
        }

        if (self::canViewStudentAttendanceLog($user, $student)) {
            $urls['attendance'] = $user->isStudent()
                ? ['/lms/student-affairs/attendance-log']
                : ['/lms/student-affairs/attendance', 'id' => (int)$student->id];
        }

        if (self::canViewStudentMarks($user, $student)) {
            $urls['marks'] = $user->isStudent()
                ? ['/lms/student-affairs/marks-log']
                : ['/lms/student-affairs/marks', 'id' => (int)$student->id];
        }

        return $urls;
    }

    public static function isSubjectsEnabled(): bool
    {
        return SchoolDepartment::isEnabled() && SchoolSubject::isEnabled();
    }

    public static function canAccessLmsSubjects(User $user): bool
    {
        return self::getLmsSubjectsAccessType($user) !== null;
    }

    public static function getLmsSubjectsAccessType(User $user): ?string
    {
        if (!self::isSubjectsEnabled()) {
            return null;
        }

        if ($user->isStudent()) {
            $student = self::findLinkedStudent($user);

            return $student !== null
                && (int)$student->school_section_id > 0
                && SchoolSectionMaterial::isEnabled()
                ? self::SUBJECTS_ACCESS_STUDENT
                : null;
        }

        if (!$user->isSuperHrAdmin() && self::isTeacherEmployee($user)) {
            $employee = self::findLinkedEmployee($user, true);
            if ($employee === null || !$employee->canAccessTeacherLms()) {
                return null;
            }

            if ($employee->isSchoolDepartmentHead() || $employee->isSchoolDepartmentDeputy()) {
                return self::SUBJECTS_ACCESS_DEPARTMENT_LEADER;
            }

            return self::SUBJECTS_ACCESS_TEACHER;
        }

        return null;
    }

    public static function canCreateLmsSubjects(User $user): bool
    {
        return self::getLmsSubjectsAccessType($user) === self::SUBJECTS_ACCESS_DEPARTMENT_LEADER;
    }

    public static function canManageLmsSubjectsInDepartment(User $user, int $departmentId): bool
    {
        return self::canCreateLmsSubjects($user)
            && $departmentId > 0
            && in_array($departmentId, self::getAccessibleDepartmentIds($user), true);
    }

    public static function isTimetableEnabled(): bool
    {
        return SchoolSection::isEnabled()
            && SchoolSectionMaterial::isEnabled()
            && SchoolSectionTimetableRow::isEnabled()
            && SchoolSectionTimetableColumn::isEnabled();
    }

    public static function isTimetableStorageReady(): bool
    {
        return self::isTimetableEnabled() && SchoolSectionTimetableSlot::isEnabled();
    }

    public static function canAccessLmsTimetable(User $user): bool
    {
        if ($user->isStudent()) {
            return self::canAccessOwnStudentSchedule($user);
        }

        return self::getLmsTimetableAccessType($user) !== null;
    }

    public static function getLmsTimetableAccessType(User $user): ?string
    {
        if (!self::isTimetableEnabled()) {
            return null;
        }

        if ($user->isSuperHrAdmin()) {
            return self::TIMETABLE_ACCESS_DEPARTMENT_LEADER;
        }

        if ($user->isStudent()) {
            $student = self::findLinkedStudent($user);

            return $student !== null ? self::TIMETABLE_ACCESS_STUDENT : null;
        }

        if (!$user->isSuperHrAdmin() && self::isTeacherEmployee($user)) {
            $employee = self::findLinkedEmployee($user, true);
            if ($employee === null || !$employee->canAccessTeacherLms()) {
                return null;
            }

            if ($employee->isSchoolDepartmentHead() || $employee->isSchoolDepartmentDeputy()) {
                return self::TIMETABLE_ACCESS_DEPARTMENT_LEADER;
            }

            if ((int)($employee->school_department_id ?? 0) > 0) {
                return self::TIMETABLE_ACCESS_INSTRUCTOR;
            }
        }

        return null;
    }

    public static function canAccessOwnInstructorSchedule(User $user): bool
    {
        return self::getLmsTimetableAccessType($user) === self::TIMETABLE_ACCESS_INSTRUCTOR;
    }

    /**
     * Teachers (including department heads/deputies who teach) may edit lesson notes on their own slots.
     */
    public static function canEditOwnLessonBrief(User $user): bool
    {
        if ($user->isStudent() || $user->isSuperHrAdmin()) {
            return false;
        }

        $employee = self::findLinkedEmployee($user, true);

        return $employee !== null && $employee->canAccessTeacherLms();
    }

    public static function isDepartmentTimetableManager(User $user): bool
    {
        return self::getLmsTimetableAccessType($user) === self::TIMETABLE_ACCESS_DEPARTMENT_LEADER;
    }

    public static function canManageLmsTimetableForSection(User $user, SchoolSection $section): bool
    {
        if (self::getLmsTimetableAccessType($user) !== self::TIMETABLE_ACCESS_DEPARTMENT_LEADER) {
            return false;
        }

        if ($user->isSuperHrAdmin()) {
            return true;
        }

        $departmentId = self::getSectionDepartmentId($section);

        return $departmentId > 0 && in_array($departmentId, self::getAccessibleDepartmentIds($user), true);
    }

    public static function canViewLmsTimetableForSection(User $user, SchoolSection $section): bool
    {
        if ($user->isSuperHrAdmin()) {
            return true;
        }

        if (self::canManageLmsTimetableForSection($user, $section)) {
            return true;
        }

        if (self::getLmsTimetableAccessType($user) === self::TIMETABLE_ACCESS_INSTRUCTOR) {
            return false;
        }

        if (self::getLmsTimetableAccessType($user) === self::TIMETABLE_ACCESS_STUDENT) {
            $student = self::findLinkedStudent($user);
            $sectionId = $student !== null ? self::resolveStudentSectionId($student) : 0;

            return $student !== null && $sectionId > 0 && $sectionId === (int)$section->id;
        }

        return false;
    }

    /**
     * @return SchoolDepartment[]
     */
    public static function getAccessibleDepartmentsForTimetable(User $user): array
    {
        $accessType = self::getLmsTimetableAccessType($user);
        if ($accessType === self::TIMETABLE_ACCESS_DEPARTMENT_LEADER) {
            if ($user->isSuperHrAdmin()) {
                return SchoolDepartment::find()->orderBy(['name' => SORT_ASC])->all();
            }

            $departmentIds = self::getAccessibleDepartmentIds($user);
            if ($departmentIds === []) {
                return [];
            }

            return SchoolDepartment::find()
                ->where(['id' => $departmentIds])
                ->orderBy(['name' => SORT_ASC])
                ->all();
        }

        if ($accessType === self::TIMETABLE_ACCESS_INSTRUCTOR) {
            $employee = self::findLinkedEmployee($user, true);
            $departmentId = (int)($employee?->school_department_id ?? 0);
            if ($departmentId <= 0) {
                return [];
            }

            $department = SchoolDepartment::findOne($departmentId);

            return $department !== null ? [$department] : [];
        }

        if ($accessType === self::TIMETABLE_ACCESS_STUDENT) {
            $student = self::findLinkedStudent($user);
            $section = $student !== null ? self::resolveStudentSection($student) : null;
            $department = $section?->grade?->department;

            return $department !== null ? [$department] : [];
        }

        return [];
    }

    /**
     * @return int[]
     */
    public static function getAccessibleSectionIdsForTimetable(User $user): array
    {
        $accessType = self::getLmsTimetableAccessType($user);
        if ($accessType === self::TIMETABLE_ACCESS_INSTRUCTOR) {
            return [];
        }

        if ($accessType === self::TIMETABLE_ACCESS_STUDENT) {
            $student = self::findLinkedStudent($user);
            if ($student !== null) {
                $sectionId = self::resolveStudentSectionId($student);
                if ($sectionId > 0) {
                    return [$sectionId];
                }
            }
        }

        return [];
    }

    public static function isStudentAttendanceEnabled(): bool
    {
        return SchoolSectionAttendanceSession::isEnabled()
            && SchoolStudentAttendanceRecord::isEnabled()
            && self::isStudentAffairsEnabled();
    }

    public static function canAccessStudentAttendanceGateway(User $user): bool
    {
        return self::canAccessTeacherAttendanceManagement($user);
    }

    /**
     * Matches Odoo can_manage_attendance: super admin, department head/deputy,
     * or homeroom class teacher. Regular course instructors cannot take attendance.
     */
    public static function canAccessTeacherAttendanceManagement(User $user): bool
    {
        if (!self::isStudentAttendanceEnabled() || $user->isStudent() || !self::canAccessTeacherLms($user)) {
            return false;
        }

        if ($user->isSuperHrAdmin()) {
            return true;
        }

        $employee = self::findLinkedEmployee($user, true);
        if ($employee === null || !$employee->canAccessTeacherLms()) {
            return false;
        }

        if ($employee->isSchoolDepartmentHead() || $employee->isSchoolDepartmentDeputy()) {
            return true;
        }

        return self::isClassTeacher($user);
    }

    public static function attendancePortalEyebrow(User $user): string
    {
        if ($user->isSuperHrAdmin()) {
            return 'Super Admin';
        }

        if ($user->isStudent()) {
            return 'Student Portal';
        }

        return self::teacherPortalSubtitle($user);
    }

    /**
     * @return int[]
     */
    public static function getAccessibleDepartmentIdsForAttendance(User $user): array
    {
        if (!self::canAccessStudentAttendanceGateway($user)) {
            return [];
        }

        if ($user->isSuperHrAdmin() && SchoolDepartment::isEnabled()) {
            return array_values(array_unique(array_map(
                'intval',
                SchoolDepartment::find()->select('id')->column()
            )));
        }

        $accessType = self::studentAffairsAccessType($user);
        if ($accessType === self::STUDENT_AFFAIRS_ACCESS_DEPARTMENT_LEADER) {
            return self::getAccessibleDepartmentIds($user);
        }

        $departmentId = self::getEmployeeSchoolDepartmentId($user);

        return $departmentId > 0 ? [$departmentId] : [];
    }

    /**
     * @return int[]
     */
    public static function getAccessibleSectionIdsForAttendanceGateway(User $user): array
    {
        if (!self::canAccessStudentAttendanceGateway($user) || !SchoolSection::isEnabled()) {
            return [];
        }

        if ($user->isSuperHrAdmin()) {
            return array_values(array_unique(array_map(
                'intval',
                SchoolSection::find()->select('id')->column()
            )));
        }

        $employee = self::findLinkedEmployee($user, true);
        if ($employee !== null
            && !$employee->isSchoolDepartmentHead()
            && !$employee->isSchoolDepartmentDeputy()) {
            return self::getAccessibleSectionIds($user);
        }

        $departmentIds = self::getAccessibleDepartmentIdsForAttendance($user);
        if ($departmentIds === [] || !SchoolGrade::isEnabled()) {
            return [];
        }

        return array_values(array_unique(array_map(
            'intval',
            SchoolSection::find()
                ->alias('section')
                ->select('section.id')
                ->innerJoin(['grade' => SchoolGrade::tableName()], 'grade.id = section.school_grade_id')
                ->where(['grade.school_department_id' => $departmentIds])
                ->column()
        )));
    }

    public static function canViewSectionAttendance(User $user, SchoolSection $section): bool
    {
        if (!self::canAccessStudentAttendanceGateway($user)) {
            return false;
        }

        return in_array((int)$section->id, self::getAccessibleSectionIdsForAttendanceGateway($user), true);
    }

    public static function canTakeAttendanceForSection(User $user, SchoolSection $section): bool
    {
        return self::canViewSectionAttendance($user, $section);
    }

    public static function canAccessOwnStudentAttendanceLog(User $user): bool
    {
        if (!self::isStudentAttendanceEnabled() || !self::canAccessOwnStudentProfile($user)) {
            return false;
        }

        return self::findLinkedStudent($user) !== null;
    }

    public static function canViewStudentAttendanceLog(User $user, SchoolStudent $student): bool
    {
        if (!self::isStudentAttendanceEnabled()) {
            return false;
        }

        if ($user->isStudent()) {
            return (int)$student->user_id === (int)$user->id
                && self::canAccessOwnStudentAttendanceLog($user);
        }

        return self::canAccessTeacherAttendanceManagement($user)
            && self::canViewStudent($user, $student);
    }

    public static function isStudentCertificatesEnabled(): bool
    {
        return SchoolStudentCertificateService::isEnabled();
    }

    /**
     * Matches Odoo can_manage_student_certificates: super admin, department head,
     * or homeroom class teacher. Deputies are excluded unless they are also a class teacher.
     */
    public static function canAccessStudentCertificateManagement(User $user): bool
    {
        if (!self::isStudentCertificatesEnabled() || $user->isStudent() || !self::canAccessTeacherLms($user)) {
            return false;
        }

        if ($user->isSuperHrAdmin()) {
            return true;
        }

        $employee = self::findLinkedEmployee($user, true);
        if ($employee === null || !$employee->canAccessTeacherLms()) {
            return false;
        }

        if ($employee->isSchoolDepartmentHead()) {
            return true;
        }

        return self::isClassTeacher($user);
    }

    public static function canAccessOwnStudentCertificate(User $user): bool
    {
        if (!self::isStudentCertificatesEnabled() || !self::canAccessOwnStudentProfile($user)) {
            return false;
        }

        $student = self::findLinkedStudent($user);

        return $student !== null && $student->status === SchoolStudent::STATUS_ACTIVE;
    }

    public static function canAccessStudentCertificates(User $user): bool
    {
        return self::canAccessStudentCertificateManagement($user)
            || self::canAccessOwnStudentCertificate($user);
    }

    /**
     * Managed department IDs for certificate creators (heads only — not deputies).
     *
     * @return int[]
     */
    public static function getAccessibleDepartmentIdsForCertificates(User $user): array
    {
        if (!self::canAccessStudentCertificateManagement($user) || !SchoolDepartment::isEnabled()) {
            return [];
        }

        if ($user->isSuperHrAdmin()) {
            return array_values(array_unique(array_map(
                'intval',
                SchoolDepartment::find()->select('id')->column()
            )));
        }

        $employee = self::findLinkedEmployee($user, true);
        if ($employee === null || !$employee->isSchoolDepartmentHead()) {
            return [];
        }

        $departmentIds = [];
        foreach ($employee->managedSchoolDepartments as $department) {
            $departmentIds[] = (int)$department->id;
        }

        return array_values(array_unique(array_filter($departmentIds)));
    }

    /**
     * @return int[]
     */
    public static function getAccessibleSectionIdsForCertificates(User $user): array
    {
        if (!self::canAccessStudentCertificateManagement($user) || !SchoolSection::isEnabled()) {
            return [];
        }

        if ($user->isSuperHrAdmin()) {
            return array_values(array_unique(array_map(
                'intval',
                SchoolSection::find()->select('id')->column()
            )));
        }

        $ids = [];
        $departmentIds = self::getAccessibleDepartmentIdsForCertificates($user);
        if ($departmentIds !== [] && SchoolGrade::isEnabled()) {
            $ids = array_merge($ids, array_map(
                'intval',
                SchoolSection::find()
                    ->alias('section')
                    ->select('section.id')
                    ->innerJoin(['grade' => SchoolGrade::tableName()], 'grade.id = section.school_grade_id')
                    ->where(['grade.school_department_id' => $departmentIds])
                    ->column()
            ));
        }

        if (self::isClassTeacher($user)) {
            $ids = array_merge($ids, self::getAccessibleSectionIds($user));
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    public static function canAccessMyClasses(User $user): bool
    {
        if ($user->isStudent() || !self::canAccessTeacherLms($user) || !SchoolMyClassesService::isEnabled()) {
            return false;
        }

        if ($user->isSuperHrAdmin()) {
            return true;
        }

        $employee = self::findLinkedEmployee($user, true);
        if ($employee === null || !$employee->canAccessTeacherLms()) {
            return false;
        }

        if ($employee->isSchoolDepartmentHead() || $employee->isSchoolDepartmentDeputy()) {
            return true;
        }

        return self::getAssignedTeachingSectionIds($employee) !== []
            || (int)($employee->school_department_id ?? 0) > 0;
    }

    public static function shouldFilterMyClassesSectionsForUser(User $user): bool
    {
        if (!self::canAccessMyClasses($user) || $user->isSuperHrAdmin()) {
            return false;
        }

        $employee = self::findLinkedEmployee($user, true);
        if ($employee !== null && ($employee->isSchoolDepartmentHead() || $employee->isSchoolDepartmentDeputy())) {
            return false;
        }

        return true;
    }

    /**
     * Sections where the employee is class mentor or assigned as a material instructor.
     *
     * @return int[]
     */
    public static function getAssignedTeachingSectionIds(Employee $employee): array
    {
        if (!SchoolSection::isEnabled()) {
            return [];
        }

        $sectionIds = array_map(
            'intval',
            self::buildClassTeacherSectionQuery($employee)->column()
        );

        if (SchoolSectionMaterial::isEnabled()) {
            $materialQuery = SchoolSectionMaterial::find()
                ->alias('material')
                ->select('material.school_section_id')
                ->innerJoin(
                    ['section' => SchoolSection::tableName()],
                    'section.id = material.school_section_id'
                )
                ->where(['material.instructor_id' => (int)$employee->id]);

            if (Employee::hasColumn('school_department_id') && SchoolGrade::isEnabled()) {
                $employeeDepartmentId = (int)($employee->school_department_id ?? 0);
                if ($employeeDepartmentId > 0) {
                    $materialQuery->innerJoin(
                        ['grade' => SchoolGrade::tableName()],
                        'grade.id = section.school_grade_id'
                    )->andWhere(['grade.school_department_id' => $employeeDepartmentId]);
                }
            }

            $sectionIds = array_merge(
                $sectionIds,
                array_map('intval', $materialQuery->column())
            );
        }

        return array_values(array_unique(array_filter($sectionIds)));
    }

    /**
     * @return int[]
     */
    public static function getAccessibleSectionIdsForMyClasses(User $user): array
    {
        if (!self::canAccessMyClasses($user) || !self::shouldFilterMyClassesSectionsForUser($user)) {
            return [];
        }

        $employee = self::findLinkedEmployee($user, true);
        if ($employee === null) {
            return [];
        }

        return self::getAssignedTeachingSectionIds($employee);
    }

    /**
     * @return SchoolDepartment[]
     */
    public static function getAccessibleDepartmentsForMyClasses(User $user): array
    {
        if (!self::canAccessMyClasses($user)) {
            return [];
        }

        if ($user->isSuperHrAdmin()) {
            return SchoolDepartment::find()->orderBy(['name' => SORT_ASC])->all();
        }

        $employee = self::findLinkedEmployee($user, true);
        if ($employee !== null && ($employee->isSchoolDepartmentHead() || $employee->isSchoolDepartmentDeputy())) {
            $departmentIds = self::getAccessibleDepartmentIds($user);
            if ($departmentIds === []) {
                return [];
            }

            return SchoolDepartment::find()
                ->where(['id' => $departmentIds])
                ->orderBy(['name' => SORT_ASC])
                ->all();
        }

        $departmentId = self::getEmployeeSchoolDepartmentId($user);
        if ($departmentId <= 0) {
            return [];
        }

        $department = SchoolDepartment::findOne($departmentId);

        return $department !== null ? [$department] : [];
    }

    public static function canViewMyClassesDepartment(User $user, int $departmentId): bool
    {
        if ($departmentId <= 0 || !self::canAccessMyClasses($user)) {
            return false;
        }

        foreach (self::getAccessibleDepartmentsForMyClasses($user) as $department) {
            if ((int)$department->id === $departmentId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sidebar tree for My Classes: grades, each expanding to accessible sections.
     *
     * @return array<string, array{url: array, label: string, children?: array<string, array{url: array, label: string}>}>
     */
    public static function getMyClassesNavChildren(User $user): array
    {
        if (!self::canAccessMyClasses($user) || !SchoolSection::isEnabled() || !SchoolGrade::isEnabled()) {
            return [];
        }

        $sections = SchoolSection::find()
            ->with(['grade.department'])
            ->all();

        $grades = [];
        foreach ($sections as $section) {
            if (!self::canAccessMyClassesSection($user, $section)) {
                continue;
            }
            $grade = $section->grade;
            if ($grade === null) {
                continue;
            }
            $gradeId = (int)$grade->id;
            if (!isset($grades[$gradeId])) {
                $grades[$gradeId] = [
                    'id' => $gradeId,
                    'name' => trim((string)$grade->name),
                    'sequence' => (int)($grade->sort_order ?? 0),
                    'department_id' => (int)($grade->school_department_id ?? 0),
                    'department_name' => trim((string)($grade->department->name ?? '')),
                    'sections' => [],
                ];
            }
            $sectionName = trim((string)$section->name);
            $grades[$gradeId]['sections'][] = [
                'id' => (int)$section->id,
                'name' => self::formatSectionNavLabel($sectionName),
            ];
        }

        if ($grades === []) {
            return [];
        }

        $nameCounts = [];
        foreach ($grades as $grade) {
            $key = strtolower($grade['name']);
            $nameCounts[$key] = ($nameCounts[$key] ?? 0) + 1;
        }

        uasort($grades, static function (array $a, array $b): int {
            if ($a['sequence'] !== $b['sequence']) {
                return $a['sequence'] <=> $b['sequence'];
            }
            $cmp = strcasecmp($a['name'], $b['name']);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcasecmp($a['department_name'], $b['department_name']);
        });

        $children = [];
        foreach ($grades as $grade) {
            usort($grade['sections'], static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));
            $label = $grade['name'] !== '' ? $grade['name'] : 'Grade';
            if (($nameCounts[strtolower($grade['name'])] ?? 0) > 1 && $grade['department_name'] !== '') {
                $label .= ' (' . $grade['department_name'] . ')';
            }

            $sectionChildren = [];
            foreach ($grade['sections'] as $section) {
                $sectionChildren['lms_my_classes_section_' . $section['id']] = [
                    'url' => [
                        '/lms/my-classes/index',
                        'department_id' => $grade['department_id'],
                        'grade_id' => $grade['id'],
                        'section_id' => $section['id'],
                    ],
                    'label' => $section['name'],
                ];
            }

            $children['lms_my_classes_grade_' . $grade['id']] = [
                'url' => [
                    '/lms/my-classes/index',
                    'department_id' => $grade['department_id'],
                    'grade_id' => $grade['id'],
                ],
                'label' => $label,
                'children' => $sectionChildren,
            ];
        }

        return $children;
    }

    private static function formatSectionNavLabel(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return 'Section';
        }
        if (preg_match('/^section\s*(.*)$/iu', $name, $matches)) {
            $name = trim((string)$matches[1]);
        }
        if ($name === '') {
            return 'Section';
        }

        return 'Section ' . mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1);
    }

    public static function canAccessMyClassesSection(User $user, SchoolSection $section): bool
    {
        if (!self::canAccessMyClasses($user)) {
            return false;
        }

        $departmentId = self::getSectionDepartmentId($section);
        if ($departmentId <= 0 || !self::canViewMyClassesDepartment($user, $departmentId)) {
            return false;
        }

        if (self::shouldFilterMyClassesSectionsForUser($user)) {
            return in_array((int)$section->id, self::getAccessibleSectionIdsForMyClasses($user), true);
        }

        return true;
    }

    public static function canOpenMyClassesCourseOperations(User $user, SchoolSection $section): bool
    {
        if (!self::canAccessMyClassesSection($user, $section)) {
            return false;
        }

        if (self::canViewSection($user, $section)) {
            return true;
        }

        $employee = self::findLinkedEmployee($user, true);
        if ($employee === null || !SchoolSectionMaterial::isEnabled()) {
            return false;
        }

        return SchoolSectionMaterial::find()
            ->where([
                'school_section_id' => (int)$section->id,
                'instructor_id' => (int)$employee->id,
            ])
            ->exists();
    }

    public static function isStudentMarksEnabled(): bool
    {
        return SchoolSectionMarksService::isEnabled();
    }

    public static function canAccessOwnStudentMarks(User $user): bool
    {
        if (!self::isStudentMarksEnabled() || !self::canAccessOwnStudentProfile($user)) {
            return false;
        }

        $student = self::findLinkedStudent($user);

        return $student !== null && (int)self::resolveStudentSectionId($student) > 0;
    }

    public static function canViewStudentMarks(User $user, SchoolStudent $student): bool
    {
        if (!self::isStudentMarksEnabled()) {
            return false;
        }

        if ($user->isStudent()) {
            return (int)$student->user_id === (int)$user->id
                && self::canAccessOwnStudentMarks($user);
        }

        if (self::isClassTeacher($user)) {
            return (int)$student->school_section_id > 0
                && in_array((int)$student->school_section_id, self::getAccessibleSectionIds($user), true)
                && self::canViewStudent($user, $student);
        }

        $employee = self::findLinkedEmployee($user, true);
        if ($employee !== null && ($employee->isSchoolDepartmentHead() || $employee->isSchoolDepartmentDeputy())) {
            return self::canViewStudent($user, $student);
        }

        return false;
    }

    /**
     * @return SchoolSectionMaterial[]
     */
    public static function getMarksMaterialsForSection(SchoolSection $section): array
    {
        if (!SchoolSectionMaterial::isEnabled()) {
            return [];
        }

        return SchoolSectionMaterial::find()
            ->with(['subject'])
            ->where(['school_section_id' => (int)$section->id])
            ->andWhere(['status' => [
                SchoolSectionMaterial::STATUS_ACTIVE,
                SchoolSectionMaterial::STATUS_PENDING,
            ]])
            ->orderBy(['name' => SORT_ASC])
            ->all();
    }

    /**
     * @return SchoolSectionMaterial[]
     */
    public static function getActiveMaterialsForSection(SchoolSection $section): array
    {
        return self::getMarksMaterialsForSection($section);
    }

    public static function hasViewableMarksInSection(User $user, SchoolSection $section): bool
    {
        return self::getViewableMaterialsForSection($user, $section) !== [];
    }

    /**
     * Materials the user may open on the marks page (view-only or editable).
     *
     * @return SchoolSectionMaterial[]
     */
    public static function getViewableMaterialsForSection(User $user, SchoolSection $section): array
    {
        if (!self::canAccessMyClassesSection($user, $section) || !SchoolSectionMaterial::isEnabled()) {
            return [];
        }

        $materials = self::getActiveMaterialsForSection($section);
        if ($materials === []) {
            return [];
        }

        if ($user->isSuperHrAdmin()) {
            return $materials;
        }

        $employee = self::findLinkedEmployee($user, true);
        if ($employee !== null && ($employee->isSchoolDepartmentHead() || $employee->isSchoolDepartmentDeputy())) {
            return $materials;
        }

        if ($employee !== null && (int)($section->lead_teacher_id ?? 0) === (int)$employee->id) {
            return $materials;
        }

        if ($employee === null) {
            return [];
        }

        return array_values(array_filter(
            $materials,
            static fn(SchoolSectionMaterial $material): bool => (int)($material->instructor_id ?? 0) === (int)$employee->id
        ));
    }

    /**
     * Materials the user may edit on the marks page (assigned instructor only).
     *
     * @return SchoolSectionMaterial[]
     */
    public static function getEditableMaterialsForSection(User $user, SchoolSection $section): array
    {
        if (!self::canAccessMyClassesSection($user, $section) || !SchoolSectionMaterial::isEnabled()) {
            return [];
        }

        $materials = self::getActiveMaterialsForSection($section);
        if ($materials === []) {
            return [];
        }

        if ($user->isSuperHrAdmin()) {
            return $materials;
        }

        $employee = self::findLinkedEmployee($user, true);
        if ($employee === null) {
            return [];
        }

        return array_values(array_filter(
            $materials,
            static fn(SchoolSectionMaterial $material): bool => (int)($material->instructor_id ?? 0) === (int)$employee->id
        ));
    }

    /**
     * @return SchoolSectionMaterial[]
     */
    public static function getMarkableMaterialsForSection(User $user, SchoolSection $section): array
    {
        return self::getEditableMaterialsForSection($user, $section);
    }

    public static function canViewMaterialMarks(User $user, SchoolSection $section, SchoolSectionMaterial $material): bool
    {
        if ((int)$material->school_section_id !== (int)$section->id) {
            return false;
        }

        foreach (self::getViewableMaterialsForSection($user, $section) as $viewableMaterial) {
            if ((int)$viewableMaterial->id === (int)$material->id) {
                return true;
            }
        }

        return false;
    }

    public static function canManageMaterialMarks(User $user, SchoolSection $section, SchoolSectionMaterial $material): bool
    {
        if ((int)$material->school_section_id !== (int)$section->id) {
            return false;
        }

        foreach (self::getEditableMaterialsForSection($user, $section) as $editableMaterial) {
            if ((int)$editableMaterial->id === (int)$material->id) {
                return true;
            }
        }

        return false;
    }

    public static function canManageCourseMaterials(User $user, SchoolSection $section, SchoolSectionMaterial $material): bool
    {
        return self::canManageMaterialMarks($user, $section, $material);
    }
}
