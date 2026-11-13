
<?php

return [
    'sequences' => [
        1 => [
            'item_limit' => 6,
            'allocation_type' => 'dynamic',
            'target_winners' => 156,
            'prize_quantities' => array_fill(0, 6, 26),
        ],

        2 => [
            'item_limit' => 10,
            'allocation_type' => 'dynamic',
            'target_winners' => 500,
            'prize_quantities' => array_fill(0, 10, 50),
        ],

        3 => [
            'item_limit' => 11,
            'allocation_type' => 'fixed',
            'target_winners' => 208,
            'prize_quantities' => [
                10, 7, 15, 15, 11, 25,
                10, 15, 10, 40, 50,
            ],
        ],

        4 => [
            'item_limit' => 11,
            'allocation_type' => 'fixed',
            'target_winners' => 170,
            'prize_quantities' => [
                20, 20, 20, 10, 10, 8,
                10, 63, 3, 3, 3,
            ],
        ],

        5 => [
            'item_limit' => 6,
            'allocation_type' => 'fixed',
            'target_winners' => 30,
            'prize_quantities' => [
                8, 5, 10, 2, 2, 3,
            ],
        ],

        6 => [
            'item_limit' => 4,
            'allocation_type' => 'fixed',
            'target_winners' => 50,
            'prize_quantities' => [
                45, 1, 2, 2,
            ],
        ],

        7 => [
            'item_limit' => 5,
            'allocation_type' => 'fixed',
            'target_winners' => 5,
            'prize_quantities' => [
                1, 1, 1, 1, 1,
            ],
        ],

        8 => [
            'item_limit' => 1,
            'allocation_type' => 'fixed',
            'target_winners' => 10,
            'prize_quantities' => [10],
        ],

        9 => [
            'item_limit' => 1,
            'allocation_type' => 'fixed',
            'target_winners' => 1,
            'prize_quantities' => [1],
        ],
    ],
];
