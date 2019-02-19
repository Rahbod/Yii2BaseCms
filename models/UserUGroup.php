<?php

namespace app\models;

use app\components\CustomActiveRecord;
use Yii;

/**
 * This is the model class for table "userugroup".
 *
 * @property integer $userID
 * @property integer $ugroupID
 *
 * @property User $user
 * @property UGroup $ugroup
 */
class UserUGroup extends CustomActiveRecord
{
    /**
     * This variables determine that the log is executed or not.
     * Each one is empty or not declared, that event will not be logged.
     */
    protected $insertLogCode = Log::EVENT_USER_ASSIGN_TO_GROUP;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'userugroup';
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
            [['userID', 'ugroupID'], 'required'],
            [['userID', 'ugroupID'], 'integer'],
            [['userID'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['userID' => 'id']],
            [['ugroupID'], 'exist', 'skipOnError' => true, 'targetClass' => UGroup::className(), 'targetAttribute' => ['ugroupID' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'userID' => 'User ID',
            'ugroupID' => 'UGroup ID',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'userID']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUGroup()
    {
        return $this->hasOne(UGroup::className(), ['id' => 'ugroupID']);
    }

    /**
     * Return valid query
     * @return \yii\db\ActiveQuery
     */
    public static function validQuery()
    {
        return self::find();
    }
}
