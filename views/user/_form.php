<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\User */
/* @var $roles array */
/* @var $groups array */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'image')->widget(\devgroup\dropzone\DropZone::className(),[
        'url' => \yii\helpers\Url::to(['uploadImage']), // upload url
        'removeUrl' => \yii\helpers\Url::to(['removeImage']), // upload url
        'storedFiles' => isset($storedFiles)?$storedFiles:[], // stores files
        'eventHandlers' => [], // dropzone event handlers
        'sortable' => false, // sortable flag
        'sortableOptions' => [], // sortable options
        'htmlOptions' => ['class' => 'image-uploader'], // container html options
        'options' => [ // dropzone js options
            'dictRemoveFile' => Yii::t('words', 'base.delete'),
            'dictDefaultMessage' =>  Yii::t('words', 'base.addImage'),
            'addRemoveLinks' => true,
            'acceptedFiles' => array('jpg', 'jpeg', 'png'),
            'maxFiles' => 1,
            'thumbnailWidth' => 150,
            'thumbnailHeight' => 220,
        ],
    ]) ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'username')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'password')->passwordInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'repeatPassword')->passwordInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'roleID')->dropDownList($roles, ['prompt' => Yii::t('words', 'user.chooseRole')]) ?>

    <?= $form->field($model, 'groups')->checkboxList($groups, [
        'class' => 'row',
        'value' => $model->groups ? array_keys($model->groups) : null,
        'item' => function ($index, $label, $name, $checked, $value) {
            $id = $name . '-' . $index;
            $ch = $checked ? 'checked="true"' : '';
            return "<div style='display: inline-block;padding:0 15px;'>
                                        <input type='checkbox' id='{$id}' name='{$name}' value='{$value}' {$ch}>
                                        <label for='{$id}'>{$label}</label>
                                </div>";
        },
    ]) ?>

    <?= $form->field($model, 'email')->textInput() ?>

    <?= $form->field($model, 'phone')->textInput() ?>

    <?= $form->field($model, 'status')->dropDownList(app\models\User::getStatusLabels(), ['prompt' => Yii::t('words', 'base.chooseStatus')]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
