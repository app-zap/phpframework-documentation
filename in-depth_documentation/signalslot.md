# Signal/Slot

## What is it?

Signal/Slot is an important mechanism to extend or modify the behaviour of the PHPFramework. It follows the [observer pattern](http://en.wikipedia.org/wiki/Observer_pattern) and allows you to jump in at certain points of the framework and run your own code.

It works like this: At a particular place in the framework code a **signal** is sent (emitted). On the other sides **slots** can listen for that signal and get called whenever it is emitted. Normally the signal also contains parameters which can be used by the slot.

If you're familiar with Javascript, this is comparable to the Event model there.

## How to use?

Search the code for calls to `SignalSlotDispatcher::emitSignal()` to find the available signals. To listen to a certain signal you need to register a slot using `SignalSlotDispatcher::registerSlot()`. Most likely you want to do that in your *PluginLoader*.

### On parameters
Notice that the slot should have as many parameters as the emitSignal call - minus 1 (the `$signalName`). So if emitSignal is called with 3 parameters, your slot should have 2.

The first signal parameter (after `$signalName` is always passed by reference).

## Example

The [PlaceImg](https://github.com/app-zap/PHPFrameworkPlaceImg) package is a simple example of how to use Signal/Slot. In its [PluginLoader](https://github.com/app-zap/PHPFrameworkPlaceImg/blob/develop/classes/PluginLoader.php) it uses the `\AppZap\PHPFramework\Mvc\AbstractController::SIGNAL_INIT_RESPONSE` signal to register 2 twig functions. So by installing the package you'll instantly have those twig functions available with zero extra code or configuration.

## List of available signals

* `\AppZap\PHPFramework\Mvc\AbstractController::SIGNAL_INIT_REQUEST`
* `\AppZap\PHPFramework\Mvc\AbstractController::SIGNAL_INIT_RESPONSE`
* `\AppZap\PHPFramework\Mvc\Dispatcher::SIGNAL_OUTPUT_READY`

## The signal I need is not there

You're welcome to open an [issue](https://github.com/app-zap/PHPFramework/issues). Please provide the exact position and a little description of your use case.
