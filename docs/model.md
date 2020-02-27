# Model (ORM) library

The Model Library offers a way to handle entities saved in your database defining their models and without worrying
about queries and object mapping.


## Model definition

In order to use the ORM Library, you have to define the model of your entities extending the abstract class
`Nano\Model\Entity` and overriding some public and static field. Available properties are the following:
 
### $table `string`
The table in the database that refers to this entity.

### $primaryKey `string`
The primary key column name; default: `'id'`.\
_Only one primary key is supported._\
Example of SQL definition: `INT UNSIGNED PRIMARY KEY AUTO_INCREMENT`.

### $columns `array`
The column list definition; default: `[]`.

Each item of the list MUST be in the form `$column => $type` where:
 - `$column` is the column name composed only by alphanumeric or underscore characters;
 - `$type` is the data type using the `Entity::TYPE_*` constants.
 
>In this list MUST NOT be inserted:
> - primary key column,
> - relation columns, used as reference to an another entity,
> - `'created'` and `'updated'` datetime columns, used for entity history,
> - `'deleted'` datetime column, used for soft deletion.

Example:
```
$columns = [
    'username'  => Entity::TYPE_STRING,
    'password'  => Entity::TYPE_STRING,
    'lastLogin' => Entity::TYPE_DATETIME
];
```

#### Available column data types

| Constant                | SQL Datatype            | PHP Datatype         |
| ----------------------- | ----------------------- | -------------------- |
| `Entity::TYPE_BOOL`     | `TINYINT(1)`            | `bool`               |
| `Entity::TYPE_DATE`     | `DATE`                  | `\DateTimeImmutable`* |
| `Entity::TYPE_DATETIME` | `DATETIME`              | `\DataTimeImmutable`* |
| `Entity::TYPE_FLOAT`    | `FLOAT`                 | `float`              |
| `Entity::TYPE_JSON`     | `TEXT`                  | `array / object`     |
| `Entity::TYPE_INT`      | `INT`                   | `int`                |
| `Entity::TYPE_STRING`   | `CHAR / VARCHAR / TEXT` | `string`             |
| `Entity::TYPE_TIME`     | `TIME`                  | `\DateTimeImmutable`* |
(*) Unknown fields are set to `0`.


### $relations `array`
The relations definition; default: `[]`.

Every item of this array represent a relation between current entity and a specified binding entity. Each relation
SHOULD be identified uniquely by its index.

A relation is represented by an array with the following items:
 - `name` is the name of the property associated to this relation;
 - `type` is the type if the relation using the `Relation::ONE_TO_ONE`, `Relation::ONE_TO_MANY` or
 `Relation::MANY_TO_MANY` constants;
 - `entity` is the class name of the binding entity;
 - `foreignKey` _\[optional\]_ is the column name if the foreign key, by default, is the value of `'name'` option;
 - `bindingKey` _\[optional\]_ is the column name of the binding key, default is `'id'`;
 - `junctionTable` _\[required for ManyToMany relations\]_ is the name of the junction table.
 - `loading` _\[optional\]_ is the type of loading using `Relation::EAGER` or `Relation::LAZY` constants; default is
 `Relation::EAGER` for OneToOne relations, `Relation::LAZY` otherwise.

In OneToMany relations, `bindingKey` is referred to a column in the external table while `foreignKey` is referred to
the table associated to this entity. In ManyToMany relations, it is supposed that the junction table has two columns
and their names are the values of `foreignKey` and `bindingKey` options respectively. The first column is referred to
the entity table, and the latter one is referred to the external table.

Example of definition:
```
$relations = [
    'user_role' => [
        'name'       => 'role',
        'type'       => Relation::RELATION_ONE_TO_ONE,
        'entity'     => UserRole::class,
        'foreignKey' => 'roleId',
        'bindingKey' => 'id'
    ]
];
```

### $datetime `bool`
Enable datetime functionality; default: `true`.

>For this feature to work, the `updated` and `created` columns of the `DATETIME` type must be defined in the entity
>table.\
>Example of SQL definition: `updated DATETIME NOT NULL, created DATETIME NOT NULL`.


### $softDeletion `bool`
Enable soft deletion functionality; default: `true`.

>For this feature to work, the `deleted` column of the `DATETIME` type must be defined in the entity table.\
>Example of SQL definition: `deleted DATETIME DEFAULT NULL`.

### $readOnly `bool`
Disable automatic setters and entity saving; default: `false`.



## Example

Let's make an example starting from a definition of a simple user entity:
```php
use \Nano\Model\Entity;

class User extends Entity {
    public static $table = 'users';
    public static $columns = [
        'username'           => Entity::TYPE_STRING,
        'password'           => Entity::TYPE_STRING,
        'lastPasswordChange' => Entity::TYPE_DATETIME
    ];
}
```

>It is suggest to use the `@property` tag in DocBlock to define columns and relations of the entity class.
>In this example:
>```php
>/**
> * @property string $id
> * @property string $username
> * @property string $password
> * @property DateTimeImmutable $lastPasswordChange
> * @property DateTimeImmutable $updated
> * @property DateTimeImmutable $created
> * @property DateTimeImmutable|null $deleted
> */
>```

### Entity creation
```php
$user = new User();
$user->username = 'your_name';
$user->password = 'your_password';
$user->lastPasswordChange = new DateTimeImmutable();
$user->save();
```
The ID associated to this entity by the database can be retrieved through `$user->id`;

### Entity retrieving and updating
```php
$user = User::get($id);
$user->password = 'new_password';
$user->lastPasswordChange = new DateTimeImmutable(); // or a string in the format "Y-m-d H:i:s".
$user->save();
```

### Entity deletion
```php
$user->delete();
```
In this case the field `deleted` of the selected entity in the database is updated with the actual datetime. If you
want to physically remove the entity from the database, you should call `$user->delete(true);` or set
`public static $softDeletion = false;` in the model definition.



## API

### Class `Entity`

Implements functionalities for create, read, update and delete entities.\
**Namespace:** `Nano\Model`


#### all() `public` `static`
```
all(): SelectAllBuilder
```
Get a list of entities with optional filters and options.

**Return** [`SelectAllBuilder`](#class-selectallbuilder) Returns the helper for query building.


#### get() `public` `static`
```
get(string $primaryKey): ?self
```
Get a single entity using the value of its primary key.

**Parameters**\
&nbsp;&nbsp;`string $primaryKey` The primary key value.\
**Return** `static|null` Returns an instance of searched entity or NULL if not found.\
**Throws**\
&nbsp;&nbsp;`ModelExecutionException` if an error occurs during the execution of the query.


#### count() `public` `static`
```
count(): CountBuilder
```
Get the number of entities that respect particular conditions.

**Return** [`CountBuilder`](#class-countbuilder) Returns the helper for query building.


#### __construct() `public`
```
__construct(array $data = [])
```
Create an instance of this entity.

**Parameters**\
&nbsp;&nbsp;`array $data` _\[optional\]_ The list of values for entity properties.\
**Throws**\
&nbsp;&nbsp;`NotDefinedPropertyException` if one of the properties given is not defined.\
&nbsp;&nbsp;`InvalidEntityException` for an invalid entity definition.\
&nbsp;&nbsp;`InvalidValueException` if one of the properties given has an invalid value.


#### getMetadata() `public`
```
getMetadata(): EntityMetadata
```
Get metadata for this entity.\
**Return** `EntityMetadata` Returns the entity metadata.


#### isNew() `public`
```
isNew(): bool
```
Determines if this entity is new or is persisted in database.

**Return** `bool`


#### isModified() `public`
```
isModified(): bool
```
Determines if this entity has some updates that are not persisted.

**Return** `bool`


#### isDeleted() `public`
```
isDeleted(): bool
```
Determines if this entity was soft deleted.

**Return** `bool`


#### getId() `public`
```
getId(): ?string
```
Returns the primary key value or NULL if the entity is not persisted.

**Return** `string|null`


#### beforeCreation() `protected`
```
beforeCreation()
```
This method can be overwritten in order to execute some code before the entity creation.


#### afterCreation() `protected`
```
afterCreation()
```
This method can be overwritten in order to execute some code after the entity creation.


#### beforeUpdate() `protected`
```
beforeUpdate()
```
This method can be overwritten in order to execute some code before the entity update.


#### afterUpdate() `protected`
```
afterUpdate()
```
This method can be overwritten in order to execute some code after the entity update.


#### save() `public`
```
save()
```
Update or insert the entity in database.

**Throws**\
&nbsp;&nbsp;`ModelExecutionException` if an error occurs during the execution of the query.


#### beforeDeletion() `protected`
```
beforeDeletion()
```
This method can be overwritten in order to execute some code before the entity deletion.


#### afterDeletion() `protected`
```
afterDeletion()
```
This method can be overwritten in order to execute some code after the entity deletion.


#### delete() `public`
```
delete(bool $hardDelete = false)
```
Delete the entity.

**Parameters**\
&nbsp;&nbsp;`bool $hardDelete` _\[optional\]_ Force hard deletion; default: `false`.\
**Throws**\
&nbsp;&nbsp;`ModelExecutionException` if this entity is not persisted in database.\
&nbsp;&nbsp;`ModelExecutionException` if an error occur during query execution.


#### beforeRestore() `protected`
```
beforeRestore()
```
This method can be overwritten in order to execute some code before the entity restoration.


#### afterRestore() `protected`
```
afterRestore()
```
This method can be overwritten in order to execute some code after the entity restoration.


#### restore() `public` 
```
restore()
```
Restore the entity from soft deletion.

**Throws**\
&nbsp;&nbsp;`ModelExecutionException` if this entity is not soft deleted.\
&nbsp;&nbsp;`ModelExecutionException` if an error occur during query execution.


#### __get() `public`
```
__get(string $name): mixed
```
Get the value of an entity property.
>Note that this method does not return-by-reference, so the value provided is a copy of the actual value.
>In case of an array, the operation `$entity->array[] = $new_item;` produces a warning and doesn't work.
>You should retrieve the array, modify it and then re-assign the value to the property:
>```
>$array = $entity->array;
>$array[] = $new_item;
>$entity->array = $array;
>```

**Parameters**\
&nbsp;&nbsp;`string $name` The name of the property.\
**Return** `mixed` Returns the value of the property.\
**Throws**\
&nbsp;&nbsp;`NotDefinedPropertyException` if the property is not defined or is not set.\
&nbsp;&nbsp;`ModelExecutionException` if an error occur during query execution.


#### __set() `public`
```
__set(string $name, mixed $value)
```
Set the value of an entity property.

**Parameters**\
&nbsp;&nbsp;`string $name` The name of the property.\
&nbsp;&nbsp;`mixed $value` The new value of the property.\
**Throws**\
&nbsp;&nbsp;`NotDefinedPropertyException` if the property is not defined.\
&nbsp;&nbsp;`InvalidValueException` if value is invalid or entity is read-only.


#### __isset() `public`
```
__isset(string $name): bool
```
Determine if an entity property is set and is not `null`.

**Parameters**\
&nbsp;&nbsp;`string $name` The name of the property.\
**Return** `bool` Returns `true` if the property is set and is not `null`, `false` otherwise.


#### __debugInfo() `public`
```
__debugInfo(): array
```
Magic method called by `var_dump()` when dumping an object to get the properties that should be shown.

**Return** `array`


---


### Class `SelectAllBuilder`

Helper class for retrieving a list of entity.\
**Namespace:** `Nano\Model`


#### where() `public`
```
where(string $column, string $operator, $value, int $type = null): self
```
Filter result-set with AND conditions.\
Each condition is concatenated to the others with an `AND` operator.

**Parameters**\
&nbsp;&nbsp;`string $column` The name of the column. The string can contains only alphanumeric or underscore characters.
In addition, it is possible to prepend a table name or an alias to the column name adding a dot between them.
&nbsp;&nbsp;`string $operator` The comparison operator from:
`=, !=, <>, <, >, <=, >=, [NOT] LIKE, [NOT] IN, IS [NOT] NULL`.
&nbsp;&nbsp;`mixed $value` The condition value. For `[NOT] IN` operator this must be an `array`, for `[NOT] IS`
operator this is not considered, otherwise this must be a `scalar`.
&nbsp;&nbsp;`int $type` _\[optional\]_ The data type using the `Types::*` constants; if `null`, the type is evaluated
from `$value`.
**Return** `static` Returns self reference for method chaining.\
**Throws**\
&nbsp;&nbsp;`InvalidValueException` if column name, operator or value is not valid.


#### orWhere() `public`
```
orWhere(array $conditions): self
```
Filter result-set with OR conditions.\
Each condition must be in the form `[$column, $operator, $value]` or `[$column, $operator, $value, $type]`, where:
 - `$column` is the name of the column. The string can contains only alphanumeric or underscore characters.
 In addition, it is possible to prepend a table name or an alias to the column name adding a dot between them.
 - `$operator` is the comparison operator from: `=, !=, <>, >, <, >=, <=, [NOT] LIKE, [NOT] IN, IS [NOT] NULL`.
 - `$value` is the condition value. For `[NOT] IN` operator this must be an `array`, for `[NOT] IS` operator this is
 not considered, otherwise this must be a `scalar`.
 - `$type` is the data type using the `Types::*` constants; if `null`, the type is evaluated from `$value`.
 
**Parameters**\
&nbsp;&nbsp;`array $conditions` The condition list.\
**Return** `static` Returns self reference for method chaining.\
**Throws**\
&nbsp;&nbsp;`InvalidValueException` if the condition list is not valid.


#### orderBy() `public`
```
orderBy(string $column, string $order = Query::SORT_ASC): self
```
Add a sorting rule for result-set.

**Parameters**\
&nbsp;&nbsp;`string $column` The name of the column. The string can contains only alphanumeric or underscore characters.
In addition, it is possible to prepend a table name or alias to the column adding a dot between them.\
&nbsp;&nbsp;`string $order` _\[optional\]_ The sort order using `Query::SORT_*` constants; default: `Query::SORT_ASC`.\
**Return** `static` Returns self reference for method chaining.
**Throws**\
&nbsp;&nbsp;`InvalidValueException` if column name or order is not valid.


#### limit() `public`
```
limit(int $limit, int $offset = 0): self
```
Specify the number of rows to return and to skip.

`$limit = 0` means no limit, `$offset = 0` means no offset.\
**Parameters**\
&nbsp;&nbsp;`int $limit` The limit value.\
&nbsp;&nbsp;`int $offset` _\[optional\]_ The offset value; default: `0`.\
**Return** `static` Returns self reference for method chaining.


#### showDeleted() `public`
```
showDeleted(bool $show = true): self
```
Add soft deleted entities in the result.

**Parameters**
&nbsp;&nbsp;`bool $show` _\[optional\]_ Whether to show soft deleted entities in the result; default: `true`.
**Return** `static` Returns self reference for method chaining.


#### execute() `public`
```
execute(): Entity[]
```
Execute the query and return the list of entities.

**Return** `Entity[]` Returns the list of searched entities.\
**Throws**\
&nbsp;&nbsp;`ModelExecutionException` if an error occur during query execution.


---


### Class `CountBuilder`
Helper class for count entities that respect particular conditions.\
**Namespace:** `Nano\Model`


#### where() `public`
```
where(string $column, string $operator, $value, int $type = null): self
```
Filter result-set with AND conditions.\
Each condition is concatenated to the others with an `AND` operator.

**Parameters**\
&nbsp;&nbsp;`string $column` The name of the column. The string can contains only alphanumeric or underscore characters.
In addition, it is possible to prepend a table name or an alias to the column name adding a dot between them.
&nbsp;&nbsp;`string $operator` The comparison operator from:
`=, !=, <>, <, >, <=, >=, [NOT] LIKE, [NOT] IN, IS [NOT] NULL`.
&nbsp;&nbsp;`mixed $value` The condition value. For `[NOT] IN` operator this must be an `array`, for `[NOT] IS`
operator this is not considered, otherwise this must be a `scalar`.
&nbsp;&nbsp;`int $type` _\[optional\]_ The data type using the `Types::*` constants; if `null`, the type is evaluated
from `$value`.
**Return** `static` Returns self reference for method chaining.\
**Throws**\
&nbsp;&nbsp;`InvalidValueException` if column name, operator or value is not valid.


#### orWhere() `public`
```
orWhere(array $conditions): self
```
Filter result-set with OR conditions.\
Each condition must be in the form `[$column, $operator, $value]` or `[$column, $operator, $value, $type]`, where:
 - `$column` is the name of the column. The string can contains only alphanumeric or underscore characters.
 In addition, it is possible to prepend a table name or an alias to the column name adding a dot between them.
 - `$operator` is the comparison operator from: `=, !=, <>, >, <, >=, <=, [NOT] LIKE, [NOT] IN, IS [NOT] NULL`.
 - `$value` is the condition value. For `[NOT] IN` operator this must be an `array`, for `[NOT] IS` operator this is
 not considered, otherwise this must be a `scalar`.
 - `$type` is the data type using the `Types::*` constants; if `null`, the type is evaluated from `$value`.
 
**Parameters**\
&nbsp;&nbsp;`array $conditions` The condition list.\
**Return** `static` Returns self reference for method chaining.\
**Throws**\
&nbsp;&nbsp;`InvalidValueException` if the condition list is not valid.


#### showDeleted() `public`
```
showDeleted(bool $show = true): self
```
Add soft deleted entities in the result.

**Parameters**\
&nbsp;&nbsp;`bool $show` _\[optional\]_ Whether to show soft deleted entities in the result; default: `true`.\
**Return** `static` Returns self reference for method chaining.


#### execute() `public`
```
execute(): int
```
Get the count result.

**Return** `int` Returns the number of entities with given conditions.\
**Throws**\
&nbsp;&nbsp;`ModelExecutionException` if an error occur during query execution.