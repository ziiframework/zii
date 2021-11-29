<?php declare(strict_types=1);

if (method_exists($this, 'beginPage')) { ?>
<?php $this->beginPage(); ?>
<?php } ?>
Exception View
<?php if (method_exists($this, 'endBody')) { ?>
<?php $this->endBody(); ?>
<?php } ?>
<?php if (method_exists($this, 'endPage')) { ?>
<?php $this->endPage(); ?>
<?php }
