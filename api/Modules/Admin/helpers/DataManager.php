<?php

namespace Admin\helpers;

use Admin\layout\Action;
use Admin\layout\ClientAction;
use Admin\layout\DataManagementInterface;
use Admin\layout\Input;
use Admin\layout\Layout;
use Admin\layout\TableRow;
use Db\Where;
use Models\UserModel;
use Opis\Closure\SerializableClosure;

class DataManager {

    private $model;

    /* @var DataManagementInterface */
    private $layout;

    /* @var \Closure */
    private $filteringFn;

    /* @var \Closure */
    private $dataMapperFn;

    /* @var \Closure */
    private $searchFormFn;

    /* @var Where */
    private $where;

    /* @var PageContainer */
    private $container;

    /* @var Action */
    private $paginate_action;

    /* @var Action */
    private $search_action;

    private $filters = [];

    private $order_by = ['id' => 'DESC'];

    private $group_by;

    public function __construct(
        string $model,
        PageContainer $container,
        DataManagementInterface $layout,
        Where $where = null,
        $group_by = null,
        $exclude_deleted = true) {

        $this->model = $model;
        $this->layout = $layout;
        $this->where = Where::and();
        $this->group_by = $group_by;
        $this->container = $container;

        if ($where !== null) {
            $this->where->set($where);
        }

        if ($model::hasDefaultFields() && $exclude_deleted) {
            $this->where->set('deleted_at', Where::OperatorIs, null);
        }

        $this->filteringFn = function (array $filters, Where $where) {
            return $where;
        };

        $self = $this;
        $this->search_action = $this->container->createAction(function (ActionRequest $request) use ($self) {
            $values = [];
            $raw_values = $request->getValues();
            foreach ($raw_values as $k => $v) {
                $k = str_replace('_' . $this->id(), '', $k);
                $values[$k] = $v;
            }

            $params = $request->getParams();
            if (!isset($params['filters'])) {
                $params['filters'] = [];
            }
            $self->setFilters(array_merge($self->getFilters(), $params['filters'], $values));
            $params['filters'] = $self->getFilters();
            if (isset($params['page'])) {
                $params['page'] = 0;
            }
            return $self->getReloadAction($params, $raw_values);
        }, $this->layout->getId());
    }

    public function getReloadAction(array $params, array $values): ClientAction {
        $builder = new LayoutBuilder;
        $builder->push($this->build($params, $values));

        return ClientAction::withParams(ClientAction::ACTION_RELOAD_TABLE, [
            'id' => $this->getLayout()->getId(),
            'layout' => $builder->build()[0],
        ]);
    }

    public function getLayout(): DataManagementInterface {
        return $this->layout;
    }

    public function setOrderBy(array $order_by): DataManager {
        $this->order_by = $order_by;
        return $this;
    }

    public function setGroupBy(array $group_by): DataManager {
        $this->group_by = $group_by;
        return $this;
    }

    // closure params: array $filters, Where $where
    public function setFiltering(\Closure $fn): DataManager {
        $this->filteringFn = $fn;
        return $this;
    }

    public function setDataMapper(\Closure $fn): DataManager {
        $this->dataMapperFn = $fn;

        $wrapper = new SerializableClosure($fn);
        $reflector = $wrapper->getReflector();
        $id = md5($reflector->getCode());

        $self = $this;
        $this->paginate_action = $this->container->createAction(function (ActionRequest $request) use ($self) {
            return $self->getReloadAction($request->getParams(), $request->getValues());
        }, $id);

        return $this;
    }

    private function updateFilters(array $filters) {
        $result = [];
        foreach ($filters as $k => $v) {
            if ($v !== '__unset') {
                $result[$k] = $v;
            }
        }
        return $result;
    }

    public function getWhere(): Where {
        return $this->where;
    }

    public function setWhere(Where $where): self {
        $this->where = $where;
        return $this;
    }

    public function getFilters(): array {
        return $this->filters;
    }

    public function setFilters(array $filters): self {
        $this->filters = $filters;
        return $this;
    }

    public function setSearchForm(\Closure $fn): self {
        $this->searchFormFn = $fn;
        return $this;
    }

    public function build(array $params = [], array $values = []): Layout {
        /* @var \Db\QueryBuilder $builder */
        $builder = $this->model::queryBuilder();

        if (count($this->order_by)) {
            $builder->orderBy($this->order_by);
        }

        if ($this->group_by) {
            $builder->groupBy($this->group_by);
        }

        $limit = 50;

        $filters = isset($params['filters']) ? $params['filters'] : $this->filters;
        $filters = $this->updateFilters($filters);

        $page = isset($params['page']) ? $params['page'] : 0;

        $filtering_params = [];
        $where = $this->filteringFn->call($this, $filters, $this->where);
        if (is_array($where)) {
            $filtering_params = $where[1];
            $where = $where[0];

            if ($where instanceof Where === false) {
                throw new \LogicException('check returned values in filtering func');
            }
        }
        $ret = $builder->where($where)->paginate($page, $limit);

        $data = $this->dataMapperFn->call($this->container, $ret->getItems(), $filtering_params);
        foreach ($data as $k => $v) {
            if ($v instanceof TableRow) {
                continue;
            } else {
                $data[$k] = TableRow::withParams(...$v);
            }
        }

        $params['filters'] = $filters;

        /* @var DataManagementInterface $layout */
        $this->layout->setTotalCount($ret->getTotal());
        $this->layout->addItem(...array_values($data));
        $this->layout->setPerPage($limit);
        $this->layout->setParams($params);
        $this->layout->setAction($this->paginate_action);

        $search_form = $this->searchFormFn ? $this->searchFormFn->call($this->container) : false;

        if ($search_form) {
            $values = [];
            $search_form_prepared = [];
            /* @var Layout $item */
            foreach ($search_form as $item) {
                $name = $item->getName();
                $dom_name = $name . '_' . $this->container->id();
                $values[$name] = $dom_name;
                $item->setName($dom_name);
                $search_form_prepared[] = $item->serialize();
            }
            $this->search_action->setParams($params);
            $this->layout->setSearch($search_form_prepared, $this->search_action);
        }

        return $this->layout;
    }

    public function setUserFilters(string $user_field = 'user_id'): DataManager {
        return $this
            ->setSearchForm(function() {
                return [
                    Input::withParams('user', 'Enter user'),
                ];
            })
            ->setFiltering(function(array $filters, Where $where) use ($user_field) {
                $user = $filters['user'] ?? null;

                if ($user) {
                    $user = trim($user);
                    $users = UserModel::select(
                        Where::and()
                            ->set(Where::equal('platform', PLATFORM_FINDIRI))
                            ->set(
                                Where::or()
                                    ->set(Where::equal('id', $user))
                                    ->set('login', Where::OperatorLike, "%$user%")
                                    ->set('email', Where::OperatorLike, "%$user%")
                                    ->set("CONCAT(first_name, ' ', last_name)", Where::OperatorLike, "%$user%")
                            )
                    );
                    $where->set(Where::in($user_field, $users->column('id')));
                }

                return $where;
            });
    }

    public static function applyUserFilters($filters, Where $where, array $user_fields = ['user_id'], string $platform = PLATFORM_FINDIRI): Where {
        if (isset($filters['user'])) {
            $user = trim($filters['user']);
            if ($user == '') {
                return $where;
            }
            $users = UserModel::select(
                Where::and()
                    ->set(Where::equal('platform', $platform))
                    ->set(
                        Where::or()
                            ->set(Where::equal('id', $user))
                            ->set('login', Where::OperatorLike, "%$user%")
                            ->set('email', Where::OperatorLike, "%$user%")
                            ->set("CONCAT(first_name, ' ', last_name)", Where::OperatorLike, "%$user%")
                    )
            );
            $user_where = Where::or();
            foreach ($user_fields as $user_field) {
                $user_ids = $users->column('id');
                if (!empty($user_ids)) {
                    $user_where->set(Where::in($user_field, $user_ids));
                } else {
                    $user_where->set(Where::equal($user_field, $user));
                }
            }

            $where->set($user_where);
        }

        return $where;
    }

    public static function applyDateFilters($filters, Where $where): Where {
        if (isset($filters['date_from'])) {
            $date = \DateTime::createFromFormat('d/m/Y', trim($filters['date_from']));
            if ($date) {
                $date->setTime(0,0);
                $where->set('created_at_timestamp', Where::OperatorGreaterEq, $date->getTimestamp());
            }
        }
        if (isset($filters['date_to'])) {
            $date = \DateTime::createFromFormat('d/m/Y', trim($filters['date_to']));
            if ($date) {
                $date->setTime(0,0);
                $where->set('created_at_timestamp', Where::OperatorLowerEq, $date->modify('+1 days')->getTimestamp());
            }
        }
        return $where;
    }
}
