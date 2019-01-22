# joopschilder/php-async
## Introduction
This package provides functions to run callables asynchronously in PHP. Return values are shared via System-V shared memory.<br/>
To use this package, you'll need PHP >= 7.0.0 with `ext-sysvshm` and `ext-pcntl`.<br/>
You should consider the state of this package to be <i>experimental</i>.<br/><br/>
<b>Note:</b> This package should not be used in a CGI environment. 
The key that is used to access the block of shared memory is created based on the inode information of one of the source files.
This means that, whenever multiple instances (processes) from the same project source are created, they will try to use the same block of memory and collisions will occur.
I might swap the `ftok()` call for a random string generator somewhere down the road.<br/><br/>
<b>Note:</b> It is possible (but discouraged) to change the amount of available shared memory. 
If you wish to do so, it's as simple as calling either `Runtime::_setSharedMemorySizeMB(<amount of MB>);` or `Runtime::_setSharedMemorySizeB(<amount of bytes>);`.<br/>
If you want to use 32MB for example, call `Runtime::_setSharedMemorySizeMB(32);`.<br/>
Be sure to make this call before using any of the asynchronous functionalities.
## Installation
This package is available on <a href="https://packagist.org/packages/joopschilder/php-async">Packagist</a>
and can be installed using <a href="https://getcomposer.org/">Composer</a>:<br/>
```bash
$ composer require joopschilder/async-php
```
It's also possible to manually add it to your `composer.json`:
```json
{
    "require": {
        "joopschilder/php-async": "dev-master"
    }
}
``` 

## Usage
#### Functions
The library exposes three functions in the global namespace that provide indirect access to the class `Asynchronous`:
* `async(callable $function, ...$parameters)` to run something asynchronously, giving back a `Promise`;
* `async_wait_all()` to wait for all currently running jobs to finish;
* `async_reap_zombies()` to clean up any zombie processes during runtime if any exist;

#### Promises
Whenever you call `async(...)`, a `Promise` instance is returned.<br/>
A `Promise` is considered to be resolved when the function it belongs to returned a value or finished execution.
To block execution until a promise is resolved, simply call the `resolve()` method on the promise.
It's possible to check whether the promise has been resolved in a non-blocking way by calling the `isResolved()` method.<br/>
You can actually return anything that is serializable in PHP: objects, arrays, strings, you name it.
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

$promise = async(function() {
    sleep(random_int(1, 5));
    return getmypid();
});

// ... do some other work

$promise->resolve();
$pid = $promise->getValue();
```
The shutdown handler and destructors should take care of the cleanup.<br/>
#### Asynchronous curl requests
... though you should probably look into curl multi handles for this: <a href="http://php.net/manual/en/function.curl-multi-init.php">curl_multi_init()</a>.

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

// Create the body for the process...
$process = function(string $url) {
    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($handle);
    curl_close($handle);
    file_put_contents(uniqid('download_'), $response);
};

// Define some urls we want to download...
$urls = [
    'example.com',
    'example2.com',
    'some.other.domain'
];

// And there we go.
foreach($urls as $url)
    async($process, $url);
```
That's all there is to it.

## Tips
If you're on a UNIX system, you can make use of the tools `ipcs` and `ipcrm` to monitor and manage the shared memory blocks.<br/>
To track what's happening in real time, I like to use:<br/>
```bash
$ watch -n 1 "ipcs -m --human && ipcs -m -p && ipcs -m -t && ipcs -m -u"
```
<br/>To clean all 'unused' shared memory blocks (they might remain resident in RAM if your program terminated unexpectedly):<br/>
```bash
$ ipcrm -a
``` 

## What's next?
- Improving stability
- Add an explaining diagram