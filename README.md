# joopschilder/php-async
Asynchronous PHP callable processing with return values via SysV shared memory.<br/>
Requires the `php-sysvshm` extension.<br/>
Works with PHP >= 5.3 due to `shm_attach(...)` returning a resource instead of an int.<br/>
<br/>
<b>Note:</b> This project is merely an experiment. It is, however, available on packagist.
If you think your project lacks witchcraft combined with black magic, just add this package to your `composer.json`:
```json
{
    "require": {
        "joopschilder/php-async": "dev-master"
    }
}
```


## Examples

### Promises
You can actually return anything that is serializable in PHP: objects, arrays, strings, you name it.
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

$promise = async(function() {
	sleep(random_int(1, 5));
	return getmypid();
});

// ... some other work

$promise->resolve();
$pid = $promise->getValue();
printf("Me (%d) and %d have worked very hard!\n", getmypid(), $pid);
```
The shutdown handler and destructors should take care of the rest.



### Asynchronous curl requests
... though you should probably look into curl multi handles for this: <a href="http://php.net/manual/en/function.curl-multi-init.php">curl_multi_init() on PHP.net</a>.

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

// Create the body for the process
$process = function(string $url) {
    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($handle);
    curl_close($handle);
    file_put_contents(uniqid('download_'), $response);
};

// Define some urls we want to download
$urls = [
    'example.com',
    'example2.com',
    'some.other.domain'
];

// And away we go!
foreach($urls as $url)
    async($process, $url);
```
That's all there is to it.

## What's next?
- Refactoring
- More functionality (maybe)
- Improving stability
- Add a diagram that explains this witchcraft