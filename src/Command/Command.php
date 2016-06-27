<?php

namespace CraftCli\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Craft\ConsoleApp as Craft;

/**
 * Based on Illuminate\Console\Command
 */
abstract class Command extends BaseCommand implements NeedsCraftInterface
{
    /**
     * The input interface implementation.
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * The output interface implementation.
     *
     * @var \Symfony\Component\Console\Style\StyleInterface
     */
    protected $output;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description;

    /**
     * Craft app instance
     * @var \Craft\ConsoleApp
     */
    protected $craft;

    /**
     * Craft app path
     * @var string
     */
    protected $appPath;

    /**
     * Craft base path
     * @var string
     */
    protected $basePath;

    /**
     * Craft config path
     * @var string
     */
    protected $configPath;

    /**
     * Craft storage path
     * @var string
     */
    protected $storagePath;

    /**
     * Craft plugins path
     * @var string
     */
    protected $pluginsPath;

    /**
     * Craft templates path
     * @var string
     */
    protected $templatesPath;

    /**
     * Craft translations path
     * @var string
     */
    protected $translationsPath;

    /**
     * Whether to show the command's duration after the command finishes
     * @var boolean
     */
    protected $showsDuration = false;

    /**
     * Specify the arguments and options on the command.
     *
     * @return void
     */
    protected function configure()
    {
        if ($this->name) {
            $this->setName($this->name);
        }

        if ($this->description) {
            $this->setDescription($this->description);
        }

        // add default options
        $this->addOption('environment', null, InputOption::VALUE_REQUIRED, 'Craft environment name');

        // We will loop through all of the arguments and options for the command and
        // set them all on the base command instance. This specifies what can get
        // passed into these commands as "parameters" to control the execution.
        foreach ($this->getArguments() as $arguments) {
            call_user_func_array([$this, 'addArgument'], $arguments);
        }

        foreach ($this->getOptions() as $options) {
            call_user_func_array([$this, 'addOption'], $options);
        }
    }

    /**
     * Run the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;

        $this->output = new SymfonyStyle($input, $output);

        return parent::run($input, $output);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    protected function fire()
    {
    }

    /**
     * Execute the console command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);

        $return = $this->fire();

        if ($this->showsDuration) {
            $output->writeln(sprintf('<info>Took %d seconds.</info>', microtime(true) - $start));
        }

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function setCraft(Craft $craft)
    {
        $this->craft = $craft;
    }

    /**
     * {@inheritdoc}
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function setAppPath($path)
    {
        $this->appPath = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function setBasePath($path)
    {
        $this->basePath = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfigPath($path)
    {
        $this->configPath = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function setStoragePath($path)
    {
        $this->storagePath = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function setPluginsPath($path)
    {
        $this->pluginsPath = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function setTemplatesPath($path)
    {
        $this->templatesPath = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function setTranslationsPath($path)
    {
        $this->translationsPath = $path;
    }

    /**
     * Get the value of a command argument.
     *
     * @param  string  $key
     * @return string|array
     */
    public function argument($key = null)
    {
        if (is_null($key)) {
            return $this->input->getArguments();
        }

        return $this->input->getArgument($key);
    }

    /**
     * Get the value of a command option.
     *
     * @param  string  $key
     * @return string|array
     */
    public function option($key = null)
    {
        if (is_null($key)) {
            return $this->input->getOptions();
        }

        return $this->input->getOption($key);
    }

    /**
     * Confirm a question with the user.
     *
     * @param  string  $question
     * @param  bool    $default
     * @return bool
     */
    public function confirm($question, $default = false)
    {
        return $this->output->confirm($question, $default);
    }

    /**
     * Prompt the user for input.
     *
     * @param  string  $question
     * @param  string  $default
     * @return string
     */
    public function ask($question, $default = null)
    {
        return $this->output->ask($question, $default);
    }

    /**
     * Prompt the user for input with auto completion.
     *
     * @param  string  $question
     * @param  array   $choices
     * @param  string  $default
     * @return string
     */
    public function anticipate($question, array $choices, $default = null)
    {
        return $this->askWithCompletion($question, $choices, $default);
    }

    /**
     * Prompt the user for input with auto completion.
     *
     * @param  string  $question
     * @param  array   $choices
     * @param  string  $default
     * @return string
     */
    public function askWithCompletion($question, array $choices, $default = null)
    {
        $question = new Question($question, $default);

        $question->setAutocompleterValues($choices);

        return $this->output->askQuestion($question);
    }

    /**
     * Prompt the user for input but hide the answer from the console.
     *
     * @param  string  $question
     * @param  bool    $fallback
     * @return string
     */
    public function secret($question, $fallback = true)
    {
        $question = new Question($question);

        $question->setHidden(true)->setHiddenFallback($fallback);

        return $this->output->askQuestion($question);
    }

    /**
     * Give the user a single choice from an array of answers.
     *
     * @param  string  $question
     * @param  array   $choices
     * @param  string  $default
     * @param  mixed   $attempts
     * @param  bool    $multiple
     * @return bool
     */
    public function choice($question, array $choices, $default = null, $attempts = null, $multiple = null)
    {
        $question = new ChoiceQuestion($question, $choices, $default);

        $question->setMaxAttempts($attempts)->setMultiselect($multiple);

        return $this->output->askQuestion($question);
    }

    /**
     * Format input to textual table.
     *
     * @param  array   $headers
     * @param  array   $rows
     * @param  string  $style
     * @return void
     */
    public function table(array $headers, array $rows, $style = 'default')
    {
        $table = new Table($this->output);

        $table->setHeaders($headers)->setRows($rows)->setStyle($style)->render();
    }

    /**
     * Write a string as information output.
     *
     * @param  string  $string
     * @return void
     */
    public function info($string)
    {
        $this->output->writeln("<info>$string</info>");
    }

    /**
     * Write a string as standard output.
     *
     * @param  string  $string
     * @return void
     */
    public function line($string)
    {
        $this->output->writeln($string);
    }

    /**
     * Write a string as comment output.
     *
     * @param  string  $string
     * @return void
     */
    public function comment($string)
    {
        $this->output->writeln("<comment>$string</comment>");
    }

    /**
     * Write a string as question output.
     *
     * @param  string  $string
     * @return void
     */
    public function question($string)
    {
        $this->output->writeln("<question>$string</question>");
    }

    /**
     * Write a string as error output.
     *
     * @param  string  $string
     * @return void
     */
    public function error($string)
    {
        $this->output->writeln("<error>$string</error>");
    }

    /**
     * Write a string as warning output.
     *
     * @param  string  $string
     * @return void
     */
    public function warn($string)
    {
        $this->output->writeln("<warning>$string</warning>");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }

    /**
     * Useful for supressing log messages
     * @param  callable $callback
     * @return mixed
     */
    protected function suppressOutput(callable $callback)
    {
        ob_start();

        $return = call_user_func($callback);

        ob_end_clean();

        return $return;
    }
}
