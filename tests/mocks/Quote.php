<?php

namespace Firesphere\Seeder\Tests\Mock;


use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Quote extends DataObject// implements TestOnly
{

    private static $db = [
        'Quote' => 'Text'
    ];

    private static $has_many = [
        'Pages' => Page::class
    ];

    private static $table_name = 'SeederTestQuote';
}