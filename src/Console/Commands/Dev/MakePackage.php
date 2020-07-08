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
        $this->packageDescription = $this->ask('Whats the purpose of this package?', 'New package');
        $this->packageCreatorName = $this->anticipate('Whats your name?', [$creatorName], '');
        $this->packageCreatorEmail = $this->anticipate('Whats you e-mail address?', [$creatorEmail], '');
        $this->packageLaravelVersions = $this->choice(
            'For which laravel versions do you want to create this plugin.',
            $laravelVersions,
            sizeOf($laravelVersions) - 1,
            $maxAttempts = null,
            $allowMultipleSelections = true
        );

        $this->generateReadMeFile() == false ? $this->error("[${name}] README file could not be created.") : $this->line("[${name}] README file has been created.");
        $this->generateChangelogFile() == false ? $this->error("[${name}] CHANGELOG file could not be created.") : $this->line("[${name}] CHANGELOG file has been created.");
        $this->generateComposerFile() == false ? $this->error("[${name}] composer.json file could not be created.") : $this->line("[${name}] composer.json file has been created.");
        $this->generatePhpUnitFile() == false ? $this->error("[${name}] phpunit.xml file could not be created.") : $this->line("[${name}] phpunit.xml file has been created.");
        $this->generateEmptyFolder("tests") === false ? $this->error("[${name}] test folder could not be created.") : $this->line("[${name}] test folder has been created.");
        $this->generateEmptyFolder("docs") === false ? $this->error("[${name}] docs folder could not be created.") : $this->line("[${name}] docs folder has been created.");

        $this->info("The package ${name} has been created.");

    }

    protected function getLaravelVersionLists(): array {

        $file = json_decode(File::get(__DIR__."/../../../../composer.json"), true);
        $laravel = data_get($file, 'require-dev.laravel/framework', '7.*|dev-master');
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

        $content = $this->replaceTokens($stub, [
            "{PACKAGE_NAME}",
            "{PACKAGE_DESCRIPTION}",
            "{EMAIL}",
            "{NAME}",
            "{LARAVEL_VERISON}"
        ], [
            $this->packageName,
            $this->packageDescription,
            $this->packageCreatorEmail,
            $this->packageCreatorName,
            implode("|", $this->packageLaravelVersions)
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
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClassCustom(String $name, String $stubname) {
        $stub = $this->files->get($this->getStubFilePath($stubname));
        return $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStubFilePath(String $stubname):String {
        return $this->getStub()."${stubname}.stub";
    }

    protected function replaceTokens(&$stub, array $placeholder = [], array $replaceWith = []) {
        $stub = str_replace(
            $placeholder,
            $replaceWith,
            $stub
        );

        return $this;
    }

}
