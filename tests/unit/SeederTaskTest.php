<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 20-Feb-18
 * Time: 19:20
 */

namespace Firesphere\Seeder\Tests;


use Firesphere\Seeder\Tasks\SeederTask;
use Firesphere\Seeder\Tests\Mock\Page;
use Firesphere\Seeder\Tests\Mock\Quote;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;

class SeederTaskTest extends SapphireTest
{
    /**
     * @var SeederTask
     */
    protected $seeder;

    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        Mock\Page::class,
        Mock\Quote::class
    ];

    public function setUp()
    {
        parent::setUp();
        Config::modify()->update(SeederTask::class, 'Seedfile', 'tests/fixtures/seedertasktest.yml');
        $this->seeder = Injector::inst()->get(SeederTask::class);
    }

    public function testRun()
    {
        $request = new HTTPRequest('GET', '', ['type' => 'seed']);
        $this->seeder->run($request);

        $pages = Page::get();
        $this->assertEquals(2, $pages);

        $page = $pages->filter(['Title' => 'Samuel L. Lipsum'])->first();

        $this->assertEquals('Cat Lipsum', $page->Friend()->Title);

        $quotes = Quote::get();

        $this->assertEquals(2, $quotes->count());
    }

    public function testParseFixture()
    {

    }
}