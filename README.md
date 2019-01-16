# php-async
Asynchronous PHP callable processing with return values via SysV shared memory.<br/>
Requires the `php-sysvshm` extension.<br/>
Works with PHP from version 5.3 upwards due to `shm_attach(...)` returning a resource instead of an int.
