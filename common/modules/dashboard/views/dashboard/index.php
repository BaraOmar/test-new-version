<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var array $snapshot */
/** @var array $navigationCards */

$this->title = 'HR Management Hub';

$metricCards = [
    [
        'label' => 'Total Employees',
        'value' => $snapshot['totalEmployees'],
        'suffix' => '',
        'helper' => 'Active employee records',
        'class' => 'text-primary',
    ],
    [
        'label' => 'Survey Response Rate',
        'value' => $snapshot['surveyRate'],
        'suffix' => '%',
        'helper' => 'Target: ' . $snapshot['responseRateTarget'] . '%',
        'class' => $snapshot['surveyRate'] >= $snapshot['responseRateTarget'] ? 'text-success' : 'text-warning',
    ],
    [
        'label' => 'Evaluation Response Rate',
        'value' => $snapshot['evaluationRate'],
        'suffix' => '%',
        'helper' => 'Target: ' . $snapshot['responseRateTarget'] . '%',
        'class' => $snapshot['evaluationRate'] >= $snapshot['responseRateTarget'] ? 'text-success' : 'text-warning',
    ],
];
?>

<div class="dashboard-index py-4">
    <div class="mb-4">
        <h1 class="mb-1"><?= Html::encode($this->title) ?></h1>
        <p class="text-muted mb-0">Operational overview for HR administration and future analytics.</p>
    </div>

    <div class="row g-4 mb-4">
        <?php foreach ($metricCards as $metric): ?>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="text-muted small text-uppercase fw-semibold"><?= Html::encode($metric['label']) ?></span>
                        <div class="display-6 fw-bold <?= Html::encode($metric['class']) ?>">
                            <?= Html::encode($metric['value']) ?><?= Html::encode($metric['suffix']) ?>
                        </div>
                        <p class="text-muted small mb-0"><?= Html::encode($metric['helper']) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-3">
                <div>
                    <h2 class="h5 mb-1">Analytics Readiness</h2>
                    <p class="text-muted mb-0">Dashboard metrics are prepared in a controller snapshot for later BI, charts, trends, and export integrations.</p>
                </div>
                <div class="text-muted small">
                    Surveys: <?= (int)$snapshot['totalSurveys'] ?> · Survey responses: <?= (int)$snapshot['surveyResponses'] ?><br>
                    Evaluations: <?= (int)$snapshot['totalEvaluations'] ?> · Evaluation responses: <?= (int)$snapshot['evaluationResponses'] ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach ($navigationCards as $card): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="mb-3">
                            <span class="badge bg-light text-dark border"><?= Html::encode($card['icon']) ?></span>
                        </div>
                        <h2 class="h5"><?= Html::encode($card['title']) ?></h2>
                        <p class="text-muted flex-grow-1"><?= Html::encode($card['description']) ?></p>
                        <?= Html::a(Html::encode($card['button']), $card['route'], ['class' => 'btn btn-primary mt-auto']) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
