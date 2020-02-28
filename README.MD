# LeadVertex process plugin component
`\Leadvertex\Plugin\Components\Process` - component, which designed to store and provide information
about the current state of the process based on the base DB model
[from DB plugin component](https://github.com/leadvertex/plugin-component-db "from DB plugin component")
and accepts process id into itself.

## Usage
For example, you can create new process like this:
```
<?php
use Leadvertex\Plugin\Components\Process\Process;

$process = new Process($id);
$process->save();

$process->initialize(5);
$process->save();
...

$process->handle();
$process->handle();
$process->handle();
$process->save();
...

$process->skip();
$process->save;
...

$process->addError(new Error('Error message', $entityId));
$process->save();
...

$result = $process->finish(true);
$process->save();
```
Note, that you need to use save() method to save actual information to DB.

### Initialize method
Method `initialize()` is intended to indicate the number of entities being processed.\
It accepts a number of entities, to be handled. The number may be null for cases where you cannot determine
its exact value.\
Note, that you must initialize process before you can use its `handle()`, `skip()` and `finish()` methods.\
You can check if the process has already been initialized with `isInitialized()` method.

### Handle method
Method `handle()` is intended to increase the number of successfully processed entities.\
You can get current number of handled entities with `getHandledCount()` method.

### Skip method
Method `skip()` is intended to skip processing of current entities. You can use it in cases, when your entity become
inaccessible or was deleted during processing.\
You can get current number of skipped entities with `getSkippedCount()` method.

### AddError method
Method `addError()` is intended to be used when handle of current entity fails.\
It accepts an instance of `\Leadvertex\Plugin\Components\Process\Components\Error` into itself. You can use it like this:
```
$process->addError(new Error('Entity handle is failed', $entityId));
$process->save();

$errors = $process->getLastErrors();
```
You can get list of 20 last errors with `getLastErrors()` method.

### Terminate method
Method `terminate()` is intended to stop execution of process due to fatal error.\
It accepts an instance of `\Leadvertex\Plugin\Components\Process\Components\Error` into itself,
adds an error in similar to `addError()` method way, marks handle of all remaining entities as failed,
sets result of process execution as `false` and sets `updatedAt` property of model as the current DateTime.\
You can get result of the process execution with `getResult()` method.\
You can use this method like this:
```
$process->terminate(new Error('Fatal error', $entityId));
$process->save();

$errors = $process->getLastErrors();
```

### Finish method
Method `finish()` is intended to be used when your process finished all its work.\
It accepts a result into itself which can be one of the 3 following types:
1. `bool` - is used when you need to return final status of your process execution.
2. `int` - is used when you need to return number of handled entities.
3. `string` - is used when you need to return URI in result.

Also it sets result of process execution as the accepted value
and sets `updatedAt` property of model as the current DateTime.

### JsonSerializable process
Process implements a `jsonSerialize()` method, which means you can get process info as a serialized json by simply using
json_encode to process instance.
For example, you can use it like this:
```
$response = json_encode($process);
```