<?php

namespace Engine\Parser;

use Engine\Debugger\Traceback;
use Engine\Parser\Exception\InvalidParamException;

class Parser {
    private $input = null;
    private $rawInput = null;
    private $url_parameters = [];
    private $parameters = [];
    private $is_admin_parser = false;
    private $admin_values = [];

    /**
     * Parser constructor.
     *
     * @param array $parameters
     * @param array $url_parameters
     */
    function __construct(array $parameters, array $url_parameters = []) {
        $this->parameters = $parameters;
        $this->url_parameters = $url_parameters;
    }

    /**
     * @param string $name
     *
     * @return mixed|null
     */
    private function urlParameter(string $name) {
       return isset($this->url_parameters[$name]) ? $this->url_parameters[$name] : null;
    }


    private function parseInput() {
        if ($this->input === null) {
            $input = file_get_contents('php://input');
            $this->rawInput = $input;
            $json = json_decode($input, true);
            if ($json) {
                $this->input = $json;
            } else {
                $this->input = [];
                parse_str(utf8_decode(urldecode($input)), $this->input);
            }
        }
    }
    /**
     * @param string $field
     *
     * @return mixed|null
     */
    private function getFromInput(string $field) {
        $this->parseInput();
        return isset($this->input[$field]) ? $this->input[$field] : null;
    }

    /**
     * @param string $field
     *
     * @return mixed|string|null
     */
    private function getField(string $field) {
        $value = null;

        if ($this->isAdminParser()) {
            return isset($this->admin_values[$field]) ? $this->admin_values[$field] : null;
        }

        if (isset($_REQUEST[$field])) {
            $value = $_REQUEST[$field];
        }

        if ($value === null) {
            $value = $this->getFromInput($field);
        }

        if ($value === null) {
            $value = $this->urlParameter($field);
        }

        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }
        $value = addslashes($value);

        return $value;
    }

    /**
     * @param string $field
     * @param array $filters
     *
     * @return float|int|mixed|string|null
     * @throws InvalidParamException
     */
    public function get(string $field, array $filters = []) {
        $normalized_filters = [];
        foreach ($filters as $k => $v) {
            if (is_numeric($k)) {
                $normalized_filters[$v] = 1;
            } else {
                $normalized_filters[$k] = $v;
            }
        }

        $priority_filters = array_intersect(
            ['required', 'default', 'int', 'double', 'positive'],
            array_keys($normalized_filters));
        $filters = array_merge(array_flip($priority_filters), $normalized_filters);

        $value = $this->getField($field);
        if ($value !== null && !isset($filters['skip_trim']) && !isset($filters['json']) && !is_array($value)) {
            $value = trim($value);
        }

        foreach ($filters as $filter => $settings) {
            switch ($filter) {
                case 'required':
                    if ($value === null || $value === '') {
                        throw new InvalidParamException(
                            lang(
                                'field_param_is_required',
                                ['field' => $field]
                            )
                        );
                    }
                    break;
                case 'password':
                    $uppercase = preg_match('@[A-Z]@', $value);
                    $lowercase = preg_match('@[a-z]@', $value);
                    $number = preg_match('@[0-9]@', $value);
                    $special_symbol = preg_match('/[\'\/~`\!@#\$%\^&\*\(\)_\-\+=\{\}\[\]\|;:"\<\>,\.\?\\\]/', $value);
                    if (!$uppercase) {
                        throw new InvalidParamException(
                            lang(
                                'field_password_not_have_uppercase',
                                ['field' => $field]
                            )
                        );
                    }

                    if (!$lowercase) {
                        throw new InvalidParamException(
                            lang(
                                'field_password_not_have_lowercase',
                                ['field' => $field]
                            )
                        );
                    }

                    if (!$number) {
                        throw new InvalidParamException(
                            lang(
                                'field_password_not_have_number',
                                ['field' => $field]
                            )
                        );
                    }

                    if (!$special_symbol) {
                        throw new InvalidParamException(
                            lang(
                                'field_password_not_have_special_symbol',
                                ['field' => $field]
                            )
                        );
                    }

                    if (strlen($value) < 8) {
                        throw new InvalidParamException(
                            lang(
                                'field_password_incorrect_length',
                                ['field' => $field]
                            )
                        );
                    }
                    break;
                case 'default':
                    if ($value === null) {
                        $value = $settings;
                    }
                    break;
                case 'maxLen':
                    if ($value !== null && mb_strlen($value) > $settings) {
                        throw new InvalidParamException(
                            lang(
                                'field_max_len',
                                ['field' => $field, 'max_len' => $settings]
                            )
                        );
                    }
                    break;
                case 'minLen':
                    if ($value !== null && mb_strlen($value) < $settings) {
                        throw new InvalidParamException(
                            lang(
                                'field_min_len',
                                ['field' => $field, 'min_len' => $settings]
                            )
                        );
                    }
                    break;
                case 'len':
                    if ($value !== null && mb_strlen($value) !== $settings) {
                        throw new InvalidParamException("length for param {$field} must be {$settings}");
                    }
                    break;
                case 'oneOfLen':
                    if ($value !== null && !in_array(mb_strlen($value), $settings)) {
                        $settings = implode(', ', $settings);
                        throw new InvalidParamException("length for param {$field} must be {$settings}");
                    }
                    break;
                case 'int':
                    if ($value !== null) {
                        if (!is_numeric($value)) {
                            throw new InvalidParamException(
                                lang(
                                    'field_int',
                                    ['field' => $field]
                                )
                            );
                        }
                        $value = (int)$value;
                    }
                    break;
                case 'bool':
                    if ($value !== null) {
                        if (!in_array($value, [0, 1])) {
                            throw new InvalidParamException(
                                lang(
                                    'field_bool',
                                    ['field' => $field]
                                )
                            );
                        }
                        $value = (bool)$value;
                    }
                    break;
                case 'double':
                    if ($value !== null) {
                        if (!is_numeric($value)) {
                            throw new InvalidParamException(
                                lang(
                                    'field_double',
                                    ['field' => $field]
                                )
                            );
                        }
                        $value = (double)$value;
                    }
                    break;
                case 'positive':
                    if ($value !== null && (!is_numeric($value) || $value < 0)) {
                        throw new InvalidParamException(
                            lang(
                                'field_positive',
                                ['field' => $field]
                            )
                        );
                    }
                    break;
                case 'email':
                    if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        throw new InvalidParamException(
                            lang(
                                'field_email',
                                ['field' => $field]
                            )
                        );
                    }
                    break;
                case 'lowercase':
                    if ($value !== null) {
                        $value = mb_strtolower($value);
                    }
                    break;
                case 'uppercase':
                    if ($value !== null) {
                        $value = mb_strtoupper($value);
                    }
                    break;
                case 'oneOf':
                    if (!in_array($value, $settings, !is_numeric($value))) {
                        throw new InvalidParamException(
                            lang(
                                'field_one_of',
                                ['field' => $field, 'variants' => implode(', ', $settings)]
                            )
                        );
                    }
                    break;
                case 'max':
                    if ($value > $settings) {
                        throw new InvalidParamException(
                            lang(
                                'field_max',
                                ['field' => $field, 'max' => $settings]
                            )
                        );
                    }
                    break;
                case 'min':
                    if ($value < $settings) {
                        throw new InvalidParamException(
                            lang(
                                'field_min',
                                ['field' => $field, 'min' => $settings]
                            )
                        );
                    }
                    break;
                case 'json':
                    if (is_string($value)) {
                        $value = json_decode(stripslashes($value), true);
                    }
                    if ($value === null) {
                        throw new InvalidParamException("param {$field} must be json");
                    }
                    break;
                case 'array':
                    if (!is_array($value)) {
                        throw new InvalidParamException("param {$field} must be array");
                    }

                    $value = $this->addSlashesToArray($value);
                    break;
                case 'username':
                    if ($value && !preg_match('/^([a-z\-]{2,20}+)$/i', $value)) {
                        throw new InvalidParamException(
                            lang(
                                'field_username',
                                ['field' => $field]
                            )
                        );
                    }
                    break;
            }
        }

        return $value;
    }

    public function getValues(): array {
        $result = [];
        foreach ($this->parameters as $parameter => $filters) {
            $result[$parameter] = $this->get($parameter, $filters);
        }
        return $result;
    }

    private function addSlashesToArray(array $array) :array {
        $arr = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $arr[$key] = $this->addSlashesToArray($value);
            } else {
                $arr[$key] = addslashes($value);
            }
        }

        return $arr;
    }

    public function getRawInput() {
        $this->parseInput();
        return $this->rawInput;
    }

    public function isAdminParser(): bool {
        return $this->is_admin_parser;
    }

    public function setIsAdminparser(bool $is_admin_parser): Parser {
        $this->is_admin_parser = $is_admin_parser;
        return $this;
    }

    public function getAdminValues(): array {
        return $this->admin_values;
    }

    public function setAdminValues(array $admin_values): Parser {
        $this->admin_values = $admin_values;
        return $this;
    }
}
