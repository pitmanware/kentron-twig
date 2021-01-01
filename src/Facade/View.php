<?php

namespace Kentron\Facade;

use Kentron\Service\File;
use Kentron\Service\Type;

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

class View
{
    protected $baseDirectory = "";
    protected $frame = "index.twig";

    private $title = "";
    private $data = [];
    private $scripts = [];
    private $styles = [];

    private function __construct (?string $frame = null)
    {
        $this->includeMasterScripts();
        $this->includeMasterStyles();

        if (is_string($frame)) {
            $this->setFrame($frame);
        }
    }

    /**
     * Setters
     */

    /**
     * Set the base directory for Twig
     *
     * @param string $directory The path of the directory to be used
     *
     * @throws InvalidArgumentException
     */
    public function setDirectory (string $directory): void
    {
        $this->baseDirectory = File::getRealPath($directory);
    }

    public function setTitle (string $title): void
    {
        $this->title = $title;
    }

    public function setFrame (string $framePath): void
    {
        $this->frame = $framePath;
    }

    public function setData ($data): void
    {
        $this->data = $data;
    }

    public function setAlerts (array $alerts): void
    {
        $this->addData("alerts", $alerts);
    }

    /**
     * Adders
     */

    public function addData ($data, $value = null): void
    {
        if (is_null($value)) {
            $this->data += Type::castToArray($data);
        }
        else {
            $this->data[Type::castToString($data)] = $value;
        }
    }

    public function addScripts ($scripts): void
    {
        $scripts = Type::castToArray($scripts);

        foreach ($scripts as $script) if (is_string($script)) {
            $this->scripts[] = $script;
        }
    }

    public function addStyles (array $styles): void
    {
        $styles = Type::castToArray($styles);

        foreach ($styles as $style) if (is_string($style)) {
            $this->scripts[] = $style;
        }
    }

    /**
     * Getters
     */

    public function getProperties (): array
    {
        return [
            "meta" => [
                "title" => $this->title,
            ],
            "scripts" => $this->scripts,
            "styles" => $this->styles,
            "data" => $this->data
        ];
    }

    /**
     * Helpers
     */

    public function includeMasterScripts (bool $include = true): void
    {
        $this->data["include_master_scripts"] = $include;
    }

    public function includeMasterStyles (bool $include = true): void
    {
        $this->data["include_master_styles"] = $include;
    }

    public function render (array $data = []): void
    {
        echo $this->capture($data);
    }

    public function capture (array $data = []): string
    {
        $this->validate();
        $this->addData($data);

        return $this->init()->render(
            $this->frame,
            $this->getProperties()
        );
    }

    public function removeScripts (): void
    {
        $this->scripts = [];
    }

    public function removeStyles (): void
    {
        $this->styles = [];
    }

    /**
     * Private methods
     */

    private function validate (): void
    {
        if (!File::isDir($this->baseDirectory)) {
            throw new \InvalidArgumentException("{$this->baseDirectory} is not a directory");
        }
        else if (!File::isReadable($this->baseDirectory)) {
            throw new \InvalidArgumentException("{$this->baseDirectory} is not readable");
        }

        $framePath = $this->baseDirectory . $this->frame;
        if (!File::isValidFile($framePath)) {
            throw new \InvalidArgumentException("{$framePath} is not a valid file");
        }
    }

    private function init (): Environment
    {
        $twigLoader = new FilesystemLoader($this->baseDirectory);
        return new Environment($twigLoader, []);
    }
}
