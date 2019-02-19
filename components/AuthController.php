<?php

namespace app\components;

use app\models\User;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;

/**
 * Class AuthController
 * @package app\components
 *
 * @property User $user
 */
class AuthController extends MainController
{
    public $bodyClass;

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        parent::beforeAction($action);

        /* @var $role \yii\rbac\Role */
        $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->id);
        foreach ($roles as $role)
            if ($role->name == 'superAdmin')
                return true;

        // Check same permissions
        $samePermissions = $this->getSamePermissions();
        $parentPermission = null;
        foreach ($samePermissions as $permission => $samePermission) {
            if (is_string($samePermission) and $samePermission == $action->id)
                $parentPermission = $permission;
            elseif (is_array($samePermission)) {
                foreach ($samePermission as $item) {
                    if ($item == lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $action->id))))) {
                        $parentPermission = $permission;
                        break;
                    }
                }
            }

            if ($parentPermission)
                break;
        }

        if ($parentPermission and Yii::$app->user->can($this->id . $parentPermission))
            return true;

        // Check permission
        $limitedActions = ['view'];
        $permissionName = $this->id . ucfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $action->id))));

        if (isset($action->actionMethod))
            $actionMethod = lcfirst(str_replace('action', '', $action->actionMethod));
        else
            $actionMethod = lcfirst(str_replace('action', '', $action->id));
        $systemActions = $this->getSystemActions();

        if (in_array($actionMethod, $systemActions) || Yii::$app->user->can($permissionName))
            return true;
        else {
            if (in_array($action->id, $limitedActions)) {
                if (method_exists($this, 'findModel')) {
                    $model = $this->findModel(Yii::$app->request->get('id'));
                    if (isset($model->access))
                        foreach ($roles as $role)
                            if ($role->name == $model->access)
                                return true;
                }
            }

            throw new ForbiddenHttpException('شما مجوز انجام این عملیات را ندارید.');
        }
    }
}