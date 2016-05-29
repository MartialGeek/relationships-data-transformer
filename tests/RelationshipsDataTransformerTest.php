<?php
/**
 * Copyright (C) 2016  Martial Saunois
 *
 * Please read the LICENSE file at the root directory of the project for the full notice.
 */

namespace MartialGeek\RelationshipsDataTransformer\Tests;

use Martial\RelationshipsDataTransformer\RelationshipsDataTransformer;
use Mockery as m;

class RelationshipsDataTransformerTest extends m\Adapter\Phpunit\MockeryTestCase
{
    public function testTransformShouldExtractTheRelationshipsInTargetKeys()
    {
        $transformer = new RelationshipsDataTransformer();

        $data = [
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

        $expected = [
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

        $options = [
            'relationships' => [
                'roles' => [
                    'prefix' => 'role_',
                    'primary_key' => 'role_id',
                    'reference_column' => 'user_id'
                ],
                'books' => [
                    'prefix' => 'book_',
                    'primary_key' => 'book_id',
                    'reference_column' => 'user_id'
                ]
            ],
            'root_primary_key' => 'user_id'
        ];

        $result = $transformer->transform($data, $options);
        $this->assertSame($expected, $result);
    }
}
