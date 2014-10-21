<?php
$attrMap = [
    //simples
    'One Size Fits All' => [
        'options'   => [],
    ],
    'Alaska Fit Coats and Jackets' => [
        'alaska_fit_jackets_vests_sizes'        => [36, 38, 40, 42, 44, 46, 48, 50, 52, 54],
        'options'   => [
            'fabric'                             => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'Alaska Fit Vests and Cruisers' => [
        'alaska_fit_jackets_vests_sizes'        => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
        'options'   => [
            'fabric'                             => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'Bags and Luggage' => [
        'bag_size'                              => ['Small', 'Medium', 'Large'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
            'bag_type'                          => ['Backpack', 'Briefcase', 'Duffle Bag', 'Field Bag', 'Garment', 'Gun Case', 'Messenger Bag', 'Rod Case', 'Tote', 'Travel Bag', 'Travel Kit'],
        ],
    ],
    'Baselayers' => [
        'garment_size'                          => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'Belts' => [
        'waist_size'                            => [28, 29, 30, 31, 32, 33, 34, 35, 36, 38, 40, 42, 44, 46, 48],
//        'belt_width'                            => ['1"', '1-1/2"', '1-1/4"'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'Bibs' => [
        'waist_size'                            => [28, 29, 30, 31, 32, 33, 34, 35, 36, 38, 40, 42, 44, 46, 48],
        'inseam'                                => [28, 29, 30, 31, 32, 33, 34, 35, 36, 'Unhemmed'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'Chaps' => [
        'chap_length'                           => ['Regular', 'Long'],
//        'chap_fit'                              => ['Regular', 'Husky'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'Dog Accessories' => [
        'dog_accessory_size'                    => ['S', 'M', 'L', 'XL'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'Dog Collars' => [
        'dog_collar_size'                       => ['9 in', '11 in', '14 in', '16 in', '19 in', '21 in', '23 in'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'Gloves' => [
        'glove_size'                            => ['S', 'M', 'L', 'XL'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'Gun Scabbards' => [
        'gun_scabbard_size'                     => ['Up to 46"', 'Up to 48"', 'Up to 50"'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'Hats and Caps' => [
        'hat_size'                              => ['XS', 'S', 'M', 'L', 'XL', 'XXL'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'Pants' => [
        'waist_size'                            => [28, 29, 30, 31, 32, 33, 34, 35, 36, 38, 40, 42, 44, 46, 48],
//        'inseam'                                => [28, 29, 30, 31, 32, 33, 34, 35, 36, 'Unhemmed'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'FixedPants' => [
        'fixed_size'                            => ['28x34', '29x34', '30x34', '31x34', '32x34', '33x34', '34x34', '36x34', '38x34', '40x34', '42x34', '44x34'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'Seattle Fit Jackets, Coats and Vests' => [
        'garment_size'                          => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'Shirts' => [
        'garment_size'                          => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'Shoes' => [
        'shoe_size'                             => ['7', '7 1/2', '8', '8 1/2', '9', '9 1/2', '10', '10 1/2', '11', '11 1/2', '12', '13', '14', '15'],
        'shoe_width'                            => ['D', 'EE'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'Shoulder Straps' => [
        'shoulder_strap_size'                   => ['30" Tongue', '34" Tongue', '37" Tongue', '40" Tongue', '45" Tongue', '46" Tongue'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'Socks' => [
        'sock_size'                             => ['S', 'M', 'L', 'XL'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'Strap Vests' => [
        'strap_vest_size'                       => ['Regular', 'Super'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'Suspenders' => [
        'suspender_size'                        => ['Regular', 'Long'],
//        'suspender_type'                        => ['Tab', 'Clip'],
    ],
    'Sweaters' => [
        'garment_size'                          => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
    'Waders' => [
        'wader_size'                            => ['S', 'M', 'L'],
//        'wader_fit'                             => ['Regular', 'King'],
        'options'   => [
            'fabric'                            => ['Fleece', 'Leather', 'Merino Wool', 'Moleskin', 'Soy Wax', 'Edmonds Wool', 'Yukon Wool', 'Mackinaw Wool', 'Antique Tin Cloth', 'Cover Cloth', 'Shelter Cloth', 'Twill', 'Tin Cloth', 'Feather Cloth'],
        ],
    ],
];