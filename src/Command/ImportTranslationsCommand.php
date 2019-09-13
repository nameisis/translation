<?php

namespace Selonia\TranslationBundle\Command;

use InvalidArgumentException;
use LogicException;
use Selonia\TranslationBundle\Manager\LocaleManagerInterface;
use Selonia\TranslationBundle\Translation\Importer\FileImporter;
use Selonia\TranslationBundle\Translation\TranslatorInterface as NameisisTranslator;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImportTranslationsCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    protected $io;
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var InputInterface
     */
    private $input;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var LocaleManagerInterface
     */
    private $localeManager;
    /**
     * @var FileImporter
     */
    private $importerFile;
    /**
     * @var array
     */
    private $formats;
    /**
     * @var string
     */
    private $projectDir;
    /**
     * @var array
     */
    private $bundles;

    public function __construct(TranslatorInterface $translator, LocaleManagerInterface $localeManager, FileImporter $importerFile, NameisisTranslator $trans, string $projectDir, array $bundles)
    {
        parent::__construct();
        $this->translator = $translator;
        $this->localeManager = $localeManager;
        $this->importerFile = $importerFile;
        $this->formats = $trans->getFormats();
        $this->projectDir = $projectDir;
        $this->bundles = $bundles;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('selonia:translation:import');
        $this->setDescription('Import all translations from flat files (xliff, yml, php) into the database.');
        $this->addOption('cache-clear', 'c', InputOption::VALUE_NONE, 'Remove translations cache files for managed locales.');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force import, replace database content.');
        $this->addOption('globals', 'g', InputOption::VALUE_NONE, 'Import only globals (app/Resources/translations.');
        $this->addOption('locales', 'l', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Import only for these locales, instead of using the managed locales.');
        $this->addOption('domains', 'd', InputOption::VALUE_OPTIONAL, 'Only imports files for given domains (comma separated).');
        $this->addOption('case-insensitive', 'i', InputOption::VALUE_NONE, 'Process translation as lower case to avoid duplicate entry errors.');
        $this->addOption('merge', 'm', InputOption::VALUE_NONE, 'Merge translation (use ones with latest updatedAt date).');
        $this->addOption('import-path', 'p', InputOption::VALUE_REQUIRED, 'Search for translations at given path');
        $this->addOption('only-vendors', 'o', InputOption::VALUE_NONE, 'Import from vendors only');
        $this->addArgument('bundle', InputArgument::OPTIONAL, 'Import translations for this specific bundle.', null);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->checkOptions();
        $this->io = new SymfonyStyle($input, $output);
        $locales = $this->input->getOption('locales');
        if (empty($locales)) {
            $locales = $this->localeManager->getLocales();
        }
        $domains = $input->getOption('domains') ? explode(',', $input->getOption('domains')) : [];
        if ($bundleName = $this->input->getArgument('bundle')) {
            $bundle = $this->getBundle($bundleName);
            $this->importBundleTranslationFiles($bundle, $locales, $domains, (bool)$this->input->getOption('globals'));
        } elseif (!$this->input->getOption('import-path')) {
            if (!$this->input->getOption('merge') && !$this->input->getOption('only-vendors')) {
                $this->io->text('<info>*** Importing application translation files ***</info>');
                $this->importAppTranslationFiles($locales, $domains);
            }
            if ($this->input->getOption('globals')) {
                $this->importBundlesTranslationFiles($locales, $domains, true);
            }
            if (!$this->input->getOption('globals')) {
                $this->io->text('<info>*** Importing bundles translation files ***</info>');
                $this->importBundlesTranslationFiles($locales, $domains);
                $this->io->text('<info>*** Importing component translation files ***</info>');
                $this->importComponentTranslationFiles($locales, $domains);
            }
            if ($this->input->getOption('merge')) {
                $this->io->text('<info>*** Importing application translation files ***</info>');
                $this->importAppTranslationFiles($locales, $domains);
            }
        }
        $importPath = $this->input->getOption('import-path');
        if (!empty($importPath)) {
            $this->io->text(sprintf('<info>*** Importing translations from path "%s" ***</info>', $importPath));
            $this->importTranslationFilesFromPath($importPath, $locales, $domains);
        }
        if ($this->input->getOption('cache-clear')) {
            $this->io->text('<info>Removing translations cache files ...</info>');
            $this->translator->removeLocalesCacheFiles($locales);
        }
    }

    protected function checkOptions()
    {
        if ($this->input->getOption('only-vendors') && $this->input->getOption('globals')) {
            throw new LogicException('You cannot use "globals" and "only-vendors" at the same time.');
        }
        if ($this->input->getOption('import-path') && ($this->input->getOption('globals') || $this->input->getOption('merge') || $this->input->getOption('only-vendors'))) {
            throw new LogicException('You cannot use "globals", "merge" or "only-vendors" and "import-path" at the same time.');
        }
    }

    private function getBundle($name)
    {
        if (!isset($this->bundles[$name])) {
            $class = get_class($this);
            $class = strpos($class, 'c') === 0 && 0 === strpos($class, "class@anonymous\0") ? get_parent_class($class).'@anonymous' : $class;

            throw new InvalidArgumentException(sprintf('Bundle "%s" does not exist or it is not enabled. Maybe you forgot to add it in the registerBundles() method of your %s.php file?', $name, $class));
        }

        return $this->bundles[$name];
    }

    /**
     * @param BundleInterface $bundle
     * @param array $locales
     * @param array $domains
     * @param boolean $global
     */
    protected function importBundleTranslationFiles(BundleInterface $bundle, $locales, $domains, $global = false)
    {
        $path = $bundle->getPath();
        if ($global) {
            $path = $this->projectDir.'/Resources/'.$bundle->getName().'/translations';
            $this->io->text('<info>*** Importing '.$bundle->getName().'`s translation files from '.$path.' ***</info>');
        }
        $this->io->text(sprintf('<info># %s:</info>', $bundle->getName()));
        $finder = $this->findTranslationsFiles($path, $locales, $domains);
        $this->importTranslationFiles($finder);
    }

    /**
     * @param $path
     * @param array $locales
     * @param array $domains
     * @param bool $autocompletePath
     *
     * @return Finder|null
     */
    protected function findTranslationsFiles($path, array $locales, array $domains, $autocompletePath = true)
    {
        $finder = null;
        if (0 === stripos(PHP_OS, 'win')) {
            $path = preg_replace('#'.preg_quote(DIRECTORY_SEPARATOR, '#').'#', '/', $path);
        }
        if (true === $autocompletePath) {
            $dir = (0 === strpos($path, $this->projectDir.'/Resources')) ? $path : $path.'/Resources/translations';
        } else {
            $dir = $path;
        }
        $this->io->text('<info>*** Using dir '.$dir.' to lookup translation files. ***</info>');
        if (is_dir($dir)) {
            $finder = new Finder();
            $finder->files()
                ->name($this->getFileNamePattern($locales, $domains))
                ->in($dir);
        }

        return (null !== $finder && $finder->count() > 0) ? $finder : null;
    }

    /**
     * @param array $locales
     * @param array $domains
     *
     * @return string
     */
    protected function getFileNamePattern(array $locales, array $domains = [])
    {
        if (!isset($this->formats['yml'])) {
            $this->formats[] = 'yml';
        }
        if (!isset($this->formats['xliff'])) {
            $this->formats[] = 'xliff';
        }
        if (!empty($domains)) {
            $regex = sprintf('/((%s)\.(%s)\.(%s))/', implode('|', $domains), implode('|', $locales), implode('|', $this->formats));
        } else {
            $regex = sprintf('/(.*\.(%s)\.(%s))/', implode('|', $locales), implode('|', $this->formats));
        }

        return $regex;
    }

    /**
     * @param Finder $finder
     */
    protected function importTranslationFiles($finder)
    {
        if (!$finder instanceof Finder) {
            $this->io->text('No file to import');

            return;
        }
        $importer = $this->importerFile;
        $importer->setCaseInsensitiveInsert($this->input->getOption('case-insensitive'));
        foreach ($finder as $file) {
            $this->io->text(sprintf('Importing <comment>"%s"</comment> ... ', $file->getPathname()));
            $number = $importer->import($file, $this->input->getOption('force'), $this->input->getOption('merge'));
            $this->io->text(sprintf('%d translations', $number));
            $skipped = $importer->getSkippedKeys();
            if (count($skipped) > 0) {
                $this->io->text(sprintf('    <error>[!]</error> The following keys has been skipped: "%s".', implode('", "', $skipped)));
            }
        }
    }

    /**
     * @param array $locales
     * @param array $domains
     */
    protected function importAppTranslationFiles(array $locales, array $domains)
    {
        $translationPath = $this->projectDir.'/translations';
        $finder = $this->findTranslationsFiles($translationPath, $locales, $domains, false);
        $this->importTranslationFiles($finder);
    }

    /**
     * @param array $locales
     * @param array $domains
     * @param boolean $global
     */
    protected function importBundlesTranslationFiles(array $locales, array $domains, $global = false)
    {
        foreach ($this->bundles as $bundle) {
            $this->importBundleTranslationFiles(new $bundle(), $locales, $domains, $global);
        }
    }

    /**
     * @param array $locales
     * @param array $domains
     *
     * @throws ReflectionException
     */
    protected function importComponentTranslationFiles(array $locales, array $domains)
    {
        $classes = [
            Validation::class => '/Resources/translations',
            Form::class => '/Resources/translations',
            AuthenticationException::class => '/../Resources/translations',
        ];
        $dirs = [];
        foreach ($classes as $namespace => $translationDir) {
            $reflection = new ReflectionClass($namespace);
            $dirs[] = dirname($reflection->getFileName()).$translationDir;
        }
        $finder = new Finder();
        $finder->files()
            ->name($this->getFileNamePattern($locales, $domains))
            ->in($dirs);
        $this->importTranslationFiles($finder->count() > 0 ? $finder : null);
    }

    /**
     * @param string $path
     * @param array $locales
     * @param array $domains
     */
    protected function importTranslationFilesFromPath($path, array $locales, array $domains)
    {
        $finder = $this->findTranslationsFiles($path, $locales, $domains, false);
        $this->importTranslationFiles($finder);
    }
}
