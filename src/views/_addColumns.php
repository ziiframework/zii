<?php foreach ($fields as $field) { ?>
        $this->addColumn('<?= $table;
        ?>', '<?= $field['property'];
        ?>', $this-><?= $field['decorators'];
        ?>);
<?php }

echo $this->render('_addForeignKeys', [
    'table' => $table,
    'foreignKeys' => $foreignKeys,
]);
