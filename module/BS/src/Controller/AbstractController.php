<?php

namespace BS\Controller;

use BS\Controller\Exception\AppException;
use BS\Db\Model\AbstractModel;
use BS\Db\TableGateway\AbstractTableGateway;
use BS\Exception;
use BS\ServiceLocatorAwareInterface;
use BS\Traits\ServiceLocatorAwareTrait;
use BS\I18n\Translator\TranslatorAwareTrait;
use BS\Utility\Measure;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Expressive\Template\TemplateRendererInterface;
use Zend\Json\Decoder;
use Zend\Json\Json;

abstract class AbstractController implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait, TranslatorAwareTrait;

    const DEFAULT_RECORD_LIMIT = 50;
    const MAXIMUM_RECORD_LIMIT = 500;
    const DEFAULT_RECORD_START = 0;

    protected $selectLimit;
    protected $selectOffset;

    public $isHtml = false;

    protected $measureConvert = false;

    /**
     * @var string Model ClassName for this Controller
     */
    protected $modelClass;

    /**
     * @var string TableGateway ClassName for this Controller
     */
    protected $tableGatewayClass;
    /**
     * @var \BS\Db\TableGateway\AbstractTableGateway
     */
    protected $tableGatewayInstance;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var array
     */
    protected $params = null;

    /**
     * @return AbstractTableGateway
     * @throws Exception
     */
    public function getTableGateway()
    {
        if (!$this->tableGatewayInstance) {
            if ($this->tableGatewayClass) {
                $this->tableGatewayInstance = $this->serviceLocator->get($this->tableGatewayClass);
            } else {
                throw new Exception('No TableGateway class has been defined in this Controller');
            }
        }

        return $this->tableGatewayInstance;
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return $this
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;

        return $this;
    }

    public function getModuleName()
    {
        return $this->request->getAttribute('module');
    }

    public function getControllerName()
    {
        return $this->request->getAttribute('controller');
    }

    public function readPre(array $params)
    {
        return $params;
    }

    public function readPost(array $returnData)
    {
        return $returnData;
    }

    public function indexAction()
    {
        $data = $this->readPre($this->getParams());

        $tableGateway = $this->getTableGateway();
        $select = $this->prepareSelect();

        $id = $this->getParam('id');

        if ($id) {
            $resultSet = $this->getTableGateway()->selectWith(
                $tableGateway->injectSelect($select->where(
                    $this->getTableGateway()->decodeCompositeKey($id)
                )->limit(1), $data)->getInjectedSelect()
            );
            $foundRows = $resultSet->count();
            if ($foundRows != 1) {
                throw new AppException('can\'t find the record');
            }
        } else {
            list($resultSet, $foundRows) = $tableGateway->injectSelect($select, $data)->fetchAll();
        }

        $resultSet = $resultSet->toArray();
        if ($this->measureConvert && $this->serviceLocator->has('Measure')) {
            $resultSet = $this->getMeasureService()->getConvertList($resultSet);
        }

        if ($id) {
            $returnData = current($resultSet);
        } else {
            $returnData = [
                'total' => $foundRows,
                'start' => $this->selectOffset,
                'limit' => $this->selectLimit,
                'list' => $resultSet
            ];
        }

        $returnData = $this->readPost($returnData);

        return $this->respond(true, $returnData);
    }

    public function createPre(array $params)
    {
        return $params;
    }

    public function createPost(array $params, AbstractModel $model)
    {
    }

    public function updatePre(array $params)
    {
        return $params;
    }

    public function updatePost(array $params, AbstractModel $model)
    {

    }

    public function postAction()
    {
        /**
         * @var \BS\Db\Model\AbstractModel $Model
         */
        $Model = new $this->modelClass($this->getParams());
        $isUpdate = false;
        $compositeKeys = $this->getTableGateway()->decodeCompositeKey($Model->getId());
        /** @var AbstractModel $OldModel */
        if (count($compositeKeys) == count($this->getTableGateway()->getPrimaryKeys())) {
            if ($OldModel = $this->getTableGateway()->select($compositeKeys)->current()) {
                $isUpdate = true;
            }
        }

        if ($isUpdate) {
            $data = $this->updatePre($this->getParams());
            $data = $this->getMeasureService()->saveConvertArray($data);
            $Model = $this->getTableGateway()->getModel($OldModel->getArrayCopy())->exchangeArray($data);
            $this->getTableGateway()->saveUpdate($Model, $OldModel);
            $Model = $this->getTableGateway()->get($Model->getId());
            $this->updatePost($data, $Model);
        } else {
            $data = $this->createPre($this->getParams());
            $data = $this->getMeasureService()->saveConvertArray($data);
            $Model->exchangeArray($data);
            $id = $this->getTableGateway()->saveInsert($Model);
            $Model = $this->getTableGateway()->get($id);
            $this->createPost($data, $Model);
        }

        return $this->respond(true, $Model->getArrayCopy());
    }

    public function deletePre(array $params)
    {
    }

    public function deletePost(array $params)
    {

    }

    public function deleteAction()
    {
        $params = $this->getParams();
        $id = $this->getParam('id');

        $tableGateway = $this->getTableGateway();
        $tableGateway->delete($this->getTableGateway()->decodeCompositeKey($id));
        $this->deletePost($params);

        return $this->respond();
    }

    public function deleteListAction()
    {
        $data = $this->getParams();
        $ids = $data['ids'];
        if (!is_array($ids)) {
            $ids = array_filter(explode('_', $ids));
        }
        $isCompositeKey = count($this->getTableGateway()->getPrimaryKeys()) > 1 ? true : false;

        $this->deletePre($data);
        if ($isCompositeKey) {
            foreach ($ids as $id) {
                $this->getTableGateway()->delete(
                    $this->getTableGateway()->decodeCompositeKey(str_replace('|', '_', $id)));
            }
        } else {
            $this->getTableGateway()->delete(['id' => $ids]);
        }
        $this->deletePost($data);

        return $this->respond();
    }

    /**
     *
     * @throws Exception
     * @return Measure
     */
    protected function getMeasureService()
    {
        $Measure = $this->serviceLocator->get('Measure')->initWithConfig();
        $measureFields = $this->getTableGateway()->getMeasureField();

        if (isset($measureFields['lengthFields']) && !empty($measureFields['lengthFields'])) {
            $Measure->lengthColumnNames = $measureFields['lengthFields'];
        }
        if (isset($measureFields['weightFields']) && !empty($measureFields['weightFields'])) {
            $Measure->weightColumnNames = $measureFields['weightFields'];
        }
        if (isset($measureFields['volumeFields']) && !empty($measureFields['volumeFields'])) {
            $Measure->volumeColumnNames = $measureFields['volumeFields'];
        }

        return $Measure;
    }

    /**
     * @param      $name
     * @param null $defaultValue
     *
     * @return mixed|null
     */
    protected function getParam($name, $defaultValue = null)
    {
        $params = $this->getParams();

        return isset($params[$name]) ? $params[$name] : $defaultValue;
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        if (is_null($this->params)) {
            $params = $this->request->getQueryParams() ?? [];
            $contentType = $this->request->getHeaderLine('Content-Type');
            if (!empty($contentType) && strstr('application/json', strtolower($contentType))) {
                $params = array_merge(
                    $params,
                    ($this->request->getBody()->getContents() ?
                        Json::decode($this->request->getBody()->getContents(), Json::TYPE_ARRAY) : [])
                );
            } else {
                $params = array_merge(
                    $params,
                    (array)$this->request->getParsedBody()
                );
            }

            if (!isset($params['id']) || empty($params['id'])) {
                $params['id'] = $this->request->getAttribute('id');
            }

            $this->params = $params;

            return $params;
        } else {
            return $this->params;
        }
    }

    public function setParams($params)
    {
        $Request = $this->getRequest()->withQueryParams($params);
        $this->setRequest($Request);
        $this->params = $params;
    }

    public function prepareSelect(Select $select = null)
    {
        if (empty($select)) {
            $select = $this->getTableGateway()->getSql()->select();
        }

        $select = $this->prepareSelectSetSelectFields($select);
        $select = $this->prepareSelectSetFilters($select);
        $select = $this->prepareSelectSetSearch($select);
        $select = $this->prepareSelectSetLimit($select);
        $select = $this->prepareSelectSetWhere($select);
        $select = $this->prepareSelectSetSortInfo($select);
        $select = $this->prepareSelectSetDateLimit($select);

        return $select;
    }


    protected function prepareSelectSetSelectFields(Select $select)
    {
        $params = $this->getParams();
        $selectFields = [];
        if (isset($params['selectFields'])) {
            $selectFields = explode(',', $params['selectFields']);
        }

        if (count($selectFields) == 0) {
            $selectFields[] = Select::SQL_STAR;
        } else {
            //select all primary keys whatsoever
            $primaryKeys = $this->getTableGateway()->getPrimaryKeys();
            $selectFields = array_merge($selectFields, $primaryKeys);
        }

        $cols = [];
        foreach ($selectFields as $column) {
            if (is_string($column) && $column != Select::SQL_STAR) {
                $columnString = str_replace('-', '.', $column);
                if (!strpos($columnString, '.')) {
                    $columnString = $this->getTableGateway()->getTable() . '.' . $columnString;
                }
                $cols[$column] = new Expression($columnString);
            } else {
                $cols[] = $column;
            }
        }

        if ($cols && (count($cols) > 0)) {
            $select->columns($cols);
        }

        return $select;
    }

    /**
     * @param             $filter
     * @param Select|null $select
     *
     * @return mixed
     */
    protected function onBeforeProcessFilter($filter, Select $select = null)
    {
        return $filter;
    }

    /**
     * @param Select $select
     * @param array $params
     *
     * @return Select
     * @codeCoverageIgnore Ignore code coverage here to prevent incorrect coverage detection
     */
    protected function prepareSelectSetFilters(Select $select, $params = [])
    {
        if (empty($params)) {
            $params = $this->getParams();
        }

        if (!empty($params['includeNullFields'])) {
            $nullFields = explode(',', $params['includeNullFields']);
            if (!empty($nullFields)) {
                $nullFields = array_map(function ($val) {
                    if (stripos($val, '-') > 0) {
                        return str_replace('-', '.', $val);
                    } else {
                        return $this->getTableGateway()->getTable() . '.' . $val;
                    }
                }, $nullFields);
            }
        }

        if (!empty($params['filter'])) {
            $filters = Json::decode($params['filter'], Json::TYPE_ARRAY);
            $customizedFields = $this->getTableGateway()->getCustomizedFilterFields();
            foreach ($filters as $filter) {
                $conditionOperator = 'where';
                // Stupid ExtJS use 'property' as property name in Store Filter
                // And 'field' as property name in Grid Filter, I have to standarlise them
                $filter['field'] = empty($filter['property']) ? $filter['field'] : $filter['property'];
                if (isset($filter['field']) && array_key_exists($filter['field'], $customizedFields)) {
                    if (is_array($customizedFields[$filter['field']])) {
                        if (isset($customizedFields[$filter['field']]['use_having']) &&
                            $customizedFields[$filter['field']]['use_having'] == 1
                        ) {
                            $conditionOperator = 'having';
                            $filter['field'] = $customizedFields[$filter['field']]['field'];
                        }
                    } else {
                        $filter['field'] = $customizedFields[$filter['field']];
                    }
                } else {

                    $filter = $this->onBeforeProcessFilter($filter, $select);

                    if (empty($filter['field']) || !isset($filter['value'])) {
                        continue;
                    }

                    $filter['field'] = str_replace('-', '.', $filter['field']);

                    if (!@strlen($filter['field'])) {
                        continue;
                    }

                    if (is_string($filter['value'])) {
                        $filter['value'] = trim($filter['value']);
                        if (0 == strlen($filter['value'])) {
                            continue;
                        }
                    }

                    if (!strstr($filter['field'], '.')) {
                        $filter['field'] = $this->getTableGateway()->getTable() . '.' . $filter['field'];
                    }
                }
                switch (@$filter['type']) {
                    case 'string':
                        $select->$conditionOperator([
                            $filter['field'] . ' LIKE ?'
                            => '%' . $filter['value'] . '%',
                        ]);
                        break;
                    case 'boolean':
                        $select->$conditionOperator([
                            $filter['field'] . ' = ?'
                            => $filter['value'] == true ? 1 : 0,
                        ]);
                        break;
                    case 'numeric':
                        $operatorMap = [
                            'ne' => '!=',
                            'eq' => '=',
                            'lt' => '<=',
                            'gt' => '>=',
                        ];

                        $filter['comparison'] =
                            array_key_exists('comparison', $filter) ? $filter['comparison'] : 'eq';
                        if (!array_key_exists($filter['comparison'], $operatorMap)) {
                            continue;
                        }
                        if (isset($nullFields) && intval($filter['value']) <= 0 &&
                            in_array($filter['comparison'], ['eq', 'lt']) && in_array($filter['field'], $nullFields)
                        ) {
                            $select->$conditionOperator([$filter['field'] . ' IS NULL']);
                        } else {
                            $select->$conditionOperator([
                                $filter['field'] . ' ' . $operatorMap[$filter['comparison']] . ' ?'
                                => $filter['value'],
                            ]);
                        }
                        break;
                    case 'date':
                        $operatorMap = [
                            'ne' => '!=',
                            'eq' => '=',
                            'lt' => '<=',
                            'gt' => '>=',
                        ];

                        $filter['comparison'] =
                            array_key_exists('comparison', $filter) ? $filter['comparison'] : 'eq';
                        if (!array_key_exists($filter['comparison'], $operatorMap)) {
                            continue;
                        }
                        if ($filter['comparison'] == 'eq') {
                            $select->$conditionOperator([
                                $filter['field'] . " BETWEEN '? 00:00:00' AND '? 23:59:59'"
                                => [new Expression($filter['value']), new Expression($filter['value'])],
                            ]);
                        } else {
                            $select->$conditionOperator([
                                $filter['field'] . ' ' . $operatorMap[$filter['comparison']] . ' ?'
                                => new Expression("'" . $filter['value'] . "'"),
                            ]);
                        }
                        break;
                    case 'list':
                        $select->$conditionOperator->in(new Expression($filter['field']), $filter['value']);
                        break;
                    default:
                        $select->$conditionOperator([$filter['field'] . ' = ?' => $filter['value']]);
                }
            }
        }

        return $select;
    }


    /**
     * @param Select $select
     *
     * @return Select
     * @throws Exception
     */
    protected function prepareSelectSetSearch(Select $select)
    {
        $params = $this->getParams();
        if (array_key_exists('field', $params) && array_key_exists('query', $params)) {
            $select->where([
                "{$this->getTableGateway()->getTable()}.{$params['field']} LIKE ?" => "%{$params['query']}%",
            ]);
        } elseif (array_key_exists('fields', $params) && array_key_exists('operators', $params) &&
            array_key_exists('values', $params)
        ) {
            $fields = @$params['fields'];
            $operators = @$params['operators'];
            $values = @$params['values'];

            if (sizeof($fields) != sizeof($operators)) {
                throw new AppException('Search criterias wrong!');
            }
            if (($fields != null) && ($operators != null) && ($values != null)) {
                $this->_setConditions($select, $fields, $operators, $values);
            }
        }

        return $select;
    }

    private function _setConditions(Select $select, $fields, $operators, $values)
    {
        if (!is_array($values)) {
            return $select->where(["{$this->getTableGateway()->getTable()}.{$fields[0]} {$operators[0]} ?" => $values]);
        }

        foreach ($fields as $key => $value) {
            if (@$values[$key]) {
                if (strtoupper($operators[$key]) == 'LIKE') {
                    $values[$key] = "%$values[$key]%";
                }

                $select->where([
                    "{$this->getTableGateway()->getTable()}.{$fields[$key]} {$operators[$key]} ?" => $values[$key],
                ]);
            }
        }

        return $select;
    }

    /**
     * @param Select $select
     *
     * @return Select
     * @throws Exception
     */
    protected function prepareSelectSetWhere(Select $select)
    {
        $params = $this->getParams();
        $primaryKeys = $this->getTableGateway()->getPrimaryKeys();
        $allKeys = array_merge($primaryKeys, $this->getTableGateway()->getSearchFields());
        foreach ($allKeys as $key) {
            if (array_key_exists($key, $params) && $params[$key]) {
                $arrField = explode('-', $key);
                if (count($arrField) < 2) {
                    array_unshift($arrField, $this->getTableGateway()->getTable());
                }
                $field = implode('.', $arrField);
                $select->where(["{$field} = ?" => $params[$key]]);
            }
        }

        return $select;
    }

    /**
     * @param Select $select
     *
     * @return Select
     */
    protected function prepareSelectSetSortInfo(Select $select)
    {
        $sortInfos =
            $this->getParam('sort') ? Decoder::decode($this->getParam('sort'), Json::TYPE_ARRAY) : null;
        $customizedSortFields = $this->getTableGateway()->getCustomizedSortFields();
        if ($sortInfos) {
            foreach ($sortInfos as $sort) {
                if (in_array($sort['property'], $customizedSortFields)) {
                    $select->order($sort['property'] . ' ' . $sort['direction']);
                } else {
                    if (array_key_exists($sort['property'], $customizedSortFields)) {
                        $select->order($customizedSortFields[$sort['property']] . ' ' . $sort['direction']);
                    } else {
                        $sort['property'] = str_replace('-', '.', $sort['property']);
                        $sort['property'] = strpos($sort['property'], '.') ? $sort['property'] :
                            $this->getTableGateway()->getTable() . '.' . $sort['property'];
                        $select->order($sort['property'] . ' ' . $sort['direction']);
                    }
                }
            }
        }

        return $select;
    }

    protected function prepareSelectSetDateLimit(Select $select)
    {
        return $select;
    }

    protected function prepareSelectSetLimit(Select $select)
    {
        $limit = $this->getParam('limit') && $this->getParam('limit') <= static::MAXIMUM_RECORD_LIMIT
            ? $this->getParam('limit')
            : static::DEFAULT_RECORD_LIMIT;

        $this->selectOffset = is_numeric($this->getParam('start')) && $this->getParam('start') >= 0 ?
            (int)$this->getParam('start') : static::DEFAULT_RECORD_START;
        $this->selectLimit = static::DEFAULT_RECORD_LIMIT;

        if ($limit > 0) {
            $this->selectLimit = (int)$limit;
        } elseif ($limit == -1) {
            $this->selectLimit = -1;
        }

        if ($this->selectLimit != -1) {
            $select->limit($this->selectLimit);
        }

        return $select->offset($this->selectOffset);
    }

    protected function respond($success = true, $data = [], $message = '', $errCode = '')
    {
        $returnData = [
            'success' => $success,
            'errCode' => $errCode,
            'message' => $message,
            'data' => $data,
        ];

        return new JsonResponse($returnData);
    }

    protected function respondList(Array $data, $message = '')
    {
        $listData = [
            'total' => count($data),
            'start' => is_null($this->selectOffset) ? 0 : $this->selectOffset,
            'limit' => is_null($this->selectLimit) ? 0 : $this->selectLimit,
            'list' => $data
        ];

        return $this->respond(true, $listData, $message);
    }

    /**
     * @return \Zend\Db\Adapter\Driver\AbstractConnection
     */
    protected function getDbConnection()
    {
        /** @var \Zend\Db\Adapter\Driver\AbstractConnection $connection */
        $connection = $this->getDbAdapter()->getDriver()->getConnection();

        return $connection;
    }

    /**
     * @return \Zend\Db\Adapter\Adapter
     */
    protected function getDbAdapter()
    {
        return $this->serviceLocator->get('db');
    }

    /**
     * @param      $template
     * @param      $data
     *
     * @return mixed
     */
    protected function getViewRenderResult($template, $data)
    {
        /**
         * @var $renderer TemplateRendererInterface
         */
        $renderer = $this->serviceLocator->get(TemplateRendererInterface::class);
        return new HtmlResponse($renderer->render($template, $data));
    }

    protected function getOriginUrl()
    {
        $accessControlAllowOrigin = getenv('AccessControlAllowOrigin');

        return $accessControlAllowOrigin !== false ? $accessControlAllowOrigin :
            $this->getRequest()->getUri()->getScheme() . '://' . $_SERVER['HTTP_HOST'];
    }
}