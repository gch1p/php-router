# php-router

Just another PHP router. Simple and fast.

## Usage

#### Route syntax

`{}` is for enumeration of predefined strings, like: `{value1,value2,value3}`.

`()` is for regular expressions, like: `([\w\d]+)`.

See examples.

#### Code example

```
$router = new router;

// main page, returned value will be `index`
$router->add('/', 'index');

// for `/test2.html`, returned value will be `test_html id=2`
$router->add('/test(\d).html', 'test_html id=$(1)');

// for `/contacts/`, returned value will be `contacts`
$router->add('{contacts,about}/', '${1}');

// for `/section2/123/action1/`, returned value will be 'section section=section2_123 action=action1'
$router->add('{section1,section2}/(\d+)/{action1,action2}/', 'section section=${1}_$(1) action=${2}');

$route = $router->find($_SERVER['DOCUMENT_URI']);
if ($route === false) {
    // NOT FOUND
}

// parse value
$route = preg_split('/ +/', $route);
$page = $route[0];
$input = [];
if (count($route) > 1) {
    for ($i = 1; $i < count($route); $i++) {
        $var = $route[$i];
        list($k, $v) = explode('=', $var);
        $input[$k] = $v;
    }
}
```

## API

`add($route, $value)` - Add route. `$value` is a string in any form.

`find($route)` - Find route. Returns compiled `$value` or `false` if not found.

`dump()` - Returns internal routes tree (for serialization, e.g. for production usage).

`load($tree)` - Loads routes tree, previously returned by `dump()`.

## License

MIT
