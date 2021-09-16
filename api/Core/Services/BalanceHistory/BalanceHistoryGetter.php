<?php


namespace Core\Services\BalanceHistory;


use Core\Exceptions\BalanceHistory\BalanceHistoryException;
use Db\Model\ModelSet;
use Db\Pagination\Paginator;
use Db\Pagination\PaginatorById;
use Db\Where;
use Models\UserBalanceHistoryModel;

class BalanceHistoryGetter {

    private $from_or_to_id = null;
    private $from_or_to_type = null;

    private $user_ids = [];

    private $currencies = [];
    private $operations = [];
    private $object_ids = [];
    private $order_by = 'DESC';

    /** @var Where */
    private $where = null;

    public function setFromOrToId(int $id): self {
        $this->from_or_to_id = $id;

        return $this;
    }

    public function setWhere(Where $where): self {
        $this->where = $where;
        return $this;
    }

    public function setFromOrToType(int $type): self {
        $this->from_or_to_type = $type;

        return $this;
    }

    public function setFromOrTo($from_or_to): self {
        $type = UserBalanceHistoryModel::getTypeByObject($from_or_to);
        $this->from_or_to_id = $from_or_to->id;
        $this->from_or_to_type = $type;
        return $this;
    }

    public function setUsersIds(array $ids) {
        $this->user_ids = $ids;

        return $this;
    }

    public function setObjectIds(array $ids) {
        $this->object_ids = $ids;

        return $this;
    }

    public function setCurrencies(array $currencies): self {
        $this->currencies = $currencies;

        return $this;
    }

    public function setOperations(array $operations): self {
        $this->operations = $operations;
        return $this;
    }

    private function buildWhere(): Where {
        $where = Where::and();

        if (!is_null($this->from_or_to_type) && !is_null($this->from_or_to_id)) {
            $where->set(
                Where::or()
                    ->set(
                        Where::and()
                            ->set(Where::equal('from_type', $this->from_or_to_type))
                            ->set(Where::equal('from_id', $this->from_or_to_id))
                    )
                    ->set(
                        Where::and()
                            ->set(Where::equal('to_type', $this->from_or_to_type))
                            ->set(Where::equal('to_id', $this->from_or_to_id))
                    )
            );
        }

        if (!empty($this->operations)) {
            if (count($this->operations) === 1) {
                $where->set(Where::equal('operation', $this->operations[0]));
            } else {
                $where->set(Where::in('operation', $this->operations));
            }
        }

        if (!empty($this->object_ids)) {
            if (count($this->object_ids) === 1) {
                $where->set(Where::equal('object_id', $this->object_ids[0]));
            } else {
                $where->set(Where::in('object_id', $this->object_ids));
            }
        }

        if (!empty($this->user_ids)) {
            if (count($this->user_ids) === 1) {
                $where->set(
                    Where::or()
                        ->set(Where::equal('from_user_id', $this->user_ids[0]))
                        ->set(Where::equal('to_user_id', $this->user_ids[0]))
                );
            } else {
                $where->set(
                    Where::or()
                        ->set(Where::in('from_user_id', $this->user_ids))
                        ->set(Where::in('to_user_id', $this->user_ids))
                );
            }
        }

        if (!empty($this->currencies)) {
            if (count($this->currencies) === 1) {
                $where->set(
                    Where::or()
                        ->set(Where::equal('from_currency', $this->currencies[0]))
                        ->set(Where::equal('to_currency', $this->currencies[0]))
                );
            } else {
                $where->set(
                    Where::or()
                        ->set(Where::in('from_currency', $this->currencies))
                        ->set(Where::in('to_currency', $this->currencies))
                );
            }
        }

        if (!is_null($this->where)) {
            $where->set($this->where);
        }

        return $where;
    }

    public function setOrderBy(string $order): self {
        if (!in_array(strtoupper($order), ['DESC', 'ASC'])) {
            throw new BalanceHistoryException();
        }
        $this->order_by = $order;

        return $this;
    }

    public function get(): ModelSet {

        $result = UserBalanceHistoryModel::queryBuilder()
            ->where($this->buildWhere())
            ->orderBy(['created_at_timestamp' => $this->order_by])
            ->select();

        return UserBalanceHistoryModel::rowsToSet($result);
    }

    public function paginate(?int $start_from, ?int $count): Paginator {
        return UserBalanceHistoryModel::queryBuilder()
            ->where($this->buildWhere())
            ->orderBy(['created_at_timestamp' => $this->order_by])
            ->paginate($start_from, $count);
    }

    public function paginateById(?int $start_from, ?int $count): PaginatorById {
        return UserBalanceHistoryModel::queryBuilder()
            ->where($this->buildWhere())
            ->paginateById($start_from, $count);
    }
}
