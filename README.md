# Twitter API Interface

## Getting Started

Install dependencies via [composer](https://getcomposer.org/):

```bash
$ composer install 
``` 

Create a php file with the following contents:

```php
// Autoload composer files and require the API interface
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/twitterApi.php';

// Create a new instance
$twitter = new Twitter($config = array(
    'consumer_key'      => '',
    'consumer_secret'   => '',
    'user_token'        => '',
    'user_secret'       => '',
    'screen_name'       => ''
));

// Get the 10 latest tweets
// Consult the twitterApi.php file for more methods
$data = $twitter->get(10);

// Output as JSON
header('Content-Type: application/json');
echo json_encode($data);
```


## Changelog
+ 2015/08/05: Composer handles library dependancies an refactoring
+ 2014/12/09: Added search method
+ 2013/06/05: Created
