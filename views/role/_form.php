<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Role */
/* @var $roles array */
/* @var $actions array */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="role-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'parent')->dropDownList($roles, ['value' =>  $model->parent ?: 'guest', 'prompt' => Yii::t('words', 'base.noParent')]) ?>

    <?php foreach($actions as $key => $controller):?>
        <div class="column">
            <h4><?php echo $controller['alias']?></h4>
            <?php foreach($controller['actions'] as $action):?>
                <?php echo Html::checkbox('actions[]', $action['selected'], ['value' => $action['name'], 'id' => $action['name']])?>
                <label for="<?php echo $action['name']?>"><?php echo $action['alias']?></label>
            <?php endforeach;?>
        </div>
    <?php endforeach;?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
