<?php

namespace app\models;

use app\components\Helper;
use app\components\Setting;
use Yii;
use yii\base\Model AS BaseModel;
use yii\data\ActiveDataProvider;

/**
 * UserSearch represents the model behind the search form about `app\models\User`.
 */
class UserSearch extends User
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'status'], 'integer'],
            [['name', 'username', 'password', 'dyna', 'created', 'roleID'], 'safe'],
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
        $query = User::validQuery();

        if(!Yii::$app->request->getQueryParam('sort'))
            $query->orderBy(['id' => SORT_DESC]);

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
            'id' => $this->id,
            'created' => $this->created,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['REGEXP', 'name', Helper::persian2Arabic($this->name)])
            ->orFilterWhere(['REGEXP', 'username', Helper::persian2Arabic($this->name)])
            ->orFilterWhere(['REGEXP', 'roleID', Helper::persian2Arabic($this->name)]);

        $query->andWhere(['<>', 'roleID', 'superAdmin']);

        return $dataProvider;
    }
}
