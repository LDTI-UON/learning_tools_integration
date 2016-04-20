
<h1><?= lang('add_institution') ?></h1>

<?=form_open($action, '', null)?>
    <p><?= validation_errors(); ?></p>
    <p><?=lang('add_institution_name')?>&nbsp;&nbsp;
    <?= form_input(array(
              'name'        => 'add_name',
              'id'          => 'add_name',
              'value'       => '',
              'maxlength'   => '100',
              'size'        => '20',
              'style' => 'width: 300px')) ?></p>

    <p><?=form_submit('add_institution', lang('add_institution'), 'class="submit"')?></p>

<?=form_close()?>
