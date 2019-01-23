<?php //-->
return [
    'disable' => '1',
    'singular' => 'Authentication',
    'plural' => 'Authentications',
    'name' => 'auth',
    'icon' => 'fas fa-lock',
    'group' => 'Users',
    'detail' => 'Collection of verified users.',
    'fields' => [
        [
            'disable' => '1',
            'label' => 'Email',
            'name' => 'slug',
            'field' => [
                'type' => 'text',
                'attributes' => [
                    'placeholder' => 'Enter email',
                ]
            ],
            'type' => 'slug',
            'validation' => [
                [
                    'method' => 'required',
                    'message' => 'Email is Required'
                ],
                [
                    'method' => 'regexp',
                    'message' => 'Must be a valid email',
                    'parameters' => '/^(?:(?:(?:[^@,"\[\]\x5c\x00-\x20\x7f-\xff\.]|\x5c(?=[@,"\[\]'.
                    '\x5c\x00-\x20\x7f-\xff]))(?:[^@,"\[\]\x5c\x00-\x20\x7f-\xff\.]|(?<=\x5c)[@,"\[\]'.
                    '\x5c\x00-\x20\x7f-\xff]|\x5c(?=[@,"\[\]\x5c\x00-\x20\x7f-\xff])|\.(?=[^\.])){1,62'.
                    '}(?:[^@,"\[\]\x5c\x00-\x20\x7f-\xff\.]|(?<=\x5c)[@,"\[\]\x5c\x00-\x20\x7f-\xff])|'.
                    '[^@,"\[\]\x5c\x00-\x20\x7f-\xff\.]{1,2})|"(?:[^"]|(?<=\x5c)"){1,62}")@(?:(?!.{64})'.
                    '(?:[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.?|[a-zA-Z0-9]\.?)+\.(?:xn--[a-zA-Z0-9]'.
                    '+|[a-zA-Z]{2,6})|\[(?:[0-1]?\d?\d|2[0-4]\d|25[0-5])(?:\.(?:[0-1]?\d?\d|2[0-4]\d|25'.
                    '[0-5])){3}\])$/'
                ],
                [
                    'method' => 'unique',
                    'message' => 'Email is already taken'
                ]
            ],
            'list' => [
                'format' => 'none',
            ],
            'detail' => [
                'format' => 'none',
            ],
            'default' => '',
            'searchable' => '1',
            'filterable' => '1'
        ],
        [
            'disable' => '1',
            'label' => 'Password',
            'name' => 'password',
            'field' => [
                'type' => 'password',
                'attributes' => [
                    'placeholder' => 'Enter a password',
                ]
            ],
            'sql' => [
                'type' => 'varchar',
                'length' => 255,
                'encoding' => 'md5',
                'index' => true
            ],
            'validation' => [
                [
                    'method' => 'required',
                    'message' => 'Password is Required'
                ]
            ],
            'list' => [
                'format' => 'hide',
            ],
            'detail' => [
                'format' => 'hide',
            ],
            'default' => '',
            'filterable' => '1',
            'sortable' => '1'
        ],
        [
            'disable' => '1',
            'label' => 'Type',
            'name' => 'type',
            'field' => [
                'type' => 'text',
            ],
            'list' => [
                'format' => 'none',
            ],
            'detail' => [
                'format' => 'none',
            ],
            'default' => '',
            'filterable' => '1',
            'sortable' => '1'
        ],
        [
            'disable' => '1',
            'label' => 'Active',
            'name' => 'active',
            'field' => [
                'type' => 'active',
            ],
            'list' => [
                'format' => 'hide',
            ],
            'detail' => [
                'format' => 'hide',
            ],
            'default' => '1',
            'filterable' => '1',
            'sortable' => '1'
        ],
        [
            'disable' => '1',
            'label' => 'Created',
            'name' => 'created',
            'field' => [
                'type' => 'created',
            ],
            'list' => [
                'format' => 'none',
            ],
            'detail' => [
                'format' => 'none',
            ],
            'default' => 'NOW()',
            'sortable' => '1'
        ],
        [
            'disable' => '1',
            'label' => 'Updated',
            'name' => 'updated',
            'field' => [
                'type' => 'updated',
            ],
            'list' => [
                'format' => 'none',
            ],
            'detail' => [
                'format' => 'none',
            ],
            'default' => 'NOW()',
            'sortable' => '1'
        ]
    ],
    'relations' => [
        [
            'many' => '1',
            'name' => 'profile'
        ]
    ],
    'fixtures' => [
        [
            'auth_slug'     => 'john@doe.com',
            'auth_password' => '123',
            'auth_type'     => 'developer',
            'auth_created'  => '2018-02-03 01:45:16',
            'auth_updated'  => '2018-02-03 01:45:16',
            'profile_id'    => 1
        ]
    ],
    'suggestion' => '{{auth_slug}}'
];
