# DDD: Collections

In PHPFramework model objects are not collected in an array, but in a Collection. Collections are objects that contain an arbitrary number of model objects. They have most of the properties an array has (like [traversable](http://php.net/manual/de/class.iterator.php) and [countable](http://php.net/manual/en/class.countable.php)).

## GenericModelCollection

The default collection class which you will get back after requesting models from a repository.

It has useful methods to operate on its containing models:

`\AppZap\PHPFramework\Domain\Collection\GenericModelCollection`:

| method | description |
| ------ | ----------- |
| `add(AbstractModel $model)` | Adds a model to the collection. *Notice that the objects will be stored by reference (PHP's standard behaviour on passed objects). If you alter your object after storing it in the collection, the stored object is also altered (it is the same object)*. |
| `remove_item(AbstractModel $model)` | If `$model` is present in the collection, it will be removed from it. |
| `removeItems(AbstractModelCollection $itemsToRemove)` | Removes the given set of items from the collection |
| `get_by_id($id)` | Returns the model with the given `$id` or `NULL` if it wasn't found. |
| `count()` | Returns the amount of contained models |
| `current()`, `next()`, `key()`, `rewind()`, `valid()` | Methods to implement [Iterator](http://php.net/manual/en/class.iterator.php) |


