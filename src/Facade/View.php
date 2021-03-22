<?php

namespace Kentron\Facade;

use Kentron\Service\File;
use Kentron\Service\Type;

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

class View
{
    private $index = "index.twig";
    private $directory = "";
    private $frame = null;
    private $title = "";
    private $data = [];
    private $scripts = [];
    private $styles = [];

    public function __construct (?string $frame = null)
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
     * Set the base directory for the index file
     *
     * @param string $directory The absolute directory path
     *
     * @throws InvalidArgumentException
     */
    public function setDirectory (string $directory): void
    {
        $this->directory = File::getRealPath($directory);
    }

    public function setTitle (string $title): void
    {
        $this->title = $title;
    }

    public function setIndex (string $indexUri): void
    {
        $this->index = $this->addExtension($indexUri);
    }

    public function setFrame (string $frameUri): void
    {
        $this->frame = $this->addExtension($frameUri);
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
            "frame" => $this->frame,
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
            $this->index,
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
        if (!File::isDir($this->directory)) {
            throw new \InvalidArgumentException("{$this->directory} is not a directory");
        }
        else if (!File::isReadable($this->directory)) {
            throw new \InvalidArgumentException("{$this->directory} is not readable");
        }

        if (is_string($this->frame)) {
            $framePath = $this->directory . $this->frame;
            if (!File::isValidFile($framePath)) {
                throw new \InvalidArgumentException("{$framePath} is not a valid file");
            }
        }
    }

    private function init (): Environment
    {
        $twigLoader = new FilesystemLoader($this->directory);
        return new Environment($twigLoader, []);
    }

    private function addExtension (string $filename): string
    {
        return preg_replace(['/^\/*/', '/.twig$/'], '', $filename) . ".twig";
    }
}
