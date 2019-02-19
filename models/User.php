<?php

namespace app\models;

use app\components\DynamicActiveRecord;
use Yii;
use yii\db\Query;
use yii\helpers\Json;
use yii\web\HttpException;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "user".
 *
 * @property integer $id
 * @property string $name
 * @property string $username
 * @property string $password
 * @property string $roleID
 * @property resource $dyna
 * @property string $created
 * @property integer $status
 */
class User extends DynamicActiveRecord implements IdentityInterface
{
    /**
     * This variables determine that the log is executed or not.
     * Each one is empty or not declared, that event will not be logged.
     */
    protected $insertLogCode = Log::EVENT_USER;
    protected $updateLogCode = null; // set null to prevent execution CustomActiveRecord update create log
    protected $deleteLogCode = Log::EVENT_USER_REMOVE_PERMANENTLY;
    private $_old; // to keep old attribute values

    const STATUS_DELETED = -1;
    const STATUS_DISABLE = 0;
    const STATUS_ENABLE = 1;

    public $oldPassword;
    public $repeatPassword;
    public $newPassword;
    public $authKey;
    public $accessToken;

    public static $dynaDefaults = [
        'email' => ['CHAR', ''],
        'phone' => ['CHAR', ''],
        'text' => ['CHAR', ''],
        'image' => ['CHAR', ''],
        'nationalCode' => ['INTEGER', ''],
        'memCode' => ['INTEGER', ''],
        'gender' => ['INTEGER', ''],
        'address' => ['CHAR', ''],
        'birthDate' => ['DATETIME', ''],

        'updated' => ['DATETIME', ''],
        'lastLogin' => ['DATETIME', ''],
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user';
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
            [['name', 'username', 'status', 'roleID', 'email'], 'required', 'except' => ['change_password', 'manual_insert']],
            ['groups', 'required', 'on' => ['insert']],
            [['name', 'username', 'status', 'roleID'], 'required', 'on' => 'manual_insert'],
            [['password'], 'required', 'on' => 'insert'],
            [['newPassword', 'repeatPassword'], 'required', 'on' => 'change_password'],
            [['oldPassword'], 'required', 'on' => 'change_password'],
            [['oldPassword'], 'checkPassword', 'on' => 'change_password'],
            ['repeatPassword', 'compare', 'compareAttribute' => 'password', 'on' => 'insert'],
            ['repeatPassword', 'compare', 'compareAttribute' => 'newPassword', 'on' => 'change_password'],
            ['created', 'default', 'value' => date(Yii::$app->params['dbDateTimeFormat']), 'on' => ['insert', 'manual_insert']],
            //['email', 'email'],
            [['dyna', 'address'], 'string'],
            [['created', 'phone', 'text', 'image', 'nationalCode', 'memCode', 'gender', 'address', 'birthDate'], 'safe'],
            [['status', 'nationalCode', 'memCode', 'gender'], 'integer'],
            [['name', 'username', 'password'], 'string', 'max' => 255],
            [['username'], 'unique'],
            [['roleID'], 'exist', 'skipOnError' => true, 'targetClass' => Role::className(), 'targetAttribute' => ['roleID' => 'name']],
        ];
    }

    public function checkPassword($attribute)
    {
        if(!$this->validatePassword($this->oldPassword, $this->password))
            $this->addError($attribute, Yii::t('words', 'user.wrongOldPassword'));
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('words', 'base.id'),
            'name' => Yii::t('words', 'user.name'),
            'username' => Yii::t('words', 'user.username'),
            'password' => Yii::t('words', 'user.password'),
            'repeatPassword' => Yii::t('words', 'user.repeatPassword'),
            'oldPassword' => Yii::t('words', 'user.oldPassword'),
            'newPassword' => Yii::t('words', 'user.newPassword'),
            'dyna' => 'Dyna',
            'created' => Yii::t('words', 'base.created'),
            'status' => Yii::t('words', 'base.status'),
            'roleID' => Yii::t('words', 'user.roleID'),
            'email' => Yii::t('words', 'user.email'),
            'phone' => Yii::t('words', 'user.phone'),
            'text' => Yii::t('words', 'base.text'),
            'image' => Yii::t('words', 'user.image'),
            'groups' => Yii::t('words', 'user.groups'),
            'group' => Yii::t('words', 'user.group'),
            'nationalCode' => Yii::t('words', 'user.nationalCode'),
            'memCode' => Yii::t('words', 'user.memCode'),
            'gender' => Yii::t('words', 'user.gender'),
            'address' => Yii::t('words', 'user.address'),
            'birthDate' => Yii::t('words', 'user.birthDate')
        ];
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        $user = self::findOne($id);
        return $user ?: null;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        foreach (self::$users as $user) {
            if ($user['accessToken'] === $token) {
                return new static($user);
            }
        }

        return null;
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        $user = self::find()->where(['username' => $username])->one();

        if ($user)
            return $user;

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->getSecurity()->validatePassword($password, $this->password);
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        $this->updated = date(Yii::$app->params['dbDateTimeFormat']);
        if($this->scenario != 'delete-user' && $this->scenario != 'change_password')
            $this->birthDate = date(Yii::$app->params['dbDateTimeFormat'], $this->birthDate);

        if ($this->scenario == 'change_password')
            $this->password = $this->newPassword;

        if ($this->isNewRecord || $this->scenario=='change_password')
            $this->password = Yii::$app->getSecurity()->generatePasswordHash($this->password);

        $this->_old = $this->oldAttributes; // for create log
        return parent::beforeSave($insert);
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        if(!$insert && $this->logging){
            if($this->scenario == 'edit-profile')
                $code = Log::EVENT_USER_EDIT_PROFILE;
            else if($this->scenario == 'delete-user')
                $code = Log::EVENT_USER_REMOVE;
            else
                $code = Log::EVENT_USER_EDIT;

            Log::createUpdateLogWithCode($code, $this->id, self::tableName(),
                Json::encode(['old' => $changedAttributes, 'new' => $this->getNewValues($changedAttributes, $this->attributes)]));
        }

        if($this->scenario == 'edit-profile')
            return;

        if (Yii::$app->request->post('User') and $insert)
            $this->password = Yii::$app->request->post('User')['password'];

        if ($this->groups) {
            if (!$insert){
                $lastRels = UserUGroup::find()->where(['userID' => $this->id])->all();
                foreach ($lastRels as $rel)
                    $rel->delete();
            }

            foreach ($this->groups as $group) {
                $userUGroup = new UserUGroup();
                $userUGroup->userID = $this->id;
                $userUGroup->ugroupID = $group;
                @$userUGroup->save();
            }
        }
    }

    public function getAvatar()
    {
        $path = Yii::getAlias('@web/themes/frontend/svg/default-user.svg');
        if(!Yii::$app->user->isGuest && $this->image && is_file(Yii::getAlias('uploads/users/avatars/') . $this->image)){
            $path = Yii::getAlias('@web/uploads/users/avatars/');
            $path .= $this->image;
        }
        return $path;
    }

    /**
     * @param bool $withSuper
     * @return Query
     */
    public static function validQuery($withSuper = false)
    {
        $query = self::find()
            ->orderBy(['id' => SORT_DESC])
            ->where('username != "guest"')
            ->andWhere(['status' => self::STATUS_ENABLE]);

        if ($withSuper)
            return $query;

        return $query->andWhere(['<>', 'roleID', 'superAdmin']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLogs()
    {
        return $this->hasMany(Log::className(), ['userID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRule()
    {
        return $this->hasOne(AuthRule::className(), ['name' => 'ruleID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserugroups()
    {
        return $this->hasMany(UserUGroup::className(), ['userID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUgroups()
    {
        return $this->hasMany(UGroup::className(), ['id' => 'ugroupID'])->viaTable('userugroup', ['userID' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRole()
    {
        return $this->hasOne(Role::className(), ['name' => 'roleID']);
    }

    /**
     * @return boolean
     */
    public static function isSuperAdmin()
    {
        /* @var $role \yii\rbac\Role */
        $roles = Yii::$app->authManager->getRolesByUser(Yii::$app->user->id);
        foreach ($roles as $role)
            if ($role->name == 'superAdmin')
                return true;

        return false;
    }

    public static function getGenderLabels($gender = null)
    {
        $labels = [
            0 => Yii::t('words', 'user.male'),
            1 => Yii::t('words', 'user.female')
        ];
        if (is_null($gender))
            return $labels;
        return $labels[$gender];
    }

    public static function getStatusLabels($status = null)
    {
        $labels = [
            self::STATUS_DELETED => Yii::t('words', 'base.deleted'),
            self::STATUS_DISABLE => Yii::t('words', 'base.disable'),
            self::STATUS_ENABLE => Yii::t('words', 'base.enable')
        ];
        if (is_null($status))
            return $labels;
        return $labels[$status];
    }
}