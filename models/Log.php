<?php

namespace app\models;

use Yii;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\db\Query;

/**
 * This is the model class for table "log".
 *
 * @property integer $id
 * @property integer $userID
 * @property integer $code
 * @property integer $action
 * @property string $model
 * @property integer $modelID
 * @property string $values
 * @property string $date
 * @property string $time
 * @property string $modelName
 *
 * @property User $user
 */
class Log extends ActiveRecord
{
    public $modelName = '-';

    // Actions
    const ACTION_GLOBAL = 0;
    const ACTION_INSERT = 1;
    const ACTION_UPDATE = 2;
    const ACTION_DELETE = 3;
    const ACTION_SEARCH = 4;

    // User
    const EVENT_USER = 1300;
    const EVENT_USER_ADD = 1301;
    const EVENT_USER_EDIT = 1302;
    const EVENT_USER_REMOVE = 1303;
    const EVENT_USER_REMOVE_PERMANENTLY = 1304;
    const EVENT_USER_LOGIN = 1311;
    const EVENT_USER_LOGOFF = 1312;
    const EVENT_USER_EDIT_PROFILE = 1313;
    const EVENT_USER_ADD_GROUP = 1351;
    const EVENT_USER_EDIT_GROUP = 1352;
    const EVENT_USER_REMOVE_GROUP = 1353;
    const EVENT_USER_ASSIGN_TO_GROUP = 1360;

    // !!! @TODO: Define events constant

    private static $_ActionLabels = [
        self::ACTION_GLOBAL => 'Global Action',
        self::ACTION_INSERT => 'Insert Action',
        self::ACTION_UPDATE => 'Update Action',
        self::ACTION_DELETE => 'Delete Action',
//        self::ACTION_SEARCH => 'Search Action'
    ];

    private static $_EventsLabels = [
        // !!! @TODO: above todo...
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'log';
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
            [['userID', 'code', 'action'], 'required'],
            [['userID', 'code', 'action', 'modelID'], 'integer'],
            [['values'], 'string'],
            [['date', 'time'], 'safe'],
            [['model'], 'string', 'max' => 15],
            [['userID'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['userID' => 'id']],
            ['date', 'default', 'value' => date(Yii::$app->params['dbDateFormat'])],
            ['time', 'default', 'value' => date(Yii::$app->params['dbTimeFormat'])],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('words', 'base.id'),
            'userID' => Yii::t('words', 'base.userID'), // User who did the action. Null is system actions
            'code' => Yii::t('words', 'log.code'), // Action code. Codes are defined in excel file
            'action' => Yii::t('words', 'log.action'), // 1: Insert 2: Update 3: Delete
            'model' => Yii::t('words', 'log.model'), // Table of item. NULL is reserved.
            'modelID' => Yii::t('words', 'log.modelID'), // Item ID, NULL is reserved for system activities.
            'values' => Yii::t('words', 'log.values'), // JSON decoded values of old and new values. {'old':[], 'new':[]}
            'date' => Yii::t('words', 'log.date'),
            'time' => Yii::t('words', 'log.time'),
            'modelName' => Yii::t('words', 'log.modelName'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'userID']);
    }

    public static function getActionLabels()
    {
        return self::$_ActionLabels;
    }

    public static function getEventsLabels()
    {
        return self::$_EventsLabels;
    }

    public function getActionLabel()
    {
        return key_exists($this->action, self::getActionLabels()) ? self::$_ActionLabels[$this->action] : null;
    }

    public function getEventLabel()
    {
        return key_exists($this->code, self::getEventsLabels()) ? self::$_EventsLabels[$this->code] : null;
    }

    /**
     * Create new log
     *
     * @param $action
     * @param $code
     * @param null $model
     * @param null $modelID
     * @param null $values
     * @return bool
     * @throws Exception
     */
    public static function create($action, $code, $model = null, $modelID = null, $values = null)
    {
        if (!Yii::$app->user->isGuest &&
            key_exists($action, self::getActionLabels()) &&
            key_exists($code, self::getEventsLabels())
        ) {
            $log = new self;
            $log->attributes = [
                'userID' => Yii::$app->user->getId(),
                'code' => $code,
                'action' => $action,
                'model' => $model,
                'modelID' => $modelID,
                'values' => $values,
                'date' => date(Yii::$app->params['dbDateFormat'], time()),
                'time' => date(Yii::$app->params['dbTimeFormat'], time())
            ];
            if ($log->save())
                return true;
            return false;
        }
        return false;
    }

    /**
     * Create Update Log
     * @param $code
     * @param $primaryKey
     * @param $tableName
     * @param $values
     * @throws Exception
     */
    public static function createUpdateLogWithCode($code, $primaryKey, $tableName, $values)
    {
        Log::create(Log::ACTION_UPDATE, $code, $tableName, $primaryKey, $values);
    }

    public function afterFind()
    {
        parent::afterFind(); // TODO: Change the autogenerated stub

        $targetField = null;
        switch ($this->model) {
            case User::tableName():
                $targetField = 'name';
                break;
        }

        if ($targetField) {
            $query = new Query();
            $this->modelName = $query->select([$targetField])
                ->from($this->model)
                ->where(['id' => $this->modelID])
                ->one();
            $this->modelName = $this->modelName ? $this->modelName[$targetField] : '-';
        }
    }
}