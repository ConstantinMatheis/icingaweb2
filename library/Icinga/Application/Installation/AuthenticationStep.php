<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application\Installation;

use Exception;
use Zend_Config;
use Icinga\Web\Setup\Step;
use Icinga\Application\Config;
use Icinga\Data\ResourceFactory;
use Icinga\Config\PreservingIniWriter;
use Icinga\Authentication\Backend\DbUserBackend;

class AuthenticationStep extends Step
{
    protected $data;

    protected $dbError;

    protected $authIniError;

    protected $permIniError;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function apply()
    {
        $success = $this->createAuthenticationIni();
        if (isset($this->data['adminAccountData']['resourceConfig'])) {
            $success &= $this->createAccount();
        }

        $success &= $this->defineInitialAdmin();
        return $success;
    }

    protected function createAuthenticationIni()
    {
        $config = array();
        $backendConfig = $this->data['backendConfig'];
        $backendName = $backendConfig['name'];
        unset($backendConfig['name']);
        $config[$backendName] = $backendConfig;
        if (isset($this->data['resourceName'])) {
            $config[$backendName]['resource'] = $this->data['resourceName'];
        }

        try {
            $writer = new PreservingIniWriter(array(
                'config'    => new Zend_Config($config),
                'filename'  => Config::resolvePath('authentication.ini'),
                'filemode'  => octdec($this->data['fileMode'])
            ));
            $writer->write();
        } catch (Exception $e) {
            $this->authIniError = $e;
            return false;
        }

        $this->authIniError = false;
        return true;
    }

    protected function defineInitialAdmin()
    {
        $this->permIniError = false;
        return true;
    }

    protected function createAccount()
    {
        try {
            $backend = new DbUserBackend(
                ResourceFactory::createResource(new Zend_Config($this->data['adminAccountData']['resourceConfig']))
            );

            if (array_search($this->data['adminAccountData']['username'], $backend->listUsers()) === false) {
                $backend->addUser(
                    $this->data['adminAccountData']['username'],
                    $this->data['adminAccountData']['password']
                );
            }
        } catch (Exception $e) {
            $this->dbError = $e;
            return false;
        }

        $this->dbError = false;
        return true;
    }

    public function getSummary()
    {
        return '';
    }

    public function getReport()
    {
        $report = '';
        if ($this->authIniError === false) {
            $message = t('Authentication configuration has been successfully written to: %s');
            $report .= '<p>' . sprintf($message, Config::resolvePath('authentication.ini')) . '</p>';
        } elseif ($this->authIniError !== null) {
            $message = t('Authentication configuration could not be written to: %s; An error occured:');
            $report .= '<p class="error">' . sprintf($message, Config::resolvePath('authentication.ini')) . '</p>'
                . '<p>' . $this->authIniError->getMessage() . '</p>';
        }

        if ($this->dbError === false) {
            $message = t('Account "%s" has been successfully created.');
            $report .= '<p>' . sprintf($message, $this->data['adminAccountData']['username']) . '</p>';
        } elseif ($this->dbError !== null) {
            $message = t('Unable to create account "%s". An error occured:');
            $report .= '<p class="error">' . sprintf($message, $this->data['adminAccountData']['username']) . '</p>'
                . '<p>' . $this->dbError->getMessage() . '</p>';
        }

        if ($this->permIniError === false) {
            $message = t('Account "%s" has been successfully defined as initial administrator.');
            $report .= '<p>' . sprintf($message, $this->data['adminAccountData']['username']) . '</p>';
        } elseif ($this->permIniError !== null) {
            $message = t('Unable to define account "%s" as initial administrator. An error occured:');
            $report .= '<p class="error">' . sprintf($message, $this->data['adminAccountData']['username']) . '</p>'
                . '<p>' . $this->permIniError->getMessage() . '</p>';
        }

        return $report;
    }
}
