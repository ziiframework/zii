<?php declare(strict_types=1);

/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

?>
Name: <?php print $name; ?>

Code: <?php print Yii::$app->response->statusCode; ?>

Message: <?php print $message; ?>

Exception: <?php print get_class($exception); ?>
