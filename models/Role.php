<?php

namespace app\models;

use app\components\CustomActiveRecord;
use app\components\Setting;
use Yii;
use yii\db\Expression;
use yii\web\HttpException;

/**
 * This is the model class for table "auth_item".
 *
 * @property string $name
 * @property integer $type
 * @property string $description
 * @property string $rule_name
 * @property resource $data
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property AuthRule $ruleName
 * @property AuthItemChild[] $authItemChildren
 * @property AuthItemChild[] $authItemChildren0
 * @property Role[] $children
 * @property Role $parent
 */
class Role extends CustomActiveRecord
{
    public $actions;

    private static $_GuestPermissions = [
        'site-index',
        'item-view',
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'auth_item';
    }

    /**
     * @inheritdoc
     */
    public static function dynamicColumn()
    {
        return 'dyna';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['description'], 'required'],
            ['name', 'unique'],
            [['type', 'created_at', 'updated_at'], 'integer'],
            [['description', 'data'], 'string'],
            [['name', 'rule_name'], 'string', 'max' => 64],
            [['rule_name'], 'exist', 'skipOnError' => true, 'targetClass' => AuthRule::className(), 'targetAttribute' => ['rule_name' => 'name']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'name' => Yii::t('words', 'base.name'),
            'type' => 'Type',
            'description' => Yii::t('words', 'base.alias'),
            'rule_name' => 'Rule Name',
            'data' => 'Data',
            'created_at' => Yii::t('words', 'base.created'),
            'updated_at' => Yii::t('words', 'role.updated'),
            'parent' => Yii::t('words', 'role.parent'),
            'child' => Yii::t('words', 'role.child'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthAssignments()
    {
        return $this->hasMany(AuthAssignment::className(), ['item_name' => 'name']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRuleName()
    {
        return $this->hasOne(AuthRule::className(), ['name' => 'rule_name']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthItemChildren()
    {
        return $this->hasMany(AuthItemChild::className(), ['parent' => 'name']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthItemChildren0()
    {
        return $this->hasMany(AuthItemChild::className(), ['child' => 'name']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChildren()
    {
        return $this->hasMany(Role::className(), ['name' => 'child'])->viaTable('auth_item_child', ['parent' => 'name']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(Role::className(), ['name' => 'parent'])
            ->viaTable('auth_item_child', ['child' => 'name']);
    }

    /**
     * Return valid query
     * @return \yii\db\ActiveQuery
     */
    public static function validQuery()
    {
        if (Setting::get('defaultRole'))
            return self::find()
                ->where(['type' => 1])
                ->andWhere(['<>', 'name', 'superAdmin'])
                ->orderBy(new Expression('FIELD(`name`, :default) DESC', [':default' => Setting::get('defaultRole')]));
        return self::find()
            ->where(['type' => 1])
            ->andWhere(['<>', 'name', 'superAdmin'])
            ->orderBy('`name`');
    }

    public static function getOrCreateGuestRole()
    {
        /** @var $model Role */
        $auth = Yii::$app->authManager;
        $model = self::find()->andWhere(['name' => 'guest'])->one();

        if (!$model) {
            $role = $auth->createRole('guest');
            $role->description = 'Guest Role';
            if ($auth->add($role)) {
                $model = Role::find()->andWhere(['name' => 'guest'])->one();
                // Remove all permissions
                $actions = self::$_GuestPermissions;
                $auth->removeChildren($role);
                if ($actions) {
                    // Add new permissions
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
            } else
                throw new HttpException(500, 'Create Guest Role Failed.');
        }

        return $model;
    }

    /**
     * Get list of parents.
     * @return Role[]
     */
    public function getParents()
    {
        $parents = [];
        if ($this->parent) {
            $parents[] = $this->parent;
            $parents = array_merge($parents, $this->parent->getParents());
        }
        return $parents;
    }
}