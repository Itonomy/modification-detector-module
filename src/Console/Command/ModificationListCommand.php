<?php

namespace Itonomy\ModificationDetector\Console\Command;

use Itonomy\ModificationDetector\Model\ModificationDetector;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Config\ScopeInterface;
use Magento\Framework\Console\Cli;
use Magento\Framework\Module\Manager;
use Magento\Setup\Console\Style\MagentoStyle;

/**
 * Class PluginListCommand
 */
class ModificationListCommand extends Command
{
    /**
     * @var ScopeInterface
     */
    private $scope;

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var ModificationDetector
     */
    private $modificationDetector;

    /**
     * @var string[]
     */
    private $allowedTypes = ['plugin', 'preference','all'];

    /**
     * ModificationListCommand constructor.
     * @param ScopeInterface $scope
     * @param Manager $moduleManager
     * @param ModificationDetector $modificationDetector
     * @param string|null $name
     */
    public function __construct(
        ScopeInterface $scope,
        Manager $moduleManager,
        ModificationDetector $modificationDetector,
        ?string $name = null
    )
    {
        $this->scope = $scope;
        $this->modificationDetector = $modificationDetector;
        $this->moduleManager = $moduleManager;
        parent::__construct($name);
    }
    /**
     * @inheritDoc
     */
    public function configure()
    {
        $this->setName('dev:modification:list');
        $this->setDescription('Get the list of modifications installed in Magento');
        $this->addOption(
            'type',
            't',
            InputOption::VALUE_OPTIONAL,
            'Type of modification to lookup. Options: empty|plugin|preference|all'
        );
        $this->addOption(
            'filter',
            'f',
            InputOption::VALUE_OPTIONAL,
            'Search/Filter results by classname'
        );
        $this->addOption(
            'no-native',
            null,
            InputOption::VALUE_NONE,
            'Filters all plugins/preferences done by Magento itself'
        );
        $this->addOption(
            'summary',
            null,
            InputOption::VALUE_NONE,
            'Gives a summary of detected modifications'
        );
        $this->addOption(
            'detect-conflict',
            'd',
            InputOption::VALUE_NONE,
            'Gives a summary of detected modifications'
        );
    }
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null
     * @throws ReflectionException
     */
    public function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $style = new MagentoStyle($input, $output);

        $type = strtolower($input->getOption('type'));
        if ($type != null && !\in_array($input->getOption('type'), $this->allowedTypes, true)) {
            $style->error('Type "' . $input->getOption('type') . '" does not exist.');
            return Cli::RETURN_FAILURE;
        }
        $keyword = $input->getOption('filter') ?? '';
        if($keyword != null && strlen($keyword) < 3){
            $style->error('Keyword should be at least 3 characters long');
            return Cli::RETURN_FAILURE;
        }

        $hidenative = $input->getOption('no-native') ? 1 : 0;
        $this->modificationDetector->setHideNatives($hidenative);

        if($detectConflicts = $input->getOption('detect-conflict')){
            $this->generatePluginConflictList($style);
            return Cli::RETURN_SUCCESS;
        }

        if ($summary = $input->getOption('summary')){
            $this->printSummary($style);
            return Cli::RETURN_SUCCESS;
        }


        if(in_array($type,['plugin','all',''])) {
            $this->generatePluginList($style, $keyword);
        }

        if(in_array($type,['preference','all',''])) {
            $this->generatePreferenceList($style, $keyword);
        }

        $style->writeln('');
        $style->writeln('<info>Succesfully executed</info>');

        return Cli::RETURN_SUCCESS;
    }

    protected function generatePluginConflictList(MagentoStyle $style){

        $classArray = $this->modificationDetector->findPluginConflicts();

        foreach ($classArray as $className => $plugins){

            foreach ($plugins as $methodName => $method){
                if($method['potential_conflict'] == 1){
                    $style->title(sprintf($className));
                    $style->writeln('this method may be functioning incorrectly');
                    $style->table(array_keys($method['plugins'][0]),$method['plugins']);
                }
            }
        }

    }
    /**
     * @param $style
     * @param $keyword
     * @param $hideNative
     */
    protected function generatePluginList($style, $keyword){

        if ($keyword) {
            $list = $this->modificationDetector->getPluginByClassname($keyword);
        } else {
            $list = $this->modificationDetector->getPlugins();
        }

        $style->writeln('<info>Generating list of modifications</info>');

        if(count($list)) $style->writeln('<info>Plugins</info>');
            else $style->writeln('<info>No matching plugins found</info>');

        foreach ($list as $class => $modificationClasses){
            $style->title($class);
            foreach ($modificationClasses['classes'] as $modificationClass) {
                $style->writeln(sprintf('%s <-> %s', $class,$modificationClass));
            }
        }

    }

    /**
     * @param MagentoStyle $style
     * @param string $keyword
     */
    protected function generatePreferenceList($style, $keyword){
        if ($keyword) {
            $list = $this->modificationDetector->getPreferenceByClassname($keyword);
        } else {
            $list = $this->modificationDetector->getPreferences();
        }

        $style->writeln('<info>Generating list of modifications</info>');

        if(count($list)) $style->writeln('<info>Preferences</info>');
            else $style->writeln('<info>No matching preferences found</info>');

        foreach ($list as $class => $modificationClasses){
            $style->title($class);
            foreach ($modificationClasses['classes'] as $modificationClass) {
                $style->writeln(sprintf('%s <-> %s', $class,$modificationClass));
            }
        }
    }

    /**
     * @param MagentoStyle $style
     */
    protected function printSummary($style){

        $style->writeln('<info>generating: Modification Summary</info>');
        $allPlugins = $this->modificationDetector->getPlugins();
        $allPreferences = $this->modificationDetector->getPreferences();

        $pluggedClasses = 0;
        $preffedClasses = 0;

        $numOfPlugins = 0;
        $numOfPrefs = 0;

        foreach ($allPlugins as $plugin){
            $numOfPlugins += count($plugin['classes']);
            $pluggedClasses += 1;
        }

        foreach ($allPreferences as $preference){
            $numOfPrefs += count($preference['classes']);
            $preffedClasses += 1;
        }

        $style->writeln('Number of preferences: <info>'.$numOfPrefs.'</info> on <info>'.$preffedClasses.'</info> classes');
        $style->writeln('Number of plugins: <info>'.$numOfPlugins.'</info> on <info>'.$pluggedClasses.'</info> classes');
    }
}

