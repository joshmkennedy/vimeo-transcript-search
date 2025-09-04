<?php

return [
    'slug'=> 'aim-clip-list',
    'args' =>[
        // 'label' => 'Aim Clips Lists',
        'labels' => [
            'name' => 'Aim Clips Lists',
            'singular_name' => 'Aim Clips List',
            'add_new' => 'Add New',
            'add_new_item' => 'Add New Aim Clips List',
            'edit_item' => 'Edit Aim Clips List',
            'new_item' => 'New Aim Clips List',
            'view_item' => 'View Aim Clips List',
            'search_items' => 'Search Aim Clips Lists',
            'not_found' => 'No Aim Clips Lists found',
            'not_found_in_trash' => 'No Aim Clips Lists found in Trash',
            'parent_item_colon' => 'Parent Aim Clips List:',
            'menu_name' => 'Aim Clips Lists',
        ],
        'description' => 'A list of Aim Clips',
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => true,
        'show_in_rest' => true,
        'rest_base' => 'aim-clips-lists',
        'has_archive' => false,
        'show_in_menu' => true,
        'exclude_from_search' => true,
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'hierarchical' => false
    ],
];
