<?php declare(strict_types=1);

/* @var $exception Exception */

?>
Code: <?php print Yii::$app->response->statusCode; ?>

Message: <?php print $exception->getMessage(); ?>

Exception: <?php print get_class($exception); ?>
