<?php

namespace Selonia\TranslationBundle\Command;

use Selonia\TranslationBundle\Manager\FileInterface;
use Selonia\TranslationBundle\Storage\StorageInterface;
use Selonia\TranslationBundle\Translation\Exporter\ExporterCollector;
use Selonia\TranslationBundle\Translation\TranslatorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class ExportTranslationsCommand extends Command
{
    /**
     * @var SymfonyStyle
     */
    protected $io;
    /**
     * @var InputInterface
     */
    private $input;
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var FileSystem
     */
    private $filesystem;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    private $exporter;

    public function __construct(string $projectDir, FileSystem $filesystem, StorageInterface $storage, TranslatorInterface $translator, ExporterCollector $exporter)
    {
        parent::__construct();
        $this->projectDir = $projectDir;
        $this->filesystem = $filesystem;
        $this->storage = $storage;
        $this->translator = $translator;
        $this->exporter = $exporter;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('selonia:translation:export');
        $this->setDescription('Export translations from the database to files.');
        $this->addOption('locales', 'l', InputOption::VALUE_OPTIONAL, 'Only export files for given locales. e.g. "--locales=en,de"', null);
        $this->addOption('domains', 'd', InputOption::VALUE_OPTIONAL, 'Only export files for given domains. e.g. "--domains=messages,validators"', null);
        $this->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Force the output format.', null);
        $this->addOption('override', 'o', InputOption::VALUE_NONE, 'Only export modified phrases (app/Resources/translations are exported fully anyway)');
        $this->addOption('export-path', 'p', InputOption::VALUE_REQUIRED, 'Export files to given path.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);
        $filesToExport = $this->getFilesToExport();
        if (count($filesToExport) > 0) {
            foreach ($filesToExport as $file) {
                $this->exportFile($file);
            }
        } else {
            $this->io->text('<comment>No translation\'s files in the database.</comment>');
        }
    }

    /**
     * @return array
     */
    protected function getFilesToExport()
    {
        $locales = $this->input->getOption('locales') ? explode(',', $this->input->getOption('locales')) : [];
        $domains = $this->input->getOption('domains') ? explode(',', $this->input->getOption('domains')) : [];

        return $this->storage->getFilesByLocalesAndDomains($locales, $domains);
    }

    /**
     * @param FileInterface $file
     */
    protected function exportFile(FileInterface $file)
    {
        $rootDir = $this->input->getOption('export-path') ?: $this->projectDir;
        $rootDir = rtrim($rootDir, \DIRECTORY_SEPARATOR);
        $this->io->text(sprintf('<info># Exporting "%s/%s":</info>', $file->getPath(), $file->getName()));
        $override = $this->input->getOption('override');
        if (!$this->input->getOption('export-path')) {
            if ($override) {
                $onlyUpdated = ('Resources/translations' !== $file->getPath());
            } else {
                $onlyUpdated = (false !== strpos($file->getPath(), 'vendor/'));
            }
        } else {
            $onlyUpdated = !$override;
        }
        $translations = $this->storage->getTranslationsFromFile($file, $onlyUpdated);
        if (count($translations) < 1) {
            $this->io->text('<comment>No translations to export.</comment>');

            return;
        }
        $format = $this->input->getOption('format') ? $this->input->getOption('format') : $file->getExtension();

        if (false !== strpos($file->getPath(), 'vendor/') || $override) {
            $outputPath = sprintf('%s/translations', $rootDir);
        } else {
            $outputPath = sprintf('%s/%s', $rootDir, $file->getPath());
        }
        $this->io->text(sprintf('<info># OutputPath "%s":</info>', $outputPath));

        if ($this->input->getOption('export-path')) {
            /** @var Filesystem $fs */
            $fs = $this->filesystem;
            if (!$fs->exists($outputPath)) {
                $fs->mkdir($outputPath);
            }
        }
        $outputFile = sprintf('%s/%s.%s.%s', $outputPath, $file->getDomain(), $file->getLocale(), $format);
        $this->io->text(sprintf('<info># OutputFile "%s":</info>', $outputFile));
        $translations = $this->mergeExistingTranslations($file, $outputFile, $translations);
        $this->doExport($outputFile, $translations, $format);
    }

    /**
     * @param FileInterface $file
     * @param string $outputFile
     * @param array $translations
     *
     * @return array
     */
    protected function mergeExistingTranslations($file, $outputFile, $translations)
    {
        if (file_exists($outputFile)) {
            $extension = pathinfo($outputFile, PATHINFO_EXTENSION);
            $messageCatalogue = $this->translator->getLoader($extension)
                ->load($outputFile, $file->getLocale(), $file->getDomain()
                    ->getName());
            $translations = array_merge($messageCatalogue->all($file->getDomain()
                ->getName()), $translations);
        }

        return $translations;
    }

    /**
     * @param string $outputFile
     * @param array $translations
     * @param string $format
     */
    protected function doExport($outputFile, $translations, $format)
    {
        $this->io->text(sprintf('<comment>Output file: %s</comment>', $outputFile));
        $this->output->write(sprintf('<comment>%d translations to export: </comment>', count($translations)));
        try {
            $exported = $this->exporter->export($format, $outputFile, $translations);
            $this->io->text($exported ? '<comment>success</comment>' : '<error>fail</error>');
        } catch (\Exception $e) {
            $this->io->text(sprintf('<error>"%s"</error>', $e->getMessage()));
        }
    }
}
