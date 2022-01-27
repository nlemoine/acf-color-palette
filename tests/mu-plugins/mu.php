<?php

if (!file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    return;
}

require __DIR__ . '/../../vendor/autoload.php';

$field = new StoutLogic\AcfBuilder\FieldsBuilder('color_palette');
$field->addField('color', 'color_palette', [
    'exclude_colors' => ['primary', 'blue'],
    'allow_null' => true,
]);

$simple = new StoutLogic\AcfBuilder\FieldsBuilder('test_simple');
$simple
    ->addFields($field)
;

$repeater = new StoutLogic\AcfBuilder\FieldsBuilder('test_repeater');
$repeater
    ->addRepeater('repeater', [
        'layout' => 'block',
    ])
        ->addFields($field)
    ->endRepeater()
;

$flexible = new StoutLogic\AcfBuilder\FieldsBuilder('test_flexible');
$flexible
    ->addFlexibleContent('flexible', [
        'layout' => 'block',
    ])
        ->addLayout('color')
            ->addFields($field)
        ->addLayout('text')
            ->addText('text')
;

$fields = [
    $simple,
    $repeater,
    $flexible,
];

add_action('acf/init', function () use ($fields) {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    foreach ($fields as $field) {
        $field
            ->setLocation('post_type', '==', 'page')
            ->or('post_type', '==', 'post')
        ;
        acf_add_local_field_group($field->build());
    }
});
