<?php

namespace AMBERSIVE\PackageMaker\Tests\Unit;

use Tests\TestCase;

use Config;
use File;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MakeCommandTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        Config::set("package-maker.path", "tmp");

        shell_exec("rm -rf ".base_path("tmp"));
        mkdir(base_path("tmp"));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        shell_exec("rm -rf ".base_path("tmp"));
    }
    
    /**
     * Test if the missing name property will throw an exception
     */
    public function testIfMakePackageCommandWillHaveAnException():void {

        $this->expectException(\Symfony\Component\Console\Exception\RuntimeException::class);
        $this->artisan('make:package')->assertExitCode(1);

    }

    /**
     * Test if the name "demo" will throw an error 
     */
    public function testIfMakePackageCommandWillNotHaveAnException():void {

        $this->artisan('make:package', ['name' => 'demo'])->expectsOutput('Invalid package name. Please make sure you choose a valid package name. eg. ambersive/demo.')->assertExitCode(0);

    }

    /**
     * Test if the package with its content will be created
     */
    public function testIfMakePackageCommandWillCreatePackageIfAllParamsAreCorrect():void {

        $this->artisan('make:package', ['name' => 'ambersive/demo'])
            ->expectsQuestion('Whats the purpose of this package?', 'Demo Package')
            ->expectsQuestion('Whats your name?', 'Manuel')
            ->expectsQuestion('Whats you e-mail address?', 'manuel.pirker-ihl@ambersive.com')
            ->expectsQuestion('For which laravel versions do you want to create this plugin.', ["dev-master"])
            ->assertExitCode(0);

        $this->assertTrue(File::exists(base_path("tmp/ambersive/demo/README.md")));
        $this->assertTrue(File::exists(base_path("tmp/ambersive/demo/CHANGELOG.md")));
        $this->assertTrue(File::exists(base_path("tmp/ambersive/demo/composer.json")));
        $this->assertTrue(File::exists(base_path("tmp/ambersive/demo/src/DemoServiceProvider.php")));

        $content = File::get(base_path("tmp/ambersive/demo/src/DemoServiceProvider.php"));

        $this->assertNotFalse(strpos($content, "class DemoServiceProvider"));
        $this->assertNotFalse(strpos($content, "namespace Ambersive\Demo;"));

    }

}