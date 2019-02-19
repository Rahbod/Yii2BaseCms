<?php

namespace app\controllers;

use app\components\MainController;
use app\components\Setting;
use app\models\RoleSearch;
use Yii;
use app\models\Role;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\StringHelper;
use yii\web\Response;
use yii\widgets\ActiveForm;
use app\components\AuthController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * RoleController implements the CRUD actions for Role model.
 */
class RoleController extends MainController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Role models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new RoleSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * Creates a new Role model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Role();

        if (Yii::$app->request->isAjax and !Yii::$app->request->isPjax) {
            $model->load(Yii::$app->request->post());
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        if (Yii::$app->request->post()) {
            $auth = Yii::$app->authManager;
            $data = Yii::$app->request->post('Role');
            $actions = Yii::$app->request->post('actions');
            $role = $auth->createRole(substr(uniqid(),0,64));
            $role->description = $data['description'];
            if ($auth->add($role)) {
                if ($actions) {
                    foreach ($actions as $action) {
                        if (strpos($action, '-') !== false) {
                            $permission = explode('-', $action);
                            $permissionName = $permission[0] . ucfirst($permission[1]);
                            $permission = $auth->getPermission($permissionName);
                            if (is_null($permission)) {
                                $permission = $auth->createPermission($permissionName);
                                $auth->add($permission);
                            }
                            $auth->addChild($role, $permission);
                        }
                    }
                }

                if (!empty($data['parent'])) {
                    $parent = $auth->getRole($data['parent']);
                    $auth->addChild($parent, $role);
                }

                Yii::$app->session->setFlash('alert', ['type' => 'success', 'message' => Yii::t('words', 'base.successMsg')]);
            } else
                Yii::$app->session->setFlash('alert', ['type' => 'danger', 'message' => Yii::t('words', 'base.dangerMsg')]);
        }

        $actions = $this->getAllActions();
        $actions = $this->prepareForView($actions);

        $auth = Yii::$app->authManager;
        $roles = $auth->getRoles();
        unset($roles['superAdmin']);
        $roles = ArrayHelper::map($roles, 'name', function($model){
            return ($model->description == 'Guest Role' ? Yii::t('words', 'base.guestRole') : $model->description);
        });

        return $this->render('create', [
            'model' => $model,
            'actions' => $actions,
            'roles' => $roles,
        ]);
    }

    /**
     * Updates an existing Role model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if (Yii::$app->request->isAjax and !Yii::$app->request->isPjax) {
            $model->load(Yii::$app->request->post());
            Yii::$app->response->format = Response::FORMAT_JSON;
            return ActiveForm::validate($model);
        }

        $auth = Yii::$app->authManager;
        $role = $auth->getRole($model->name);
        if (Yii::$app->request->post()) {
            $data = Yii::$app->request->post('Role');
            if (isset($data['name'])) unset($data['name']);

            $role->description = $data['description'];
            /* @var Role[] $children */
            $children = $model->getChildren()->where(['type' => 1])->all();
            if ($auth->update($model->name, $role)) {
                // Remove all permissions
                $auth->removeChildren($role);
                if (Yii::$app->request->post('actions')) {
                    // Add new permissions
                    $actions = Yii::$app->request->post('actions');
                    foreach ($actions as $action) {
                        if (strpos($action, '-') !== false) {
                            $permission = explode('-', $action);
                            $permissionName = $permission[0] . ucfirst($permission[1]);
                            $permission = $auth->getPermission($permissionName);
                            if (is_null($permission)) {
                                $permission = $auth->createPermission($permissionName);
                                $auth->add($permission);
                            }
                            $auth->addChild($role, $permission);
                        }
                    }
                }

                if (!empty($data['parent'])) {
                    $parent = $auth->getRole($data['parent']);
                    $auth->addChild($parent, $role);
                }

                if ($children) {
                    $parent = $role;
                    foreach ($children as $child) {
                        $childRole = $auth->getRole($child->name);
                        $auth->addChild($parent, $childRole);
                    }
                }

                if (Yii::$app->request->post('submitType') == 'submitAndExit')
                    Yii::$app->session->setFlash('public-alert', ['type' => 'success', 'message' => Yii::t('words', 'base.successMsg')]);
                else
                    Yii::$app->session->setFlash('alert', ['type' => 'success', 'message' => Yii::t('words', 'base.successMsg')]);
            } else
                Yii::$app->session->setFlash('alert', ['type' => 'danger', 'message' => Yii::t('words', 'base.dangerMsg')]);
        }

        $actions = $this->getAllActions();
        $actions = $this->prepareForView($actions, array_keys($auth->getPermissionsByRole($role->name)), null);

        $auth = Yii::$app->authManager;
        $roles = $auth->getRoles();
        unset($roles[$role->name]);
        unset($roles['superAdmin']);
        $roles = ArrayHelper::map($roles, 'name', 'description');

        return $this->render('update', [
            'model' => $model,
            'actions' => $actions,
            'roles' => $roles,
        ]);
    }

    /**
     * Deletes an existing Role model.
     * @return mixed
     */
    public function actionDelete()
    {
        $result = false;
        if (Yii::$app->request->post('Delete')) {
            $data = Yii::$app->request->post('Delete');
            $auth = Yii::$app->authManager;
            switch ($data['type']) {
                case 'single':
                    if ($data['id'] == 'superAdmin')
                        $result = true;
                    else {
                        $role = $auth->getRole($data['id']);
                        $result = $auth->remove($role);
                    }
                    break;

                case 'multiple':
                    foreach (Json::decode($data['id']) as $item) {
                        if ($item == 'superAdmin')
                            $result = true;
                        else {
                            $role = $auth->getRole($item);
                            $result = $auth->remove($role);
                        }
                    }
                    break;
            }
        }

        if ($result === false)
            Yii::$app->session->setFlash('public-alert', ['type' => 'danger', 'message' => Yii::t('words', 'base.deleteDangerMsg')]);
        else
            Yii::$app->session->setFlash('public-alert', ['type' => 'success', 'message' => Yii::t('words', 'base.deleteSuccessMsg')]);

        return $this->actionIndex();
    }

    /**
     * Finds the Role model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return Role the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Role::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Get all actions of project.
     * @return array of actions
     */
    private function getAllActions()
    {
        $excludeClasses = [
            'app\controllers\ApiController',
            'app\controllers\CronController'
        ];
        $actions = [];
        foreach (glob(Yii::getAlias('@app') . '/controllers/*Controller.php') as $controller) {
            $className = 'app\controllers\\' . basename($controller, '.php');
            if(!in_array($className, $excludeClasses)) {
                $methods = (new \ReflectionClass($className))->getMethods(\ReflectionMethod::IS_PUBLIC);

                $unusableClasses = ['yii\web\Controller', 'yii\base\Controller', 'app\components\MainController'];
                foreach ($methods as $method) {
                    if (in_array($method->class, $unusableClasses))
                        continue;

                    $className = StringHelper::basename($method->class);
                    preg_match('/(app\\\\controllers\\\\)(\w*)(Controller)/', $method->class, $matches);
                    if(!$matches)
                        continue;

                    $class = Yii::$app->createControllerByID(strtolower($matches[2]));

                    if(!method_exists($method->class, 'getSystemActions'))
                        continue;

                    $excludeActions = $class->getSystemActions();
                    foreach($class->getSamePermissions() as $permission => $samePermission) {
                        if (is_string($samePermission))
                            $excludeActions[] = $samePermission;
                        elseif (is_array($samePermission))
                            $excludeActions = array_merge($excludeActions, $samePermission);
                    }

                    if ($method->name == 'actions') {
                        $list = $class->actions();
                        foreach (array_keys($list) as $key) {
                            if(in_array($key, $excludeActions))
                                continue;

                            if (key_exists(lcfirst(substr($className, 0, strpos($className, 'Controller'))), $actions))
                                if (in_array($key, $actions[lcfirst(substr($className, 0, strpos($className, 'Controller')))]))
                                    continue;

                            $actions[lcfirst(substr($className, 0, strpos($className, 'Controller')))][] = $key;
                        }
                    } elseif (preg_match('/action(\w*)/', $method->name) == 1) {
                        $methodBasename = substr($method->name,6);
                        $methodBasename = lcfirst($methodBasename);

                        if(in_array($methodBasename, $excludeActions))
                            continue;
                        if (key_exists(lcfirst(substr($className, 0, strpos($className, 'Controller'))), $actions))
                            if (in_array(lcfirst(substr($method->name, 6)), $actions[lcfirst(substr($className, 0, strpos($className, 'Controller')))]))
                                continue;

                        $actions[lcfirst(substr($className, 0, strpos($className, 'Controller')))][] = lcfirst(substr($method->name, 6));
                    }
                }
            }
        }

        // @TODO: remove this line after create modules table.
        $actions['comments'] = [
            'index',
            'update',
            'delete',
            'changeStatus',
        ];

        return $actions;
    }

    /**
     * Prepare input array to show in jsTree widget
     * @param array $array
     * @param null|array $selected
     * @param null|string $parent
     * @return array
     */
    private function prepareForView($array, $selected = null, $parent = null)
    {
        $temp = [];
        foreach ($array as $key => $value) {
            $node = [
                'name' => is_string($key) ? $key : $parent . '-' . $value,
                'alias' => Yii::t('actions', is_string($key) ? $key : $value),
                'actions' => is_array($value) ? $this->prepareForView($value, $selected, $key) : false,
                'selected' => false,
            ];

            if (!is_null($selected) and !is_array($value) and !is_null($parent))
                if (in_array($parent . ucfirst($value), $selected))
                    $node['selected'] = true;

            $temp[] = $node;
        }
        return $temp;
    }

    public function actionSetDefault($id){
        Setting::set('defaultRole',$id);
        $this->actionIndex();
    }
}
