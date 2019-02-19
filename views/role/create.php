<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Role */
/* @var $roles array */
/* @var $actions array */

$this->title = 'Create Role';
$this->params['breadcrumbs'][] = ['label' => 'Roles', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="role-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'roles' => $roles,
        'actions' => $actions,
    ]) ?>

</div>
