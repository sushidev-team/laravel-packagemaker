<?php

namespace AMBERSIVE\PackageMaker\Console\Commands\Dev;

use Illuminate\Console\Command;

use Str;
use File;

use Illuminate\Console\GeneratorCommand;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputOption;

class MakePackage extends GeneratorCommand
{

    protected String $packageName = "";
    protected String $packagePath = "";
    protected String $packageDescription = "";
    protected String $packageCreatorName = "";
    protected String $packageCreatorEmail = ""; 
    protected array $packageLaravelVersions = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:package {name} {--composer} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new laravel package.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $name = $this->getNameInput();

        if (strpos($name, "/") === false) {
            return $this->error('Invalid package name. Please make sure you choose a valid package name. eg. ambersive/demo.');
        }

        $path    = $this->getPath($name);
        $success = $this->makeDirectory($path);

        if ((! $this->hasOption('force') ||
             ! $this->option('force')) && 
            File::exists($path)) {
            $this->error('Package already exists!');
            return false;
        }


        $creatorName  = config('package-maker.creator_name', null);
        $creatorEmail = config('package-maker.creator_email', null);
        $laravelVersions = $this->getLaravelVersionLists();

        $this->packageName = $name;
        $this->packagePath = $path;
        $this->packageDescription = $this->ask('Whats the purpose of this package?', 'New package');
        $this->packageCreatorName = $this->anticipate('Whats your name?', [$creatorName], $creatorName);
        $this->packageCreatorEmail = $this->anticipate('Whats you e-mail address?', [$creatorEmail], $creatorEmail);
        $this->packageLaravelVersions = $this->choice(
            'For which laravel versions do you want to create this plugin.',
            $laravelVersions,
            array_search("6.*", $laravelVersions),
            $maxAttempts = null,
            $allowMultipleSelections = true
        );

        $this->generateReadMeFile() == false ? $this->error("[${name}] README file could not be created.") : $this->line("[${name}] README file has been created.");
        $this->generateChangelogFile() == false ? $this->error("[${name}] CHANGELOG file could not be created.") : $this->line("[${name}] CHANGELOG file has been created.");
        $this->generateComposerFile() == false ? $this->error("[${name}] composer.json file could not be created.") : $this->line("[${name}] composer.json file has been created.");
        $this->generatePhpUnitFile() == false ? $this->error("[${name}] phpunit.xml file could not be created.") : $this->line("[${name}] phpunit.xml file has been created.");
        $this->generateEmptyFolder("tests") === false ? $this->error("[${name}] test folder could not be created.") : $this->line("[${name}] test folder has been created.");
        $this->generateEmptyFolder("docs") === false ? $this->error("[${name}] docs folder could not be created.") : $this->line("[${name}] docs folder has been created.");
        $this->generateEmptyFolder("src") === false ? $this->error("[${name}] src folder could not be created.") : $this->line("[${name}] src folder has been created.");
        $this->generateServiceProvider() === false ? $this->error("[${name}] service provider could not be created.") : $this->line("[${name}] service provider has been created.");
        $this->generateTestCase() === false ? $this->error("[${name}] test case could not be created.") : $this->line("[${name}] test case has been created.");


        $this->info("The package ${name} has been created.");


        if ($this->option("composer")){
            $this->info("Updating composer.json...");
            $this->updateComposer();
            $this->info("Composer.json updated!");
        }

    }
    
    /**
     * Returns list of possible laravel versions.
     *
     * @return array
     */
    protected function getLaravelVersionLists(): array {

        $file = json_decode(File::get(__DIR__."/../../../../composer.json"), true);
        $laravel = data_get($file, 'require-dev.laravel/framework', '6.*|7.*|8.*|dev-master');
        return explode("|", $laravel);

    }

    /**
     * Returns the path to the stubs folder
     */
    protected function getStub(): String {
        return __DIR__."/../../../Stubs/";
    }

    /**
     * Returns the path for the document class
     *
     * @param  mixed $name
     * @return String
     */
    protected function getPath($name):String {
        return $this->getPathFolder($name, config('package-maker.path' , 'packages'));
    }

    /**
     * Returns the base path for the file
     *
     * @param  mixed $name
     * @param  mixed $folder
     * @return String
     */
    protected function getPathFolder(String $name, String $folder = ''): String {
        return base_path("${folder}/${name}");
    }
    
    /**
     * Generate the README.md file
     *
     * @return bool
     */
    protected function generateReadMeFile():bool {

        $success = false;
        $path = $this->getPath($this->packageName."/README.md");
        $stub = $this->files->get($this->getStubFilePath("README"));
        $content = $this->replaceTokens($stub, [
            "{PACKAGE_NAME}",
            "{PACKAGE_DESCRIPTION}",
            "{EMAIL}",
            "{NAME}"
        ], [
            $this->packageName,
            $this->packageDescription,
            $this->packageCreatorEmail,
            $this->packageCreatorName
        ]);

        $this->makeDirectory($path);
        $this->files->put($path, $stub);
        $success = File::exists($path);

        return $success;

    }

    /**
     * Generate the CHANGELOG.md file
     *
     * @return bool
     */
    protected function generateChangelogFile():bool {

        $success = false;
        $path = $this->getPath($this->packageName."/CHANGELOG.md");
        $stub = $this->files->get($this->getStubFilePath("CHANGELOG"));
        $this->makeDirectory($path);
        $this->files->put($path, $stub);
        $success = File::exists($path);

        return $success;

    }

    /**
     * Generate composer.json file.
     *
     * @return bool
     */
    protected function generateComposerFile():bool {

        $success = false;
        $path = $this->getPath($this->packageName."/composer.json");
        $stub = $this->files->get($this->getStubFilePath("composer"));

        $splittedName     = explode("/", $this->packageName);
        $name             = ucfirst(Str::camel($splittedName[sizeOf($splittedName) - 1]."ServiceProvider"));
        $namespace        = $this->getNamespace(ucfirst($splittedName[0])."\\".ucfirst(Str::camel($splittedName[1]))."\\".$name);
        $namespaceTest    = $this->getNamespace(ucfirst($splittedName[0])."\\".ucfirst(Str::camel("Tests"))."\\".$name);

        $content = $this->replaceTokens($stub, [
            "{PACKAGE_NAME}",
            "{PACKAGE_DESCRIPTION}",
            "{EMAIL}",
            "{NAME}",
            "{LARAVEL_VERSION}",
            "{NAMESPACE}",
            "{NAMESPACE_TESTS}",
            "{PROVIDER}"
        ], [
            $this->packageName,
            $this->packageDescription,
            $this->packageCreatorEmail,
            $this->packageCreatorName,
            implode("|", $this->packageLaravelVersions),
            str_replace("\\","\\\\",$namespace),
            str_replace("\\","\\\\",$namespaceTest),
            $name
        ]);

        $this->makeDirectory($path);
        $this->files->put($path, $stub);
        $success = File::exists($path);

        return $success;

    }

    /**
     * Generate composer.json file.
     *
     * @return bool
     */
    protected function generatePhpUnitFile():bool {

        $success = false;
        $path = $this->getPath($this->packageName."/phpunit.xml");
        $stub = $this->files->get($this->getStubFilePath("phpunit"));

        $content = $this->replaceTokens($stub, [
            "{PACKAGE_NAME}"
        ], [
            $this->packageName
        ]);

        $this->makeDirectory($path);
        $this->files->put($path, $stub);
        $success = File::exists($path);

        return $success;

    }
    
    /**
     * Create an empty folder with a gitignore file in it
     *
     * @param  mixed $folder
     * @return bool
     */
    protected function generateEmptyFolder(String $folder): bool {

        $path = $this->getPath("$this->packageName/${folder}/.gitignore");
        $stub = $this->files->get($this->getStubFilePath("gitignore"));
        $this->makeDirectory($path);
        $this->files->put($path, $stub);
        $success = File::exists($path);

        return $success;

    }
    
    /**
     * Generate the required service provider
     *
     * @return bool
     */
    protected function generateServiceProvider(): bool {

        $splittedName = explode("/", $this->packageName);
        $name         = ucfirst(Str::camel($splittedName[sizeOf($splittedName) - 1]."ServiceProvider"));

        $path           = $this->getPath("$this->packageName/src/${name}.php");

        $this->files->put($path, $this->sortImports($this->buildClassCustom($name, 'ServiceProvider', [
            'DummyNamespace'
        ], [
            $this->getNamespace(ucfirst($splittedName[0])."\\".ucfirst(Str::camel($splittedName[1]))."\\".$name)
        ])));

        $success = File::exists($path);

        return $success; 

    }

    protected function generateTestCase(): bool {

        $splittedName = explode("/", $this->packageName);
        $name         = ucfirst(Str::camel($splittedName[sizeOf($splittedName) - 1]."TestCase"));
        $path         = $this->getPath("$this->packageName/tests/TestCase.php");

        $namespaceTest    = $this->getNamespace(ucfirst($splittedName[0])."\\".ucfirst(Str::camel("Tests"))."\\".$name);


        $this->files->put($path, $this->sortImports($this->buildClassCustom($name, 'TestCase', [
            '{PACKAGE_PROVIDER_WITHNAMESPACE}',
            '{PACKAGE_PROVIDER}',
            '{NAMESPACE_TESTS}'
        ], [
            $this->getNamespace(ucfirst($splittedName[0])."\\".ucfirst(Str::camel($splittedName[1]))."\\".$name)."\\".ucfirst(Str::camel($splittedName[sizeOf($splittedName) - 1]."ServiceProvider")),
            ucfirst(Str::camel($splittedName[sizeOf($splittedName) - 1]."ServiceProvider")),
            $namespaceTest,
        ])));

        $success = File::exists($path);

        return $success; 

    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClassCustom(String $name, String $stubname, array $placeholder = [], array $replaceWith = []) {
        $stub = $this->files->get($this->getStubFilePath($stubname));
        return $this->replaceNamespaceCustom($stub, $placeholder, $replaceWith)->replaceClass($stub, $name);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStubFilePath(String $stubname):String {
        return $this->getStub()."${stubname}.stub";
    }

    protected function replaceNamespaceCustom(&$stub, array $placeholder = [], array $replaceWith = []) {
        $stub = str_replace(
            $placeholder,
            $replaceWith,
            $stub
        );

        return $this;
    }

    protected function replaceTokens(&$stub, array $placeholder = [], array $replaceWith = []) {
        $stub = str_replace(
            $placeholder,
            $replaceWith,
            $stub
        );

        return $this;
    }
    
    /**
     * Modify the composer.json of your laravel installation and add the requirement to it.
     *
     * @return void
     */
    protected function updateComposer():void {

        $composerJson = json_decode(File::get(base_path("composer.json")), true);

        if (isset($composerJson['repositories']) == false){
            $composerJson['repositories'] = [];
        }

        $composerJson['require'][$this->packageName] = "dev-master";

        $composerJson['repositories'][$this->packageName] = [
            'type' => 'path',
            'url'  => config('package-maker.path' , 'packages')."/".$this->packageName,
            'options' => [
                "symlink" => true
            ]
        ];

        File::put(base_path("composer.json"), json_encode($composerJson, JSON_PRETTY_PRINT));

        shell_exec("composer require $this->packageName");

        // Execute composer install in the package folder
        shell_exec("cd $this->packagePath && composer install");

    }

}
