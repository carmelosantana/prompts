<?php

declare(strict_types=1);

namespace CarmeloSantana\Prompts;

echo dirname(__DIR__) . 'vendor/autoload.php';

require dirname(__DIR__) . '/vendor/autoload.php';

$data = [
    'action' => [
        'standing',
        'sitting',
        'laying',
        'walking',
        'running',
        'dancing',
        'playing',
    ],
    'amount' => [
        random_int(0, 10),
    ],
    'art_by' => [
        '{artist} and {artist 1} and {artist 2}, cgsociety',
        'style of {art_by} and {artist 1}, {render ab1}, {gallery}, {render ab2}',
        'style of Greg Rutkowski and {artist 1}, cgsociety',
    ],
    'my_styles' => [
        '{resolution}, {render 1}, {detail 1}, {lighting}, {render 2}',
        '{style}, {style 1}, {color}, {style 2}, {medium}',
        '{style}, {style 1}, {camera}, {render 1}, {color}, {style 2}, {render 2}',        
    ],
    'skin' => [
        'psychedelic',
        'iridescent',
        'almond',
        'honey',
        'gold',
        'chrome',
    ],
    'who' => [
        'dancer',
        'supermodel',
        'warrior',
        'cyborg',
        'sentient ai',
        'cybernetic',
    ],
];


$template = 'game asset of a {who} with {skin} skin, {art_by}, {my_styles}';

$prompt = new Prompts();
$prompt->addLists($data);
$prompt->setCount(10);
$prompt->setTemplate($template);
$prompt->setOption('exhaust_list', true);
$prompt->setOption('save_to_file', true);
$prompt->run();
