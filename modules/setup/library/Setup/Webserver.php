<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Setup;

use Icinga\Application\Icinga;
use Icinga\Exception\ProgrammingError;

/**
 * Base class for generating webserver configuration
 */
abstract class Webserver
{
    /**
     * Document root
     *
     * @var string
     */
    protected $documentRoot;

    /**
     * Web path
     *
     * @var string
     */
    protected $webPath;

    /**
     * Create instance by type name
     *
     * @param   string $type
     *
     * @return  WebServer
     *
     * @throws  ProgrammingError
     */
    public static function createInstance($type)
    {
        $class = __NAMESPACE__ . '\\Webserver\\' . ucfirst($type);
        if (class_exists($class)) {
            return new $class();
        }
        throw new ProgrammingError('Class "%s" does not exist', $class);
    }

    /**
     * Generate configuration
     *
     * @return string
     */
    public function generate()
    {
        $template = $this->getTemplate();

        $searchTokens = array(
            '{webPath}',
            '{documentRoot}',
            '{configPath}',
        );
        $replaceTokens = array(
            $this->getWebPath(),
            $this->getDocumentRoot(),
            Icinga::app()->getConfigDir()
        );
        $template = str_replace($searchTokens, $replaceTokens, $template);
        return $template;
    }

    /**
     * Specific template
     *
     * @return string
     */
    abstract protected function getTemplate();

    /**
     * Setter for web path
     *
     * @param string $webPath
     */
    public function setWebPath($webPath)
    {
        $this->webPath = $webPath;
    }

    /**
     * Getter for web path
     *
     * @return string
     */
    public function getWebPath()
    {
        return $this->webPath;
    }

    /**
     * Set the document root
     *
     * @param   string $documentRoot
     *
     * @return  $this
     */
    public function setDocumentRoot($documentRoot)
    {
        $this->documentRoot = (string) $documentRoot;
        return $this;
    }

    /**
     * Detect the document root
     *
     * @return string
     */
    public function detectDocumentRoot()
    {
        return Icinga::app()->getBaseDir('public');
    }

    /**
     * Get the document root
     *
     * @return string
     */
    public function getDocumentRoot()
    {
        if ($this->documentRoot === null) {
            $this->documentRoot = $this->detectDocumentRoot();
        }
        return $this->documentRoot;
    }
}
