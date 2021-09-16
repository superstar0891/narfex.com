<?php

namespace Admin\helpers;

use Engine\Parser\Parser;
use Exceptions\InvalidKeyException;

class ActionRequest {

    /** @var array */
    private $values;

    /** @var array */
    private $params;

    /** @var Parser  */
    private $parser;

    public function __construct(array $params, array $values) {
        $this->setParams($params)->setValues($values);

        $this->parser = new Parser([]);
        $this->parser->setIsAdminparser(true)->setAdminValues($this->values);
    }

    public function getParams(): array {
        return $this->params;
    }

    /**
     * @param string $key
     * @return mixed
     * @throws InvalidKeyException
     */
    public function getParam(string $key) {
        if (!isset($this->params[$key])) {
            throw new InvalidKeyException("Key $key does not exist");
        }
        return $this->params[$key];
    }

    public function setParams(array $params): ActionRequest {
        $this->params = $params;
        return $this;
    }

    public function getValues(array $filters = []): array {
        $values = [];

        foreach ($filters as $field => $filter) {
            if (!isset($this->values[$field]) && in_array('required', $filter)) {
                $this->values[$field] = null;
            }
        }

        foreach ($this->values as $field => $value) {
            $values[$field] = !isset($filters[$field]) ?
                $value :
                $this->getValue($field, $filters[$field]);
        }

        return $values;
    }

    public function getValue(string $field, array $filters = []) {
        return $this->parser->get($field, $filters);
    }

    public function setValues($values): ActionRequest {
        $this->values = $values;
        return $this;
    }

}
