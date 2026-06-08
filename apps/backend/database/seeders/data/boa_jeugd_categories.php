<?php

/**
 * BOA / Jeugd issue category reference data.
 *
 * Priority mapping: Zwaar=1, Midden=2, Laag=3 (1 = highest).
 *
 * @return array{
 *     mains: list<array{name: string, priority: int}>,
 *     subs: list<array{name: string, parent: string}>
 * }
 */
return [
    'mains' => [
        ['name' => 'Parkeeroverlast', 'priority' => 1],
        ['name' => 'Jeugdoverlast', 'priority' => 1],
        ['name' => 'Geluidsoverlast', 'priority' => 1],
        ['name' => 'Hondenoverlast', 'priority' => 2],
        ['name' => 'Hangouderen', 'priority' => 2],
        ['name' => 'Illegaal BBQen', 'priority' => 3],
        ['name' => 'Straatintimidatie', 'priority' => 2],
        ['name' => 'Buitenslapers', 'priority' => 3],
        ['name' => 'Thuis/Daklozen', 'priority' => 2],
        ['name' => 'Alcohol Gebruik', 'priority' => 3],
        ['name' => 'Bedelen', 'priority' => 3],
        ['name' => 'Vuurwerkoverlast', 'priority' => 2],
        ['name' => 'Vandalisme', 'priority' => 2],
        ['name' => 'Vernieling', 'priority' => 1],
        ['name' => 'Illegaal doorzoeken vuil', 'priority' => 3],
        ['name' => 'Illegaal dumpen vuil', 'priority' => 1],
        ['name' => 'Lachgas', 'priority' => 1],
    ],
    'subs' => [
        ['name' => 'Parkeren op stoep', 'parent' => 'Parkeeroverlast'],
        ['name' => 'Parkeren mindervaliden', 'parent' => 'Parkeeroverlast'],
        ['name' => 'Parkeren laad-en los plek', 'parent' => 'Parkeeroverlast'],
        ['name' => 'Beklading', 'parent' => 'Vandalisme'],
        ['name' => 'Ophangen posters', 'parent' => 'Vandalisme'],
    ],
];
