# DatabaseMigrator

PHPFramework ships with a DatabaseMigrator which allows you to execute SQL files automatically. The migrator works only one-way at the moment.

## How it works

The migrator must be enabled and a directory must be provided (see the Configuration section for the details).

If it is enabled it is invoked on every call to the framework at an early point of execution (from a Signal in the constructor of the Dispatcher).

It executes numbered `.sql` files in the right order and remembers the last executed number. Each file is only executed once.

The numbers have to increase over time but need not be consecutive. You can start with any number which is greater or equal 1 (0 is not supported, sorry), can ommit numbers or even use timestamps.

Files can either just contain the number as filename (`21.sql`) or also description separated from the number by underscore (`22_user_table.sql`).

All files must be directly in the configured directory. Subdirectories are not supported at the moment.
