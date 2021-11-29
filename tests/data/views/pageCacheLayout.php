<?php declare(strict_types=1);
/** @var \yii\web\View $this */
/* @var string $static */
$this->beginPage();
$this->head();
$this->beginBody();
?>
{
    "static": "<?php print $static; ?>",
    "dynamic": "<?php print $this->renderDynamic('return Yii::$app->params[\'dynamic\'];'); ?>"
}
<?php
$this->endBody();
$this->endPage();
?>
