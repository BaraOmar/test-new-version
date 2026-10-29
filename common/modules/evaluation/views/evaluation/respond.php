<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\modules\evaluation\models\Evaluation $evaluation */
/** @var common\modules\evaluation\models\EvaluationQuestion[] $questions */
/** @var bool $hasSubmitted */
/** @var bool $canRespond */
/** @var string|null $denyReason */
/** @var string|null $backUrl */

$canRespond = $canRespond ?? true;
$denyReason = $denyReason ?? '';

$this->title = $evaluation->title;
echo $this->render('_builder-styles');

$embedYoutube = static function (string $url): ?string {
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }

    return null;
};

?>

<div class="cp-respond-root min-h-full">
    <header class="cp-respond-header flex items-center justify-between px-6 py-2 sticky top-0 z-50">
        <div class="flex items-center gap-3">
            <div class="size-10 flex items-center justify-center rounded-lg bg-[#eb652c] text-white">
                <span class="material-symbols-outlined text-2xl">assignment</span>
            </div>
            <h2 class="text-white text-xl font-normal">CulturePulse</h2>
        </div>
        <?php if (!empty($backUrl)): ?>
            <a href="<?= Html::encode($backUrl) ?>" class="text-white/90 text-sm hover:text-white no-underline">Back to tasks</a>
        <?php endif; ?>
    </header>

    <main class="cp-respond-main">
        <?php if ($hasSubmitted): ?>
            <div class="google-card p-8 max-w-md mx-auto text-center">
                <span class="material-symbols-outlined text-5xl text-green-500 mb-4 block">check_circle</span>
                <h1 class="text-xl font-medium mb-2">You have already submitted this evaluation.</h1>
                <p class="text-sm text-gray-600 mb-6">
                    Thank you for completing "<?= Html::encode($evaluation->title) ?>".
                </p>
                <?php if (!empty($backUrl)): ?>
                    <a href="<?= Html::encode($backUrl) ?>"
                       class="inline-flex items-center justify-center px-6 py-2.5 rounded-lg bg-[#eb652c] hover:bg-[#d85a24] text-white text-sm font-medium transition-all no-underline">
                        Back to my tasks
                    </a>
                <?php endif; ?>
            </div>
        <?php elseif (empty($canRespond)): ?>
            <div class="cp-alert cp-alert-warning"><?= Html::encode($denyReason ?? 'This evaluation is not available.') ?></div>
        <?php elseif (!$questions): ?>
            <div class="cp-alert cp-alert-warning">This evaluation has no questions configured.</div>
        <?php else: ?>
            <div class="google-card header-top-border p-6 pt-5 mb-3">
                <h1 class="text-3xl font-normal mb-2 text-[#202124]"><?= Html::encode($evaluation->title) ?></h1>
                <?php if ($evaluation->getDescriptionPlain() !== ''): ?>
                    <p class="text-sm text-[#202124] mb-0 whitespace-pre-line"><?= Html::encode($evaluation->getDescriptionPlain()) ?></p>
                <?php endif; ?>
            </div>

            <?= Html::beginForm(['respond', 'id' => $evaluation->id], 'post') ?>
            <?php
            $qNum = 0;
            foreach ($questions as $question):
                $type = $question->question_type;
                if ($type === 'section_break'):
                    ?>
                    <div class="cp-section-break">
                        <?php if ($question->question_text && $question->question_text !== 'Section'): ?>
                            <strong class="text-slate-500 text-sm"><?= Html::encode($question->question_text) ?></strong>
                        <?php endif; ?>
                        <hr>
                    </div>
                    <?php
                    continue;
                endif;
                if ($type === 'section_title'):
                    $sectionOpts = $question->options;
                    $desc = isset($sectionOpts[0]) ? $sectionOpts[0]->getLabel() : '';
                    ?>
                    <div class="google-card cp-q-card">
                        <h2 class="text-lg font-medium mb-2"><?= Html::encode($question->question_text) ?></h2>
                        <?php if ($desc !== ''): ?>
                            <p class="text-sm text-slate-600 mb-0"><?= Html::encode($desc) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php
                    continue;
                endif;
                if ($type === 'image' && $question->question_text):
                    ?>
                    <div class="google-card cp-q-card">
                        <img src="<?= Html::encode($question->question_text) ?>" alt="" class="cp-media-img">
                    </div>
                    <?php
                    continue;
                endif;
                if ($type === 'video' && $question->question_text):
                    $embed = $embedYoutube($question->question_text);
                    ?>
                    <div class="google-card cp-q-card">
                        <?php if ($embed): ?>
                            <div class="cp-video-wrap"><iframe src="<?= Html::encode($embed) ?>" allowfullscreen></iframe></div>
                        <?php else: ?>
                            <a href="<?= Html::encode($question->question_text) ?>" target="_blank" rel="noopener" class="text-[#642754]"><?= Html::encode($question->question_text) ?></a>
                        <?php endif; ?>
                    </div>
                    <?php
                    continue;
                endif;

                $qNum++;
                ?>
                <div class="google-card cp-q-card">
                    <div class="cp-q-number flex items-start gap-1">
                        <span><?= $qNum ?>. <?= Html::encode($question->question_text) ?></span>
                        <?php if ((int)$question->is_required): ?>
                            <span class="text-red-500">*</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($type === 'rating'): ?>
                        <?php
                        $min = $question->scale_min ? (int)$question->scale_min : 1;
                        $max = $question->scale_max ? (int)$question->scale_max : 5;
                        ?>
                        <div class="cp-star-row">
                            <?php for ($i = $min; $i <= $max; $i++): ?>
                                <label>
                                    <input type="radio" name="q_<?= (int)$question->id ?>" value="<?= $i ?>" <?= (int)$question->is_required && $i === $min ? 'required' : '' ?>>
                                    <span class="material-symbols-outlined">star</span>
                                </label>
                            <?php endfor; ?>
                            <span class="ml-4 text-xs text-gray-500 uppercase tracking-wide">Scale <?= $min ?>-<?= $max ?></span>
                        </div>

                    <?php elseif ($type === 'linear_scale'): ?>
                        <?php
                        $min = $question->scale_min ? (int)$question->scale_min : 1;
                        $max = $question->scale_max ? (int)$question->scale_max : 5;
                        $opts = $question->options;
                        $labelMin = isset($opts[0]) ? $opts[0]->getLabel() : '';
                        $labelMax = isset($opts[1]) ? $opts[1]->getLabel() : '';
                        ?>
                        <div class="cp-linear-labels">
                            <span><?= Html::encode($labelMin) ?></span>
                            <span><?= Html::encode($labelMax) ?></span>
                        </div>
                        <div class="cp-linear-row">
                            <?php for ($i = $min; $i <= $max; $i++): ?>
                                <label>
                                    <span><?= $i ?></span>
                                    <input type="radio" name="q_<?= (int)$question->id ?>" value="<?= $i ?>" <?= (int)$question->is_required ? 'required' : '' ?>>
                                </label>
                            <?php endfor; ?>
                        </div>

                    <?php elseif (in_array($type, ['multiple_choice', 'checkbox'], true)): ?>
                        <div class="space-y-4">
                            <?php foreach ($question->options as $option): ?>
                                <label class="cp-option">
                                    <input type="<?= $type === 'checkbox' ? 'checkbox' : 'radio' ?>"
                                           name="q_<?= (int)$question->id ?><?= $type === 'checkbox' ? '[]' : '' ?>"
                                           value="<?= Html::encode($option->getLabel()) ?>">
                                    <span><?= Html::encode($option->getLabel()) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($type === 'dropdown'): ?>
                        <select name="q_<?= (int)$question->id ?>" class="cp-select" <?= (int)$question->is_required ? 'required' : '' ?>>
                            <option value="">Choose</option>
                            <?php foreach ($question->options as $option): ?>
                                <option value="<?= Html::encode($option->getLabel()) ?>"><?= Html::encode($option->getLabel()) ?></option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif ($type === 'long_text'): ?>
                        <textarea name="q_<?= (int)$question->id ?>" class="cp-textarea" placeholder="Your answer" rows="3" <?= (int)$question->is_required ? 'required' : '' ?>></textarea>

                    <?php else: ?>
                        <input type="text" name="q_<?= (int)$question->id ?>" class="cp-text-input" placeholder="Your answer" <?= (int)$question->is_required ? 'required' : '' ?>>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div class="cp-actions">
                <button type="reset" class="cp-btn-clear">Clear form</button>
                <button type="submit" class="cp-btn-submit">
                    Submit Response
                </button>
            </div>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </main>
</div>
