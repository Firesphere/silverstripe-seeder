<?php

namespace Firesphere\Seeder\Tests;

use Firesphere\Seeder\Tasks\SeederTask;
use Firesphere\Seeder\Tests\Mock\Page;
use Firesphere\Seeder\Tests\Mock\Quote;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;

/**
 * Class SeederTaskTest
 * @package Firesphere\Seeder\Tests
 */
class SeederTaskTest extends SapphireTest
{
    /**
     * @var SeederTask
     */
    protected $seeder;

    /**
     * @var bool
     */
    protected $usesDatabase = true;

    /**
     * @var array
     */
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

    public function testLive()
    {
        Dir
        $file = Director::baseFolder() . '/.env';
        file_put_contents($file,
            str_replace('dev', 'live', file_get_contents($file)));
        ob_start();

        $request = new HTTPRequest('GET', '', ['type' => '']);
        $this->seeder->run($request);

        $result = ob_get_contents();

        ob_end_clean();

        $this->assertContains('DO NOT RUN ME ON LIVE', $result);
    }

    public function testRun()
    {
        $request = new HTTPRequest('GET', '', ['type' => 'seed']);
        $this->seeder->run($request);

        $pages = Page::get();
        $this->assertEquals(2, $pages->count());

        /** @var Page $page */
        $page = $pages->filter(['Title' => 'Samuel L. Lipsum'])->first();

        $this->assertEquals('Cat Lipsum', $page->Friends()->first()->Title);

        $this->assertTrue($page->isPublished());

        $quotes = Quote::get();

        $this->assertEquals(2, $quotes->count());
    }

    public function testParseFixture()
    {
        SeederTask::setFixtureFile('tests/fixtures/seedertasktest.yml');

        $result = $this->seeder->parseFixture();

        $this->assertTrue(is_array($result));

        $expected = [
            Quote::class =>
                [
                    'quote1' =>
                        [
                            'quote' => 'Time is an illusion. Lunchtime doubly so.',
                        ],
                    'quote2' =>
                        [
                            'quote' => 'In the beginning the Universe was created. This has made a lot of people very angry and has been widely regarded as a bad move.',
                        ],
                ],
            Page::class  =>
                [
                    'page1' =>
                        [
                            'Title'   => 'Samuel L. Lipsum',
                            'Content' => '<p>Well, the way they make shows is, they make one show. That show\'s called a pilot. Then they show that show to the people who make shows, and on the strength of that one show they decide if they\'re going to make more shows. Some pilots get picked and become television programs. Some don\'t, become nothing. She starred in one of the ones that became nothing.</p>',
                            'Quotes'  => '=>Firesphere\\Seeder\\Tests\\Mock\\Quote.quote2',
                        ],
                    'page2' =>
                        [
                            'Title'   => 'Cat Lipsum',
                            'Content' => '<p>Give attitude pooping rainbow while flying in a toasted bread costume in space loved it, hated it, loved it, hated it yet has closed eyes but still sees you and stare out the window. Chase imaginary bugs throw down all the stuff in the kitchen. Stand in front of the computer screen eat half my food and ask for more hiss and stare at nothing then run suddenly away. Your pillow is now my pet bed soft kitty warm kitty little ball of furr but hiding behind the couch until lured out by a feathery toy meowzer hack, for attack dog, run away and pretend to be victim. Intently stare at the same spot cats go for world domination yet chase dog then run away jump around on couch, meow constantly until given food, and bleghbleghvomit my furball really tie the room together meow. Playing with balls of wool climb leg tuxedo cats always looking dapper. Hack up furballs thug cat prance along on top of the garden fence, annoy the neighbor\'s dog and make it bark for jump around on couch, meow constantly until given food, lick the plastic bag.</p>',
                            'Friend'  => '=>Firesphere\\Seeder\\Tests\\Mock\\Page.page1',
                            'Quotes'  => '=>Firesphere\\Seeder\\Tests\\Mock\\Quote.quote1,=>Firesphere\\Seeder\\Tests\\Mock\\Quote.quote2',
                        ],
                ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testParseFixtureNullFile()
    {
        SeederTask::setFixtureFile(null);

        $this->assertTrue(is_array($this->seeder->parseFixture()));
    }

    public function testNoType()
    {
        $request = new HTTPRequest('GET', '', []);
        ob_start();
        $this->seeder->run($request);


        $this->assertContains('Please tell me what to do', ob_get_contents());
        ob_end_clean();
    }

    public function testGetFixture()
    {
        // As it's static, it's null by default in this situation
        $this->assertNull(SeederTask::getFixtureFile());
    }

    public function testUnseed()
    {
        $request = new HTTPRequest('GET', '', ['type' => 'unseed']);
        $this->seeder->run($request);

        $this->assertEquals(0, Page::get()->count());

        $this->assertEquals(0, Quote::get()->count());
    }

    public function testRemoveRelations()
    {
        $request = new HTTPRequest('GET', '', ['type' => 'seed']);
        $this->seeder->run($request);

        $this->seeder->removeManyMany(Page::class);

        $pages = Page::get();

        foreach ($pages as $page) {
            $this->assertEquals(0, (int)$page->Quotes()->count());
        }
    }

    public function testUnpublishEach()
    {
        $request = new HTTPRequest('GET', '', ['type' => 'seed']);
        $this->seeder->run($request);

        $this->seeder->unpublishEach(Page::class);

        $pages = Page::get();

        foreach ($pages as $page) {
            $this->assertFalse($page->isPublished());
        }
    }
}
