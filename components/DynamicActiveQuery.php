<?php
/**
 * @link https://github.com/tom--/dynamic-ar
 * @copyright Copyright (c) 2015 Spinitron LLC
 * @license http://opensource.org/licenses/ISC
 */

namespace app\components;

use Yii;
use yii\base\ErrorException;
use yii\base\UnknownMethodException;
use yii\base\UnknownPropertyException;
use yii\db\ActiveQuery;
use creocoder\nestedsets\NestedSetsQueryBehavior;
use yii\db\Expression;

/**
 * DynamicActiveQuery represents queries on relational data with structured dynamic attributes.
 *
 * DynamicActiveQuery adds to [[ActiveQuery]] a way to write queries that involve
 * the dynamic attributes of DynamicAccessRecord models. This is only possible on
 * a DBMS that supports querying elements in serialized data structures.
 *
 * > NOTE: In this version of Dynamic AR only Maria 10.0+ is supported in this version.
 *
 * Dynamic attribtes names must be enclosed in `(! … !)` (bang-parens) and child attributes in
 * structured dynamic attributes are accessed using dotted notation, for example
 *
 * ```php
 * $model = Product::find()->where(['(!specs.color!)' => 'blue']);
 * ```
 *
 * If there is any need to specify the SQL data type of the dynamic attribute in the query,
 * for example if it appears in an SQL expression that needs this, then the type and dimension
 * can be given in the bang-parents after a `|` (vertical bar or pipe character)
 * following the attribute name, e.g.
 *
 * ```php
 * $model = Product::find()->where(['(! price.unit|DECIMAL(9,2) !) = 14.49']);
 * ```
 *
 * Allowed datatypes are specified in
 * [Maria documentation](https://mariadb.com/kb/en/mariadb/dynamic-columns/#datatypes)
 *
 * Whitespace inside the bang-parens is allowed but not around the vertical bar.
 *
 * @author Tom Worster <fsb@thefsb.org>
 * @author Danil Zakablukovskii danil.kabluk@gmail.com
 *
 * @editor Rahbod Group < http://www.rahbod.com >
 */
class DynamicActiveQuery extends ActiveQuery
{
    /**
     * @var string name of the DynamicActiveRecord's column storing serialized dynamic attributes
     */
    private $_dynamicColumn;

    public function behaviors()
    {
        return [
            // !!! TODO: When the category modules exist, the following line is uncommented
            //NestedSetsQueryBehavior::className(),
        ];
    }

    /**
     * Converts the indexBy column name an anonymous function that writes rows to the
     * result array indexed an attribute name that may be in dotted notation.
     *
     * @param callable|string $column name of the column by which the query results should be indexed
     * @return $this
     */
    public function indexBy($column)
    {
        if (!$this->asArray) {
            return parent::indexBy($column);
        }

        /** @var DynamicActiveRecord $modelClass */
        $modelClass = $this->modelClass;
        $this->indexBy = function ($row) use ($column, $modelClass) {
            if (isset($row[$column])) {
                return $row[$column];
            }

            $dynamicColumn = $modelClass::dynamicColumn();
            if (!isset($row[$dynamicColumn])) {
                throw new UnknownPropertyException("Dynamic column {$dynamicColumn} does not exist - wasn't set in select");
            }

            $dynamicAttributes = DynamicActiveRecord::dynColDecode($row[$dynamicColumn]);
            $value = $this->getDotNotatedValue($dynamicAttributes, $column);

            return $value;
        };

        return $this;
    }

    /**
     * Maria-specific preparation for building a query that includes a dynamic column.
     *
     * @param \yii\db\QueryBuilder $builder
     *
     * @return \yii\db\Query
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function prepare($builder)
    {
        /** @var DynamicActiveRecord $modelClass */
        $modelClass = $this->modelClass;
        $this->_dynamicColumn = $modelClass::dynamicColumn();

        if (empty($this->_dynamicColumn)) {
            /** @var string $modelClass */
            throw new \yii\base\InvalidConfigException(
                $modelClass . '::dynamicColumn() must return an attribute name'
            );
        }

        if (empty($this->select)) {
            $this->select[] = '*';
        }

        if (is_array($this->select) && (in_array('*', $this->select) || in_array($this->getTableNameAndAlias()[1].'.*', $this->select))) {
            $db = $modelClass::getDb();
            $this->select[$this->_dynamicColumn] =
                'COLUMN_JSON(' . $this->getTableNameAndAlias()[1] . '.' . $db->quoteColumnName($this->_dynamicColumn) . ')';
        }

        if (!empty($this->joinWith)) {
            $this->buildJoinWith();
            $this->joinWith = null;    // clean it up to avoid issue https://github.com/yiisoft/yii2/issues/2687
        }

        return parent::prepare($builder);
    }

    /**
     * Generate DB command from ActiveQuery with Maria-specific SQL for dynamic columns.
     *
     * User of DynamicActiveQuery should not normally need to use this method.
     *
     * #### History
     *
     * This implementation is the best I could manage. A dynamic attribute name
     * can appear anywhere that a schema attribute name could appear (select, join, where, ...).
     * It needs to be converted to the Maria SQL using COLUMN_CREATE('name', value, …)
     * for accessing dynamic columns.
     * Because SQL is statically-typed and there is no schema to refer to for dynamic
     * attributes, the accessor SQL must specify the the dyn-col's type, e.g.
     *
     * ```sql
     * WHERE COLUMN_GET(details, 'color' AS CHAR) = 'black'
     * ```
     *
     * In which details is the blob column containing all the dynamic columns, 'color' is the
     * name of a dynamic column that may or may not appear in any given table record, and
     * CHAR means the value should be cast to CHAR before it is compared with 'black'.
     * `COLUMN_GET(details, 'color' AS CHAR)` is the "accessor SQL".
     *
     * So I faced two problems:
     *
     * 1. How to identify a dynamic attribute name in an ActiveQuery?
     * 2. How to choose the type to which it should be cast in the SQL?
     *
     * The design prociple of DynamicAR is "an attribute that isn't an instance variable,
     * a column and doesn't have a magic get-/setter is assumed to be a dynamic attribute".
     * So, in order to infer from the properties of an AQ instance the attribute names
     * that need to be converted to dynamic column accessor SQL, I need to go through
     * the AQ to identify
     * all the column names and remove those in the schema. But I don't know how to
     * identify column names in an AQ instance. Even if I did, there's problem 2.
     *
     * The only way I can imagine to infer datatype from an AQ instance is to look
     * at the context. If the attribute is compared with a bound parameter, that's a clue.
     * If it is being used in an SQL function, e.g. CONCAT(), or being compared with a
     * schema column, that suggests something. But if it is on its own in a SELECT then I am
     * stuck. Also stuck if it is compared with another dynamic attribute. This seems
     * fundamentally intractible.
     *
     * So I decided that the user needs to help DynamicActiveQuery by distinguishing the names
     * of dynamic attributes and by explicitly specifying the type. The format for this:
     *
     *         (!name|type!)
     *
     * Omitting type implies the default type: CHAR. Children of dynamic attributes, i.e.
     * array elements, are separated from parents with `.` (period), e.g.
     * `(!address.country|CHAR!)`. (Spaces are not alowed around the `|`.) So a user can do:
     *
     *     $blueShirts = Product::find()
     *         ->where(['category' => Product::SHIRT, '(!color!)' => 'blue'])
     *         ->all();
     *
     *     $cheapShirts = Product::find()
     *         ->select(
     *             ['sale' => 'MAX((!cost|decimal(6,2)!), 0.75 * (!price.wholesale.12|decimal(6,2)!))']
     *         )
     *         ->where(['category' => Product::SHIRT])
     *         ->andWhere('(!price.retail.unit|decimal(6,2)!) < 20.00')
     *         ->all();
     *
     * The implementation is like db\Connection's quoting of [[string]] and {{string}}. Once
     * the full SQL string is ready, `preg_repalce()` it. The regex pattern is a bit complex
     * and the replacement callback isn't pretty either. Is there a better way to add to
     * `$params` in the callback than this? And for the parameter placeholder counter `$i`?
     *
     * @param null|\yii\db\Connection $db The database connection
     *
     * @return \yii\db\Command the modified SQL statement
     */
    public function createCommand($db = null)
    {
        /** @var DynamicActiveRecord $modelClass */
        $modelClass = $this->modelClass;
        if ($db === null) {
            $db = $modelClass::getDb();
        }

        $dynamicColumn = $modelClass::dynamicColumn();

        // SQL select statement
        if (is_array($this->select) and array_key_exists($dynamicColumn, $this->select)) {
            foreach ($this->select[$dynamicColumn] as $field => $type) {
                if (is_numeric($field)) {
                    $field = $type;
                    $type = 'CHAR';
                }
                $sql = $dynamicColumn;
                $lastPart = $field;
                if (strpos($field, '.') !== false) {
                    $parts = explode('.', $field);
                    $lastPart = array_pop($parts);
                    foreach ($parts as $part)
                        $sql = "COLUMN_GET($sql, '$part' AS BINARY)";
                }
                $this->select[] = "COLUMN_GET($sql, '$lastPart' AS $type) AS '#rhb#{$dynamicColumn}_{$lastPart}'";
            }
        }
        unset($this->select[$dynamicColumn]);

        // SQL where statement
        $subFields = $modelClass::${$dynamicColumn . "Defaults"};
        if (is_array($this->where)) {
            list($tableName, $alias) = $this->getTableNameAndAlias();
            $sql = $alias . '.' . $dynamicColumn;
            foreach ($this->where as $fieldName => $value) {
                if ($subFields && array_key_exists($fieldName, $subFields)) {
                    $lastPart = $fieldName;
                    unset($this->where[$lastPart]);
                    $this->where["COLUMN_GET($sql, '$lastPart' AS CHAR)"] = $value;
                }

                if (is_array($value)) {
                    foreach ($value as $subFieldName => $subValue) {
                        if ($subFields && array_key_exists($subFieldName, $subFields)) {
                            $lastPart = $subFieldName;
                            unset($this->where[$fieldName][$subFieldName]);
                            $this->where[$fieldName]["COLUMN_GET($sql, '$lastPart' AS CHAR)"] = $subValue;
                        }
                    }
                }
            }
        }

        // SQL order by statement
        if (is_array($this->orderBy)) {
            $dynamicFieldsContainer = $modelClass::${$dynamicColumn . 'Defaults'};
            $sortableFields = [];
            $dynamicFields = $dynamicFieldsContainer?array_keys($dynamicFieldsContainer):[];

            foreach($this->orderBy as $key => $value) {
                if ($value instanceof Expression)
                    continue;
                else
                    $sortableFields[] = $key;
            }

            $fields = [];
            foreach ($sortableFields as $sortableField)
                if ($dynamicFields && in_array($sortableField, $dynamicFields))
                    array_push($fields, $sortableField);

            foreach (array_keys($this->orderBy) as $orderByField) {
                if ($fields && in_array($orderByField, $fields)) {
                    try {
                        $type = $dynamicFieldsContainer[$orderByField][0];
                    }catch(ErrorException $e){
                        var_dump($this->orderBy, $orderByField);exit;
                    }
                    $this->orderBy["COLUMN_GET($dynamicColumn, '$orderByField' AS $type)"] = $this->orderBy[$orderByField];
                    unset($this->orderBy[$orderByField]);
                }
            }
        }

        if ($this->sql === null) {
            list ($sql, $params) = $db->getQueryBuilder()->build($this);
        } else {
            $sql = $this->sql;
            $params = $this->params;
        }

        return $db->createCommand($sql, $params);
    }

    /**
     * Returns the value of the element in an array refereced by a dot-notated attribute name.
     *
     * @param array $array an array of attributes and values, possibly nested
     * @param string $attribute the attribute name in dotted notation
     *
     * @return mixed|null the element in $array referenced by $attribute or null if no such
     * element exists
     */
    protected function getDotNotatedValue($array, $attribute)
    {
        $pieces = explode('.', $attribute);
        foreach ($pieces as $piece) {
            if (!is_array($array) || !array_key_exists($piece, $array)) {
                return null;
            }
            $array = $array[$piece];
        }

        return $array;
    }

    private function buildJoinWith()
    {
        $join = $this->join;
        $this->join = [];

        $model = new $this->modelClass;
        foreach ($this->joinWith as $config) {
            list ($with, $eagerLoading, $joinType) = $config;
            $this->joinWithRelations($model, $with, $joinType);

            if (is_array($eagerLoading)) {
                foreach ($with as $name => $callback) {
                    if (is_int($name)) {
                        if (!in_array($callback, $eagerLoading, true)) {
                            unset($with[$name]);
                        }
                    } elseif (!in_array($name, $eagerLoading, true)) {
                        unset($with[$name]);
                    }
                }
            } elseif (!$eagerLoading) {
                $with = [];
            }

            $this->with($with);
        }

        // remove duplicated joins added by joinWithRelations that may be added
        // e.g. when joining a relation and a via relation at the same time
        $uniqueJoins = [];
        foreach ($this->join as $j) {
            $uniqueJoins[serialize($j)] = $j;
        }
        $this->join = array_values($uniqueJoins);

        if (!empty($join)) {
            // append explicit join to joinWith()
            // https://github.com/yiisoft/yii2/issues/2880
            $this->join = empty($this->join) ? $join : array_merge($this->join, $join);
        }
    }

    /**
     * Modifies the current query by adding join fragments based on the given relations.
     * @param ActiveRecord $model the primary model
     * @param array $with the relations to be joined
     * @param string|array $joinType the join type
     */
    private function joinWithRelations($model, $with, $joinType)
    {
        $relations = [];

        foreach ($with as $name => $callback) {
            if (is_int($name)) {
                $name = $callback;
                $callback = null;
            }

            $primaryModel = $model;
            $parent = $this;
            $prefix = '';
            while (($pos = strpos($name, '.')) !== false) {
                $childName = substr($name, $pos + 1);
                $name = substr($name, 0, $pos);
                $fullName = $prefix === '' ? $name : "$prefix.$name";
                if (!isset($relations[$fullName])) {
                    $relations[$fullName] = $relation = $primaryModel->getRelation($name);
                    $this->joinWithRelation($parent, $relation, $this->getJoinType($joinType, $fullName));
                } else {
                    $relation = $relations[$fullName];
                }
                $primaryModel = new $relation->modelClass;
                $parent = $relation;
                $prefix = $fullName;
                $name = $childName;
            }

            $fullName = $prefix === '' ? $name : "$prefix.$name";
            if (!isset($relations[$fullName])) {
                $relations[$fullName] = $relation = $primaryModel->getRelation($name);
                if ($callback !== null) {
                    call_user_func($callback, $relation);
                }
                if (!empty($relation->joinWith)) {
                    $relation->buildJoinWith();
                }
                $this->joinWithRelation($parent, $relation, $this->getJoinType($joinType, $fullName));
            }
        }
    }

    /**
     * Joins a parent query with a child query.
     * The current query object will be modified accordingly.
     * @param DynamicActiveQuery $parent
     * @param DynamicActiveQuery $child
     * @param string $joinType
     */
    private function joinWithRelation($parent, $child, $joinType)
    {
        $via = $child->via;
        $child->via = null;
        if ($via instanceof DynamicActiveQuery) {
            // via table
            $this->joinWithRelation($parent, $via, $joinType);
            $this->joinWithRelation($via, $child, $joinType);
            return;
        } elseif (is_array($via)) {
            // via relation
            $this->joinWithRelation($parent, $via[1], $joinType);
            $this->joinWithRelation($via[1], $child, $joinType);
            return;
        }

        list ($parentTable, $parentAlias) = $parent instanceof DynamicActiveQuery ? $parent->getTableNameAndAlias() : self::getTableNameAndAliasFromObj($parent);
        list ($childTable, $childAlias) = $child instanceof DynamicActiveQuery ? $child->getTableNameAndAlias() : self::getTableNameAndAliasFromObj($child);

        if (!empty($child->link)) {

            if (strpos($parentAlias, '{{') === false) {
                $parentAlias = '{{' . $parentAlias . '}}';
            }
            if (strpos($childAlias, '{{') === false) {
                $childAlias = '{{' . $childAlias . '}}';
            }

            $on = [];
            foreach ($child->link as $childColumn => $parentColumn) {
                if (strpos($childColumn, 'COLUMN_GET') === false)
                    $on[] = "$parentAlias.[[$parentColumn]] = $childAlias.[[$childColumn]]";
                else
                    $on[] = "$parentAlias.[[$parentColumn]] = $childColumn";
            }
            $on = implode(' AND ', $on);
            if (!empty($child->on)) {
                $on = ['and', $on, $child->on];
            }
        } else {
            $on = $child->on;
        }
        $this->join($joinType, empty($child->from) ? $childTable : $child->from, $on);

        if (!empty($child->where)) {
            $this->andWhere($child->where);
        }
        if (!empty($child->having)) {
            $this->andHaving($child->having);
        }
        if (!empty($child->orderBy)) {
            $this->addOrderBy($child->orderBy);
        }
        if (!empty($child->groupBy)) {
            $this->addGroupBy($child->groupBy);
        }
        if (!empty($child->params)) {
            $this->addParams($child->params);
        }
        if (!empty($child->join)) {
            foreach ($child->join as $join) {
                $this->join[] = $join;
            }
        }
        if (!empty($child->union)) {
            foreach ($child->union as $union) {
                $this->union[] = $union;
            }
        }
    }

    /**
     * Returns the join type based on the given join type parameter and the relation name.
     * @param string|array $joinType the given join type(s)
     * @param string $name relation name
     * @return string the real join type
     */
    private function getJoinType($joinType, $name)
    {
        if (is_array($joinType) && isset($joinType[$name])) {
            return $joinType[$name];
        } else {
            return is_string($joinType) ? $joinType : 'INNER JOIN';
        }
    }

    /**
     * Returns the table name and the table alias for [[modelClass]].
     * @return array the table name and the table alias.
     * @internal
     */
    protected function getTableNameAndAlias()
    {
        if (empty($this->from)) {
            $tableName = $this->getPrimaryTableName();
        } else {
            $tableName = '';
            foreach ($this->from as $alias => $tableName) {
                if (is_string($alias)) {
                    return [$tableName, $alias];
                } else {
                    break;
                }
            }
        }

        if (preg_match('/^(.*?)\s+({{\w+}}|\w+)$/', $tableName, $matches)) {
            $alias = $matches[2];
        } else {
            $alias = $tableName;
        }

        return [$tableName, $alias];
    }

    /**
     * @param ActiveQuery $object
     * @return array the table name and the table alias.
     */
    private static function getTableNameAndAliasFromObj($object)
    {
        $model = Yii::createObject($object->modelClass);
        return [$model::tableName(), $model::tableName()];
    }
}
