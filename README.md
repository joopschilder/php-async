# `php-async`

## Installation

This package is available on [Packagist](https://packagist.org/packages/joopschilder/php-async) and can be installed using [Composer](https://getcomposer.org/):
  
```bash
$ composer require joopschilder/async-php
```

It's also possible to manually add it to your `composer.json`:

```json
{
    "require": {
        "joopschilder/php-async": "~1.0"
    }
}
``` 

## What is this?

This package provides functions to run callables asynchronously in PHP. Return values are shared via System-V shared memory.  
To use this package, you need PHP >= 7 with `ext-sysvshm` and `ext-pcntl`.  

You should consider the state of this package to be __highly experimental__.  

___Note:__ This package should not be used in a CGI environment._   
The key that is used to access the block of shared memory is created based on the inode information of one of the source files.  
This means that, whenever multiple instances (processes) from the same project source are created, they will try to use the same block of memory and collisions will occur.
I might swap the `ftok()` call for a multi-instance supporting mechanism later on (feel free to do so yourself).  

___Note:__ It is possible (but discouraged) to change the amount of available shared memory._  
If you wish to do so, it's as simple as calling either `Runtime::setSharedMemorySizeMB(<amount of MB>)` or `Runtime::setSharedMemorySizeB(<amount of bytes>)`.  
If you want to use 32MB for example, call `Runtime::setSharedMemorySizeMB(32)`.<br/>
Be sure to make this call before using any of the asynchronous functionalities.

## What is this not?

This is, as you probably guessed by now, not intended for use in a production environment.  
I'm not saying you _can't_, I'm just saying you _shouldn't_.  

The code is _not_ unit tested. It has been documented throughout though, so feel free to take a look.

## Usage

### Functions

The library exposes three functions in the global namespace that provide indirect access to the `Asynchronous` class:

* `async(callable $function, ...$parameters)` to run something asynchronously, returning a `Promise`;
* `async_wait_all()` to wait for all currently running jobs to finish;
* `async_cleanup()` to clean up any zombie processes during runtime if any exist;

### A `Promise`, you say?

`async(...)` returns an instance of `JoopSchilder\Asynchronous\Promise`.  
A `Promise` is considered to be resolved when the function it belongs to returned a value or finished execution.  
To block execution until a promise is resolved, simply call the `resolve()` method on the promise.  
It's possible to check whether the promise has been resolved in a non-blocking way by calling the `isResolved()` method.  
You can actually return anything that is serializable in PHP: objects, arrays, strings, you name it.

```php
$promise = async(function() {
    sleep(random_int(1, 5));
    return getmypid();
});

// ... do some other work

$promise->resolve();
$pid = $promise->getValue();
```
The shutdown handler and destructors should take care of the cleanup.

### Asynchronous curl requests

The only reason `curl` is used here is to provide an intuitive example.  
If you really wanted to perform concurrent http requests you should look into either [`curl_multi_init`](http://php.net/manual/en/function.curl-multi-init.php) or just use [Guzzle](http://docs.guzzlephp.org/en/stable/).

```php
$job = function(string $url) {
    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($handle);
    curl_close($handle);
    file_put_contents(uniqid('download_'), $response);
};

$urls = ['example.com', 'example2.com', 'some.other.domain'];
foreach($urls as $url) {
    async($job, $url);
}
```
That's all there is to it.

## Tips

If you're using a UNIX system, you can make use of the tools `ipcs` and `ipcrm` to monitor and manage System V shared memory blocks.  
To see what's happening, you can use:

```bash
$ watch -n 1 "ipcs -m --human && ipcs -m -p && ipcs -m -t && ipcs -m -u"
```

To clean all 'unused' shared memory blocks (they might stay in RAM if your program terminated unexpectedly), run

```bash
$ ipcrm -a
``` 

## What's next?
Who really knows?
