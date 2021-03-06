<?php

namespace Firesphere\Seeder\Tasks;

use \Page;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\FixtureFactory;
use SilverStripe\Dev\YamlFixture;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\DefaultAdminService;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use Symfony\Component\Yaml\Parser;

/**
 * Class SeederTask
 * @package Firesphere\Seeder\Tasks
 */
class SeederTask extends BuildTask
{
    use Configurable;

    /**
     * @var string URLSegment
     */
    private static $segment = 'seeder';

    /**
     * @var string path to fixture
     */
    protected static $fixtureFile;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var FixtureFactory
     */
    protected $factory;

    /**
     * @var YamlFixture
     */
    protected $fixture;

    /**
     * SeederTask constructor.
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Exception
     */
    public function __construct()
    {
        $this->config = Config::inst()->get(static::class);

        $this->factory = Injector::inst()->get(FixtureFactory::class);
        $seed = $this->config['Seedfile'];

        /** @var YamlFixture $fixture */
        $this->fixture = Injector::inst()->create(YamlFixture::class, $seed);

        // Log in as admin so we can publish and unpublish
        $adminService = Injector::inst()->get(DefaultAdminService::class);
        $admin = $adminService->findOrCreateDefaultAdmin();
        Security::setCurrentUser($admin);
        parent::__construct();
    }

    /**
     * @return string
     */
    public static function getFixtureFile()
    {
        return self::$fixtureFile;
    }

    /**
     * @param string $fixtureFile
     */
    public static function setFixtureFile($fixtureFile)
    {
        self::$fixtureFile = $fixtureFile;
    }

    /**
     * @param HTTPRequest $request
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function run($request)
    {
        if (!Director::isLive()) {
            switch ($request->getVar('type')) {
                case 'seed':
                    $this->seed();
                    break;
                case 'unseed':
                    $this->unSeed();
                    break;
                default:
                    Debug::message('Please tell me what to do? `type=seed` or `type=unseed`', false);
            }
        } else {
            Debug::message('DO NOT RUN ME ON LIVE ENVIRONMENTS', false);
        }
    }


    /**
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function seed()
    {
        Debug::message('Starting seed', false);
        $this->fixture->writeInto($this->factory);
        Debug::message('Publishing Versioned items', false);
        $this->publishEach();
        Debug::message('Done seeding', false);
    }

    /**
     *
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function unSeed()
    {
        Debug::message('Starting unseed', false);
        $fixtureContent = $this->parseFixture();
        foreach ($fixtureContent as $class => $items) {
            /** @var DataObject $class */
            $class = Injector::inst()->get($class);
            if ($class->manyMany()) {
                $this->removeManyMany($class);
            }
            if ($class->hasExtension(Versioned::class)) {
                $this->unpublishEach($class);
            }
            $class::get()->removeAll();
        }
        Debug::message('Done Unseeding', false);
    }

    /**
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function publishEach()
    {
        Debug::message('Publishing versioned items', false);
        /** @var DataList|\Page[] $pages */
        $pages = Page::get();
        foreach ($pages as $page) {
            $page->publishRecursive();
        }

        $fixtureContent = $this->parseFixture();
        foreach ($fixtureContent as $className => $items) {
            $class = Injector::inst()->get($className);
            if ($class->hasExtension(Versioned::class) && !$class instanceof Page) {
                /** @var DataList|DataObject[] $items */
                $items = Versioned::get_by_stage($className, Versioned::DRAFT);
                foreach ($items as $item) {
                    if (!$item->isPublished()) {
                        $item->publishRecursive();
                        $item->destroy();
                    }
                }
            }
        }
    }

    /**
     * @param DataObject|string $class
     */
    public function removeManyMany($class)
    {
        $items = $class::get();
        Debug::message('Removing relations', false);
        foreach ($items as $obj) {
            foreach ($obj->manyMany() as $name => $className) {
                $obj->$name()->removeAll();
            }
        }
    }

    /**
     * @param DataObject|string $class
     */
    public function unpublishEach($class)
    {
        Debug::message('Unpublishing versioned items', false);
        /** @var DataList|DataObject[] $items */
        $items = $class::get();
        foreach ($items as $item) {
            if ($item->isPublished()) {
                $item->doUnpublish();
                $item->destroy();
            }
        }
    }

    /**
     * @return array|mixed
     * @throws \Symfony\Component\Yaml\Exception\ParseException
     */
    public function parseFixture()
    {
        $parser = new Parser();
        $fixtureContent = [];
        if (file_exists($this->fixture->getFixtureFile())) {
            $contents = file_get_contents($this->fixture->getFixtureFile());
            $fixtureContent = $parser->parse($contents);
        }

        return $fixtureContent;
    }
}
