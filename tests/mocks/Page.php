<?php

namespace Firesphere\Seeder\Tests\Mock;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

class Page extends SiteTree implements TestOnly
{

    private static $db = [
        'ExtraContent' => 'HTMLText'
    ];

    private static $has_one = [
        'Friend' => Page::class
    ];

    private static $has_many = [
        'Quotes' => Quote::class
    ];

    private static $table_name = 'SeederTestPage';
}
