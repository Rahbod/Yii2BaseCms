<?php

namespace app\models;

use app\components\Helper;
use app\components\Setting;
use Yii;
use yii\base\Model as BaseModel;
use yii\data\ActiveDataProvider;

/**
 * RoleSearch represents the model behind the search form about `app\models\Role`.
 */
class RoleSearch extends Role
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'description', 'rule_name', 'data'], 'safe'],
            [['type', 'created_at', 'updated_at'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return BaseModel::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Role::find()
            ->where(['type' => 1])
            ->andWhere(['<>', 'name', 'superAdmin'])
            ->andWhere(['<>', 'name', 'guest']);

        if(!Yii::$app->request->getQueryParam('sort'))
            $query->orderBy(['name' => SORT_ASC]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => array('pageSize' => Setting::get('gridSize')?:20)
        ]);

        $this->load($params, 'Search');

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'type' => $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['REGEXP', 'name', Helper::persian2Arabic($this->name)])
            ->orFilterWhere(['REGEXP', 'description', Helper::persian2Arabic($this->name)]);

        return $dataProvider;
    }
}
