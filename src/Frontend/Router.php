<?php

namespace JVVM\Frontend;

use Exception;
use JVVM\Frontend\Request;
use JVVM\Utils\Exceptions\BadURI;
use JVVM\Utils\Exceptions\MalformedRoute;
use JVVM\Utils\URI;

use const JVVM\Utils\HTTP_METHODS;

enum ROUTER_ROUTE_VARS: int {
    case METHOD =   0;
    case URI =      1;
    case CALLBACK = 2;
    case ARGS =     3;
    case QS =       4;
};

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
            ROUTER_ROUTE_VARS::METHOD->value =>     $method,
            ROUTER_ROUTE_VARS::URI->value =>        $uri,
            ROUTER_ROUTE_VARS::CALLBACK->value =>   $callback,
            ROUTER_ROUTE_VARS::ARGS->value =>       $args,
            ROUTER_ROUTE_VARS::QS->value =>         $query_string
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
        var_dump($body);
        return json_decode($body);
    }

    function get_method() {
        return $this->method;
    }

    private function get_filter_name_type($filter) {
        $i = 1;
        while($filter[$i] !== '}') { $i++; }
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

        /* filter out non-matching http method */
        $routes = array_filter(
            $this->routes,
            fn($route) => $route[ROUTER_ROUTE_VARS::METHOD->value] & $uri->get_method()
        );

        foreach($routes as $route) {
            $found = true;
            $vars = [];
    
            /* try to match uri, build var array on the way */
            foreach($uri as $p) {
                if (!$route[ROUTER_ROUTE_VARS::URI->value]->valid()) {
                    $found = false;
                    break;
                }
                $m = $route[ROUTER_ROUTE_VARS::URI->value]->current();
                if ($m[0] === '{') {
                    $this->apply_filter($p, $m, $vars, $args);
                } else {
                    if ($p !== $m) {
                        $found = false;
                        break;
                    }
                }
                $route[ROUTER_ROUTE_VARS::URI->value]->next();
            }
            
            /* if we finish url but route goes further, we are not in a valid
             * uri
             */
            if ($found && !$route[ROUTER_ROUTE_VARS::URI->value]->valid()) {
                $function = $route[ROUTER_ROUTE_VARS::CALLBACK->value];
                $args = [...$args, ...$route[ROUTER_ROUTE_VARS::ARGS->value]];

                /* here we filter query string variable according to route 
                 * definition. Nothing is enforced, just it does what it finds 
                 * as best as possible
                 */
                if (!empty($route[ROUTER_ROUTE_VARS::QS->value])) {
                    $qs_filters = $route[ROUTER_ROUTE_VARS::QS->value];
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