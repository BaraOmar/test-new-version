<?php

namespace common\modules\transport\services;

use common\modules\employee\models\Employee;
use common\modules\employee\models\SchoolStudent;
use common\modules\transport\models\TransportArea;
use common\modules\transport\models\TransportBus;
use common\modules\transport\models\TransportBusAssignment;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Transaction;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

class TransportationService
{
    public static function isEnabled(): bool
    {
        return TransportArea::isEnabled() && TransportBus::isEnabled() && TransportBusAssignment::isEnabled();
    }

    public function buildAreasDataProvider(string $search = ''): ActiveDataProvider
    {
        $query = TransportArea::find()->orderBy(['name' => SORT_ASC]);

        $search = trim($search);
        if ($search !== '') {
            $query->andWhere([
                'or',
                ['like', 'name', $search],
                ['like', 'description', $search],
            ]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => false,
        ]);
    }

    public function buildBusesDataProvider(string $search = ''): ActiveDataProvider
    {
        $query = TransportBus::find()
            ->with(['area', 'driver', 'supervisor'])
            ->orderBy(['plate_number' => SORT_ASC]);

        $search = trim($search);
        if ($search !== '') {
            $query->andWhere([
                'or',
                ['like', 'plate_number', $search],
                ['like', 'notes', $search],
            ]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => false,
        ]);
    }

    public function buildAssignmentsDataProvider(string $search = ''): ActiveDataProvider
    {
        $query = TransportBus::find()
            ->alias('bus')
            ->with(['area', 'driver', 'supervisor', 'assignments.student'])
            ->orderBy(['bus.plate_number' => SORT_ASC]);

        $search = trim($search);
        if ($search !== '') {
            $query->joinWith(['area area', 'driver driver', 'supervisor supervisor'], false);
            $query->andWhere([
                'or',
                ['like', 'bus.plate_number', $search],
                ['like', 'area.name', $search],
                ['like', 'driver.full_name', $search],
                ['like', 'supervisor.full_name', $search],
            ]);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => false,
        ]);
    }

    public function findArea(int $id): TransportArea
    {
        $area = TransportArea::findOne($id);
        if (!$area instanceof TransportArea) {
            throw new NotFoundHttpException('Transport area not found.');
        }

        return $area;
    }

    public function findBus(int $id): TransportBus
    {
        $bus = TransportBus::find()
            ->with(['area', 'driver', 'supervisor', 'assignments.student'])
            ->where(['id' => $id])
            ->one();

        if (!$bus instanceof TransportBus) {
            throw new NotFoundHttpException('Bus not found.');
        }

        return $bus;
    }

    /**
     * @return array{success:bool,message:string,area:TransportArea|null}
     */
    public function saveArea(?int $id = null): array
    {
        if (!self::isEnabled()) {
            return ['success' => false, 'message' => 'Transportation module is not available. Run migrations first.', 'area' => null];
        }

        $area = $id ? $this->findArea($id) : new TransportArea();
        $request = Yii::$app->request;

        $area->name = trim((string)$request->post('name', ''));
        $area->description = trim((string)$request->post('description', '')) ?: null;
        $priceRaw = trim((string)$request->post('price', ''));
        $area->price = $priceRaw === '' ? null : max(0, (float)$priceRaw);
        $area->is_active = (string)$request->post('is_active', '1') === '1';

        if (!$area->save()) {
            return [
                'success' => false,
                'message' => implode(' ', $area->getFirstErrors()) ?: 'Could not save area.',
                'area' => $area,
            ];
        }

        return [
            'success' => true,
            'message' => $id ? 'Area updated successfully.' : 'Area created successfully.',
            'area' => $area,
        ];
    }

    /**
     * @return array{success:bool,message:string}
     */
    public function deleteArea(int $id): array
    {
        $area = $this->findArea($id);
        $busCount = (int)TransportBus::find()->where(['transport_area_id' => $area->id])->count();

        if ($busCount > 0) {
            return [
                'success' => false,
                'message' => 'Cannot delete an area that is assigned to buses. Reassign buses first.',
            ];
        }

        if (!$area->delete()) {
            return ['success' => false, 'message' => 'Could not delete area.'];
        }

        return ['success' => true, 'message' => 'Area deleted successfully.'];
    }

    /**
     * @return array{success:bool,message:string,bus:TransportBus|null}
     */
    public function saveBus(?int $id = null): array
    {
        if (!self::isEnabled()) {
            return ['success' => false, 'message' => 'Transportation module is not available. Run migrations first.', 'bus' => null];
        }

        $bus = $id ? $this->findBus($id) : new TransportBus();
        $request = Yii::$app->request;

        $bus->plate_number = trim((string)$request->post('plate_number', ''));
        $bus->capacity = max(1, (int)$request->post('capacity', 30));
        $bus->notes = trim((string)$request->post('notes', '')) ?: null;
        $bus->is_active = (string)$request->post('is_active', '1') === '1';

        if (!$bus->save()) {
            return [
                'success' => false,
                'message' => implode(' ', $bus->getFirstErrors()) ?: 'Could not save bus.',
                'bus' => $bus,
            ];
        }

        return [
            'success' => true,
            'message' => $id ? 'Bus updated successfully.' : 'Bus created successfully.',
            'bus' => $bus,
        ];
    }

    /**
     * @return array{success:bool,message:string}
     */
    public function deleteBus(int $id): array
    {
        $bus = $this->findBus($id);

        /** @var Transaction $transaction */
        $transaction = Yii::$app->db->beginTransaction();
        try {
            TransportBusAssignment::deleteAll(['transport_bus_id' => $bus->id]);
            if (!$bus->delete()) {
                $transaction->rollBack();

                return ['success' => false, 'message' => 'Could not delete bus.'];
            }
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), __METHOD__);

            return ['success' => false, 'message' => 'Could not delete bus.'];
        }

        return ['success' => true, 'message' => 'Bus deleted successfully.'];
    }

    /**
     * @param int[] $studentIds
     * @return array{success:bool,message:string,bus:TransportBus|null}
     */
    public function saveBusAssignment(int $busId, array $studentIds): array
    {
        if (!self::isEnabled()) {
            return ['success' => false, 'message' => 'Transportation module is not available. Run migrations first.', 'bus' => null];
        }

        $bus = $this->findBus($busId);
        $request = Yii::$app->request;

        $areaId = (int)$request->post('transport_area_id', 0);
        $driverId = (int)$request->post('driver_employee_id', 0);
        $supervisorId = (int)$request->post('supervisor_employee_id', 0);

        $bus->transport_area_id = $areaId > 0 ? $areaId : null;
        $bus->driver_employee_id = $driverId > 0 ? $driverId : null;
        $bus->supervisor_employee_id = $supervisorId > 0 ? $supervisorId : null;

        if ($driverId > 0 && $supervisorId > 0 && $driverId === $supervisorId) {
            return ['success' => false, 'message' => 'Driver and supervisor must be different employees.', 'bus' => $bus];
        }

        $studentIds = array_values(array_unique(array_filter(array_map('intval', $studentIds), static fn(int $id): bool => $id > 0)));

        if (count($studentIds) > (int)$bus->capacity) {
            return [
                'success' => false,
                'message' => 'Selected students exceed bus capacity (' . (int)$bus->capacity . ').',
                'bus' => $bus,
            ];
        }

        if ($studentIds !== []) {
            $conflicts = TransportBusAssignment::find()
                ->alias('assignment')
                ->innerJoinWith(['student student'])
                ->where(['assignment.school_student_id' => $studentIds])
                ->andWhere(['<>', 'assignment.transport_bus_id', $bus->id])
                ->all();

            if ($conflicts !== []) {
                $names = [];
                foreach ($conflicts as $conflict) {
                    if ($conflict instanceof TransportBusAssignment && $conflict->student) {
                        $names[] = $conflict->student->getDisplayFullName();
                    }
                }

                return [
                    'success' => false,
                    'message' => 'Some students are already assigned to another bus: ' . implode(', ', $names) . '.',
                    'bus' => $bus,
                ];
            }
        }

        /** @var Transaction $transaction */
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$bus->save()) {
                $transaction->rollBack();

                return [
                    'success' => false,
                    'message' => implode(' ', $bus->getFirstErrors()) ?: 'Could not save bus assignment.',
                    'bus' => $bus,
                ];
            }

            TransportBusAssignment::deleteAll(['transport_bus_id' => $bus->id]);

            foreach ($studentIds as $studentId) {
                $assignment = new TransportBusAssignment();
                $assignment->transport_bus_id = $bus->id;
                $assignment->school_student_id = $studentId;
                if (!$assignment->save()) {
                    $transaction->rollBack();

                    return [
                        'success' => false,
                        'message' => implode(' ', $assignment->getFirstErrors()) ?: 'Could not assign students.',
                        'bus' => $bus,
                    ];
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error($e->getMessage(), __METHOD__);

            return ['success' => false, 'message' => 'Could not save bus assignment.', 'bus' => $bus];
        }

        return [
            'success' => true,
            'message' => 'Bus assignment saved successfully.',
            'bus' => $this->findBus($bus->id),
        ];
    }

    /**
     * @return array<int, array{id:int,name:string,price:?float}>
     */
    public function areaOptionsWithPrices(bool $activeOnly = true): array
    {
        $query = TransportArea::find()->orderBy(['name' => SORT_ASC]);
        if ($activeOnly) {
            $query->andWhere(['is_active' => true]);
        }

        $options = [];
        foreach ($query->all() as $area) {
            if (!$area instanceof TransportArea) {
                continue;
            }

            $options[] = [
                'id' => (int)$area->id,
                'name' => (string)$area->name,
                'price' => $area->price !== null ? (float)$area->price : null,
            ];
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    public function areaOptions(bool $activeOnly = false): array
    {
        $query = TransportArea::find()->orderBy(['name' => SORT_ASC]);
        if ($activeOnly) {
            $query->andWhere(['is_active' => true]);
        }

        return ArrayHelper::map($query->all(), 'id', 'name');
    }

    /**
     * @return array<int, string>
     */
    public function busOptions(bool $activeOnly = false): array
    {
        $query = TransportBus::find()->orderBy(['plate_number' => SORT_ASC]);
        if ($activeOnly) {
            $query->andWhere(['is_active' => true]);
        }

        return ArrayHelper::map($query->all(), 'id', 'plate_number');
    }

    /**
     * @return array<int, array{id:int,label:string,search:string}>
     */
    public function employeeOptions(): array
    {
        $query = Employee::find()->orderBy(['full_name' => SORT_ASC]);
        if (Employee::hasColumn('status')) {
            $query->andWhere(['status' => 'Active']);
        }

        $options = [];
        foreach ($query->all() as $employee) {
            if (!$employee instanceof Employee) {
                continue;
            }

            $name = trim((string)$employee->full_name);
            if ($name === '') {
                continue;
            }

            $jobTitle = Employee::hasColumn('job_title') ? trim((string)($employee->job_title ?? '')) : '';
            $label = $name;
            if ($jobTitle !== '') {
                $label .= ' (' . $jobTitle . ')';
            }

            $options[] = [
                'id' => (int)$employee->id,
                'label' => $label,
                'search' => strtolower(trim($name . ' ' . $jobTitle)),
            ];
        }

        return $options;
    }

    /**
     * @return array<int, array{id:int,label:string,search:string}>
     */
    public function studentOptions(): array
    {
        if (!SchoolStudent::isEnabled()) {
            return [];
        }

        $query = SchoolStudent::find()->orderBy(['full_name' => SORT_ASC]);
        $options = [];

        foreach ($query->all() as $student) {
            if (!$student instanceof SchoolStudent) {
                continue;
            }

            $name = $student->getDisplayFullName();
            if ($name === '') {
                continue;
            }

            $code = trim((string)($student->student_code ?? ''));
            $label = $name;
            if ($code !== '') {
                $label .= ' — ' . $code;
            }

            $options[] = [
                'id' => (int)$student->id,
                'label' => $label,
                'search' => strtolower(trim($name . ' ' . $code)),
            ];
        }

        return $options;
    }

    /**
     * @return int[]
     */
    public function assignedStudentIds(int $busId): array
    {
        return array_map(
            'intval',
            TransportBusAssignment::find()
                ->select(['school_student_id'])
                ->where(['transport_bus_id' => $busId])
                ->column()
        );
    }
}
