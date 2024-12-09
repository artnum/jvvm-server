<?php

namespace JVVM\Frontend;

use Exception;
use JVVM\Frontend\Request;
use JVVM\Utils\Exceptions\BadURI;
use JVVM\Utils\Exceptions\MalformedRoute;
use JVVM\Utils\RouterMethod;
use JVVM\Utils\URI;

class RouterFilters {
    static function filter_boolean(mixed $value):?bool {
        $value = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if (is_null($value)) {
            return NULL;
        }
        return boolval($value);
    }

    static function filter_integer(mixed $value):?int {
        $value = filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        if (is_null($value)) {
            return NULL;
        }
        return intval(filter_var($value, FILTER_SANITIZE_NUMBER_INT, 0));
    }

    static function filter_float(mixed $value):float {
        $value = filter_var(
            FILTER_VALIDATE_FLOAT,
            FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_SCIENTIFIC | FILTER_NULL_ON_FAILURE
        );
        if (is_null($value)) {
            return NULL;
        }
        $value = filter_var(
            FILTER_SANITIZE_NUMBER_FLOAT,
            FILTER_FLAG_ALLOW_FRACTION
                | FILTER_FLAG_ALLOW_SCIENTIFIC
                | FILTER_NULL_ON_FAILURE
        );
        return floatval($value);
    }

    static function filter_alphanum(mixed $value):string {
        $value = strval($value);
        if (!ctype_alnum($value)) {
            return NULL;
        }
        return $value;
    }

    static function filter_mail(mixed $value):string {
        $value = filter_var($value, FILTER_VALIDATE_EMAIL, FILTER_NULL_ON_FAILURE);
        if (is_null($value)) {
            return NULL;
        }
        $value = filter_var($value, FILTER_SANITIZE_EMAIL);
        return strval($value);
    }
    
    static function filter_string(mixed $value):string {
        return strval($value);
    }
}

class Router {
    protected array $routes;
    protected array $resources;
    protected int $method;
    protected bool $body_read;
    protected array $filters;

    const URI_PARAM = 'uri';
    const METHOD_PARAM = 'method';
    const CALLBACK_PARAM = 'callback';
    const ARGS_PARAM = 'args';
    const QS_PARAM = 'query_string';
    const URI_VAR_START = '{';
    const URI_VAR_STOP = '}';

    function __construct() {
        $this->body_read = false;
        $this->routes = [];
        $this->filters = [
            'boolean' =>    ['JVVM\Frontend\RouterFilters', 'filter_boolean'],
            'integer' =>    ['JVVM\Frontend\RouterFilters', 'filter_integer'],
            'any' =>        ['JVVM\Frontend\RouterFilters', 'filter_string'],
            'float' =>      ['JVVM\Frontend\RouterFilters', 'filter_float'],
            'alphanum' =>   ['JVVM\Frontend\RouterFilters', 'filter_alphanum'],
            'mail' =>       ['JVVM\Frontend\RouterFilters', 'filter_mail'],
            'string' =>     ['JVVM\Frontend\RouterFilters', 'filter_string']
        ];

        $this->resources = [];
    }
    
    function set_filter(string $name, callable $callback) {
        $this->filters[$name] = $callback;
    }

    function add(
            int $method,
            URI $uri,
            callable $callback,
            array $args = [],
            array $query_string = []) {
        $id = $uri->get_id($method);
        $this->routes[$id] = [
            self::METHOD_PARAM =>   $method,
            self::URI_PARAM =>      $uri,
            self::CALLBACK_PARAM => $callback,
            self::ARGS_PARAM =>     $args,
            self::QS_PARAM =>       $query_string
        ];
    }

    function add_resource(string $name, mixed $resources) {
        $this->resources[$name] = $resources;
    }

    function get_resource(string $name) {
        if (!isset($this->resources[$name])) { return NULL; }
        return $this->resources[$name];
    }

    function get_body() {
        if ($this->body_read) {
            throw new Exception('Cannot double read body');
        }
        $this->body_read = true;
        return file_get_contents('php://input');
    }

    function get_json_body() {
        $body = $this->get_body();
        return json_decode($body);
    }

    function get_method() {
        return $this->method;
    }

    private function get_filter_name_type($filter) {
        $i = 1;
        while($filter[$i] !== self::URI_VAR_STOP) { $i++; }
        if ($i === 1) { return [null, null]; }
        $parts = explode(':', substr($filter, 1, $i -1), 2);
        if (count($parts) !== 2) { return [null, null]; }
        return [$parts[1], $parts[0]];
   
    }

    private function apply_filter(
            string $url_parameter,
            string $route_parameter,
            array &$vars
    ) {
        list ($name, $type) = $this->get_filter_name_type($route_parameter);
        if (is_null($name)) { throw new BadURI(); }
        if (!isset($this->filters[$type])) { throw new MalformedRoute(); }
        $value = call_user_func($this->filters[$type], $url_parameter);
        if (is_null($value)) { throw new BadURI(); }
        $vars[$name] = $value;
    }

    function run(URI $uri) {
        $vars = [];
        $function = NULL;
        $args = [];

        foreach($this->routes as $route) {
            $found = true;
            $vars = [];
            if (
                !($route[self::METHOD_PARAM] & $uri->get_method())
                && !RouterMethod::is_router_method( $route[self::METHOD_PARAM])
            ) { 
                continue;
            }
            /* try to match uri, build var array on the way */
            foreach($uri as $p) {
                
                if (
                        !isset($route[self::URI_PARAM])
                        || !$route[self::URI_PARAM]->valid()
                ) {
                    $found = false;
                    break;
                }
                $m = $route[self::URI_PARAM]->current();
                if ($m[0] === self::URI_VAR_START) {
                    if ($m === '{*}') { break; }
                    $this->apply_filter($p, $m, $vars, $args);
                } else {
                    if ($p !== $m) {
                        $found = false;
                        break;
                    }
                }
                $route[self::URI_PARAM]->next();
            }
            
            if (
                $found
                && RouterMethod::is_router_method($route[self::METHOD_PARAM])
                && $route[self::URI_PARAM]->valid()
            ) {
                while($route[self::URI_PARAM]->current() === '{*}') {
                    $route[self::URI_PARAM]->next();
                    if (!$route[self::URI_PARAM]->valid()) { break; }
                }
            }

            /* if we finish url but route goes further, we are not in a valid
             * uri
             */
            if ($found && !$route[self::URI_PARAM]->valid()) {
                if (RouterMethod::is_router_method($route[self::METHOD_PARAM])) {
                    $new_uri = call_user_func($route[self::CALLBACK_PARAM]);
                    $new_uri->copy_from($uri, $route[self::URI_PARAM]);
                    return $this->run($new_uri);
                }
                $function = $route[self::CALLBACK_PARAM];
                $args = [...$args, ...$route[self::ARGS_PARAM]];

                /* here we filter query string variable according to route 
                 * definition. Nothing is enforced, just it does what it finds 
                 * as best as possible
                 */
                if (!empty($route[self::QS_PARAM])) {
                    $qs_filters = $route[self::QS_PARAM];
                    foreach($uri->get_query_string() as $name => $value) {
                        foreach($qs_filters as $filter) {
                            list ($filter_name, $type) = $this->get_filter_name_type($filter);
                            if (is_null($filter_name)) { continue; }
                            if ($filter_name !== $name) { continue; }
                            if (!isset($this->filters[$type])) { continue; }
                            if (is_array($value)) {
                                $values = [];
                                foreach($value as $v) {
                                    $_v = call_user_func($this->filters[$type], $v);
                                    if (is_null($_v)) { $values[] = $v; }
                                    else { $values[] = $_v; }
                                }
                                $uri->set_qs_variable($name, $values);
                                break;
                            }
                                
                            $value = call_user_func($this->filters[$type], $value);
                            if (is_null($value)) { break; }
                            $uri->set_qs_variable($name, $value);
                            break;
                        }
                    }
                }
                break;
            }
        }

        if ($found && !is_null($function)) {
            return call_user_func($function, new Request($uri), $vars, $args);
        }
    
        return NULL;
    }

}