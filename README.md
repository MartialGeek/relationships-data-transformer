Relationships data transformer
==============================

## Purpose

This library helps you to manipulate the data fetched from queries with relationships.

An ORM really improves the way you are playing with databases, but they have a significant drawback: they kill your
application performances!

If your primary concern is the performance, you can not use an ORM. So come back to the fundamentals: what do you need
to interact with your databases?

1.  A database interface, such as PDO, or Doctrine DBAL (which is a PDO overlay). Doctrine DBAL is a good choice
    because it provides an abstraction for different kinds of databases, a useful query builder and an first class
    integration with Symfony. It is of course an efficient solution.
    
2.  Repositories, because this pattern helps you to keep your model manipulations in one place. Your repositories
    take your database connections as dependencies.
    
3.  Hydrators, because as modern PHP developers, we prefer play with objects rather than arrays. Your hydrators have
    the responsibility to transform the databases results in model objects (entities). They also are dependencies of
    your repositories.
    
But when your queries contain relationships, hydrate the entities may be a nightmare. This is where this library
comes in.

## Installation

Add the library to your Composer dependencies:

    composer require 'martial/relationships-data-transformer:~1.0'

## Usage

Ensure that you have loaded the Composer autoloader, and create an instance of RelationshipsDataTransformer:

```php
use Martial\RelationshipsDataTransformer\RelationshipsDataTransformer;

require __DIR__ . '/vendor/autoload.php';

$transformer = new RelationshipsDataTransformer();
```

Now, run a SQL query with relationships. For instance:

```php
$query = <<<SQL
SELECT
  u.id AS user_id,
  u.username AS user_name,
  r.id AS role_id,
  r.name AS role_name,
  b.id AS book_id,
  b.name AS book_name
FROM
  users AS u
LEFT JOIN
  role AS r ON u.id = r.user_id
LEFT JOIN
  user_book AS ub ON u.id = ub.user_id
LEFT JOIN
  book AS b ON ub.book_id = b.id
SQL;

$pdo = new \PDO($params);
$statement = $pdo->prepare($query);
$rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
```

At this point, you have as many rows as found relationships. The $rows variable looks like this:

```php
[
    [
        'user_id' => '1',
        'user_name' => 'MartialGeek',
        'role_id' => '1',
        'role_name' => 'ROLE_ADMIN',
        'book_id' => '1',
        'book_name' => 'Linux pour les nuls'
    ],
    [
        'user_id' => '1',
        'user_name' => 'MartialGeek',
        'role_id' => '2',
        'role_name' => 'ROLE_USER',
        'book_id' => '1',
        'book_name' => 'Linux pour les nuls'
    ],
    [
        'user_id' => '1',
        'user_name' => 'MartialGeek',
        'role_id' => '1',
        'role_name' => 'ROLE_ADMIN',
        'book_id' => '2',
        'book_name' => 'I Love PHP'
    ],
    [
        'user_id' => '1',
        'user_name' => 'MartialGeek',
        'role_id' => '2',
        'role_name' => 'ROLE_USER',
        'book_id' => '2',
        'book_name' => 'I Love PHP'
    ],
    [
        'user_id' => '2',
        'user_name' => 'Doe',
        'role_id' => '3',
        'role_name' => 'ROLE_USER',
        'book_id' => '3',
        'book_name' => 'Octavia Praetexta'
    ],
    [
        'user_id' => '2',
        'user_name' => 'Doe',
        'role_id' => '3',
        'role_name' => 'ROLE_USER',
        'book_id' => '4',
        'book_name' => 'C. Iuli Caesaris De Bello Gallico'
    ],
];
```

Iterate over these rows is a nightmare for hydrating the results as objects.
So call the method transform of the data transformer with the options to organize your data before hydrating them:

```php
$mergedRelationships = $transformer->transform($rows, [
    RelationshipsDataTransformer::OPTION_ROOT_PRIMARY_KEY => 'user_id',
    RelationshipsDataTransformer::OPTION_RELATIONSHIPS => [
        'roles' => [
            RelationshipsDataTransformer::OPTION_PREFIX => 'role_',
            RelationshipsDataTransformer::OPTION_PRIMARY_KEY => 'role_id',
            RelationshipsDataTransformer::OPTION_REFERENCE_COLUMN => 'user_id'
        ],
        'books' => [
            RelationshipsDataTransformer::OPTION_PREFIX => 'book_',
            RelationshipsDataTransformer::OPTION_PRIMARY_KEY => 'book_id',
            RelationshipsDataTransformer::OPTION_REFERENCE_COLUMN => 'user_id'
        ],
    ]
]);
```

All these options are mandatory to define your relationships:

1.  RelationshipsDataTransformer::OPTION_ROOT_PRIMARY_KEY:
    Defines the primary key of the main table of your query (in the "from" clause)

2.  RelationshipsDataTransformer::OPTION_RELATIONSHIPS:
    Contains an array of relationships definitions. Each sub-key represents a relation name. Here, the roles are stored
    in sub-keys "roles", and the books in "books" keys.
    
3.  RelationshipsDataTransformer::OPTION_PREFIX:
    The prefix of the rows containing the current relationship. For the roles, we used the alias "role_" to prefix
    the related results. Then the data transformer will look for this prefix to find the related data in the rows.
    
4.  RelationshipsDataTransformer::OPTION_PRIMARY_KEY:
    The primary key of the relationship. Once the relationships data are extracted from the database results, the
    library uses this information to find where merge the related data.
    
5.  RelationshipsDataTransformer::OPTION_REFERENCE_COLUMN:
    The reference column of the relationship, as defined as in your table definition. This information is needed to
    know when the library must create a new row set of the current relationship.

Now, the $mergedRelationships array looks like:

```php
[
    [
        'user_id' => '1',
        'user_name' => 'MartialGeek',
        'roles' => [
            [
                'id' => '1',
                'name' => 'ROLE_ADMIN'
            ],
            [
                'id' => '2',
                'name' => 'ROLE_USER'
            ]
        ],
        'books' => [
            [
                'id' => '1',
                'name' => 'Linux pour les nuls'
            ],
            [
                'id' => '2',
                'name' => 'I Love PHP'
            ]
        ]
    ],
    [
        'user_id' => '2',
        'user_name' => 'Doe',
        'roles' => [
            [
                'id' => '3',
                'name' => 'ROLE_USER'
            ]
        ],
        'books' => [
            [
                'id' => '3',
                'name' => 'Octavia Praetexta'
            ],
            [
                'id' => '4',
                'name' => 'C. Iuli Caesaris De Bello Gallico'
            ]
        ]
    ]
];
```

It is very easy to hydrate your data:

```php
$hydrator = new My\Hydrator();
$users = [];

foreach ($mergedRelationships as $row) {
      $hydrator->hydrate($row);
}
```

In your hydrator, you build the related entities by iterating over the relationship keys "books" and "roles".
