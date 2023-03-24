<?php

declare(strict_types=1);

namespace CarmeloSantana\Prompts;

use StringTemplate\Engine;

class Prompts
{
    public object $engine;

    public array $lists = [];

    public array $output = [];

    public array $restore = [];

    public array $options = [
        'count' => 1,
        'default_list' => true,
        'duplicate_delimiter' => ' ',
        'echo' => true,
        'exhaust_list' => false,
        'library_path' => '',
        'save_to_file' => true,
        'save_to_file_path' => '',
        'template' => '',
    ];

    public function __construct()
    {
        // Set dynamic options
        $this->setOption('library_path', dirname(__DIR__) . '/library');
        $this->setOption('save_to_file_path', getcwd());

        // Setup renderer
        $this->engine = new Engine();
    }

    /**
     * Add custom lists to the default lists.
     *
     * @param  array $lists
     * @return void
     */
    public function addLists(array $lists)
    {
        // Merge custom lists with default lists
        $this->lists = array_merge($this->lists, $lists);

        // Save a restore list
        $this->restore = $this->lists;
    }

    /**
     * Add a list to the default lists.
     *
     * @param  string $name
     * @param  array $list
     * @return void
     */
    public function addList(string $name, array $list)
    {
        // Add list to default lists
        $this->lists[$name] = $list;

        // Save a restore list
        $this->restore[$name] = $list;
    }

    /**
     * Populates default lists with text files from /library.
     *
     * @return void
     */
    public function buildDefaultLists()
    {
        // Get all text files from /library
        $lists = glob($this->getOption('library_path') . '/*.txt');

        foreach ($lists as $list) {
            // check if list is empty
            if (filesize($list) === 0) {
                continue;
            }
            $listName = basename($list, '.txt');
            $this->lists[$listName] = file($list, FILE_IGNORE_NEW_LINES);
            sort($this->lists[$listName]);
        }
    }

    public function dictionary($prompt)
    {
        // preg_match_all find all {variables} in $this->getOption('template')
        preg_match_all('/{([a-z0-9+-_ ]+)}/', $prompt, $matches);

        $variables = $matches[1];

        $dictionary = [];

        foreach ($variables as $key) {
            if (!isset($this->lists[$key])) {
                if (strpos($key, $this->getOption('duplicate_delimiter')) !== false) {
                    $this->addList($key, $this->getList(explode($this->getOption('duplicate_delimiter'), $key)[0]));
                } else if (strpos($key, '+') !== false) {
                    $keys = explode('+', $key);
                    $this->addList($key, array_merge($this->restore[$keys[0]], $this->restore[$keys[1]]));
                }
            } else if (empty($this->lists[$key])) {
                $this->lists[$key] = $this->restore[$key];
            }

            $dictionary[$key] = $this->getRandomItem($key);
        }

        return $dictionary;
    }

    public function echo(string $string)
    {
        if ($this->getOption('echo')) {
            echo $string . PHP_EOL;
        }
    }

    // While loop iterate how many times.
    public function generate(): array
    {
        // Save a restore list.
        $this->restore = $this->lists;

        $i = 0;
        $out = [];
        while ($i < $this->getOption('count')) {
            $prompt = $this->getPrompt();
            $out[$prompt[0]] = $prompt[1];
            $i++;

            // If we're not supposed to exhaust the list, restore it.
            if (!$this->getOption('exhaust_list')) {
                $this->lists = $this->restore;
            }
        }

        return $out;
    }

    public function getList(string $name)
    {
        return $this->lists[$name] ?? $this->restore[$name];
    }

    public function getOption(string $option, $default = false)
    {
        return $this->options[$option] ?? $default;
    }

    public function getPrompt()
    {
        $prompt = $this->getOption('template');

        // Continue building prompt till all variables are rendered.
        $i = 0;
        while (preg_match('/\{.*?\}/', $prompt) and $i < 10) {
            // Render the prompt.
            $prompt = $this->engine->render($prompt, $this->dictionary($prompt));
            $i++;
        }

        // Remove extra spaces
        $prompt = preg_replace('/\s+/', ' ', $prompt);

        // Remove extra space + comma
        $prompt = preg_replace('/\s+,/', '', $prompt);

        // Tokenize array, make a hash to compare
        $prompt = explode(', ', $prompt);

        // Remove duplicates
        $hash = $prompt = array_unique($prompt);

        // Sort
        sort($hash);
        $hash = md5(serialize($hash));

        // Return $prompt to string
        $prompt = implode(', ', $prompt);

        // Trim excess commas and spaces.
        $prompt = trim($prompt, ', ');

        // remove extra spaces
        return [$hash, $prompt];
    }

    // Get and remove random item from list
    public function getRandomItem($key)
    {
        // If empty restore list
        if (empty($this->lists[$key])) {
            $this->lists[$key] = $this->restore[$key];
        }

        // Shuffle the array
        shuffle($this->lists[$key]);

        // Get and remove the first item.
        return array_shift($this->lists[$key]);
    }

    public function run()
    {
        // Do we need to build default lists?
        if ($this->getOption('default_list')) {
            $this->buildDefaultLists();
        }

        // Run prompt
        $this->output = $this->generate();

        // Out
        $this->echo(array_values($this->output)[(count($this->output) - 1)]);
        $this->echo(count($this->output) . ' prompts generated');

        // Save to file
        if ($this->getOption('save_to_file')) {
            $this->saveToFile();
        }
    }

    public function saveToFile()
    {
        $filename = 'prompts-' . time() . '.txt';
        $filepath = $this->getOption('save_to_file_path') . DIRECTORY_SEPARATOR . $filename;
        sort($this->output);
        file_put_contents($filepath, implode(PHP_EOL, $this->output));
        $this->echo('✔️ Saved to ' . $filename . PHP_EOL);
    }

    public function setCount(int $count = 1)
    {
        $this->setOption('count', $count);
    }

    public function setOption(string $key, $value)
    {
        $this->options[$key] = $value;
    }

    public function setTemplate(string $template)
    {
        $this->setOption('template', $template);
    }
}
