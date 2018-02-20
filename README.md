[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Firesphere/silverstripe-seeder/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Firesphere/silverstripe-seeder/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/Firesphere/silverstripe-seeder/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Firesphere/silverstripe-seeder/build-status/master)
[![codecov](https://codecov.io/gh/Firesphere/silverstripe-seeder/branch/master/graph/badge.svg)](https://codecov.io/gh/Firesphere/silverstripe-seeder)

# SilverStripe Seeder

# *WARNING*

This will _not_ create a test database for you! Do not run this against a production database!

Seed your database for Acceptance testing

# Installation

`composer require firesphere/seeder`

# Usage

Create a seeder yml file somewhere in your project, with the same syntax as the standard PHPUnit tests. E.g.

```yaml

Firesphere\Seeder\Tests\Mock\Page:
  page1:
    Title: The title
    Content: "<p>Well, the way they make shows is, they make one show. That show's called a pilot. Then they show that show to the people who make shows, and on the strength of that one show they decide if they're going to make more shows. Some pilots get picked and become television programs. Some don't, become nothing. She starred in one of the ones that became nothing.</p>"

```

Relations are defined the same way as in PHPUnit.

Then run the seeder

`vendor/bin/sake dev/tasks/seeder type=seed flush=all`

It will do a `dev/build` before actually seeding, to make sure the database is up to date.

To unseed, run

`vendor/bin/sake dev/tasks/seeder type=seed flush=all`

This will destroy all data that is defined in your seeder yml. It will do a dev build to reinstate the standard pages.

# todo

Integrate with Codeception/Behat to automatically seed on run