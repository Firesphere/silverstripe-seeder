<?php

namespace Firesphere\Seeder\Tasks;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\FixtureFactory;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\YamlFixture;
use SilverStripe\ORM\DatabaseAdmin;
use SilverStripe\Security\DefaultAdminService;
use SilverStripe\Security\Security;
use SilverStripe\Versioned\Versioned;
use Symfony\Component\Yaml\Parser;

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
     * @throws \Exception
     */
    public function run($request)
    {
        if (Director::isLive()) {
            throw new \Exception('DO NOT RUN ME ON LIVE ENVIRONMENTS');
        }

        switch ($request->getVar('type')) {
            case 'seed':
                $this->seed();
                break;
            case 'unseed':
                $this->unSeed();
                break;
            default:
                throw new \Exception('Please tell me what to do? `type=seed` or `type=unseed`');
        }
    }

    /**
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function seed()
    {
        Debug::message('Starting seed');
        $this->fixture->writeInto($this->factory);
        Debug::message('Publishing Versioned items');
        $this->publishEach();
        Debug::message('Done seeding');
    }

    /**
     *
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function unSeed()
    {
        Debug::message('Starting unseed');
        $fixtureContent = $this->parseFixture();
        foreach ($fixtureContent as $class => $items) {
            $class = Injector::inst()->get($class);
            if ($class->hasExtension(Versioned::class)) {
                $items = $class::get();
                foreach ($items as $item) {
                    $item->doUnpublish();
                    $item->destroy();
                }
            }
            $class::get()->removeAll();
        }
        Debug::message('Done Unseeding');
    }

    /**
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function publishEach()
    {
        $fixtureContent = $this->parseFixture();
        foreach ($fixtureContent as $class => $items) {
            $class = Injector::inst()->get($class);
            if ($class->hasExtension(Versioned::class)) {
                $items = $class::get();
                foreach ($items as $item) {
                    $item->publishRecursive();
                    $item->destroy();
                }
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
        if ($this->fixture->getFixtureString() !== null) {
            $fixtureContent = $parser->parse($this->fixture->getFixtureString());
        } else {
            if (file_exists($this->fixture->getFixtureFile())) {
                $contents = file_get_contents($this->fixture->getFixtureFile());
                $fixtureContent = $parser->parse($contents);
            }
        }

        return $fixtureContent;
    }
}
