<?php

namespace Icinga\Clicommands;

use Icinga\Cli\Command;

/**
 * List and handle modules
 *
 * The module command allows you to handle your IcingaWeb modules
 *
 * Usage: icingaweb module [<action>] [<modulename>]
 */
class ModuleCommand extends Command
{
    protected $modules;

    public function init()
    {
        $this->modules = $this->app->getModuleManager();
    }

    /**
     * List all enabled modules
     *
     * If you are interested in all installed modules pass 'installed' (or
     * even --installed) as a command parameter. If you enable --verbose even
     * more details will be shown
     *
     * Usage: icingaweb module list [installed] [--verbose]
     */
    public function listAction()
    {
        if ($type = $this->params->shift()) {
            if (! in_array($type, array('enabled', 'installed'))) {
                return $this->showUsage();
            }
        } else {
            $type = 'enabled';
            $this->params->shift('enabled');
            if ($this->params->shift('installed')) {
                $type = 'installed';
            }
        }

        if ($this->hasRemainingParams()) {
            return $this->showUsage();
        }

        if ($type === 'enabled') {
            $modules = $this->modules->listEnabledModules();
        } else {
            $modules = $this->modules->listInstalledModules();
        }
        if (empty($modules)) {
            echo "There are no modules installed\n";
            return;
        }
        if ($this->isVerbose) {
            printf("%-14s %-9s DIRECTORY\n", 'MODULE', 'STATE');
        } else {
            printf("%-14s %-9s\n", 'MODULE', 'STATE');
        }
        foreach ($modules as $module) {
            if ($this->isVerbose) {
                $dir = ' ' . $this->modules->getModuleDir($module);
            } else {
                $dir = '';
            }
            printf(
                "%-14s %-9s%s\n",
                $module,
                ($type === 'enabled' || $this->modules->hasEnabled($module))
              ? 'enabled'
              : 'disabled',
                $dir
            );
        }
        echo "\n";
    }

    /**
     * Enable a given module
     *
     * Usage: icingaweb module enable <module-name>
     */
    public function enableAction()
    {
        if (! $module = $this->params->shift()) {
            $module = $this->params->shift('module');
        }
        if (! $module || $this->hasRemainingParams()) {
            return $this->showUsage();
        }
        $this->modules->enableModule($module);
    }

    /**
     * Disable a given module
     *
     * Usage: icingaweb module disable <module-name>
     */
    public function disableAction()
    {
        if (! $module = $this->params->shift()) {
            $module = $this->params->shift('module');
        }
        if (! $module || $this->hasRemainingParams()) {
            return $this->showUsage();
        }
        $this->modules->disableModule($module);
    }

    /**
     * Show all restrictions provided by your modules
     *
     * Asks each enabled module for all available restriction names and
     * descriptions and shows a quick overview
     *
     * Usage: icingaweb module restrictions
     */
    public function restrictionsAction()
    {
        printf("%-14s %-16s %s\n", 'MODULE', 'RESTRICTION', 'DESCRIPTION');
        foreach ($this->modules->listEnabledModules() as $moduleName) {
            $module = $this->modules->loadModule($moduleName)->getModule($moduleName);
            foreach ($module->getProvidedRestrictions() as $restriction) {
                printf(
                    "%-14s %-16s %s\n",
                    $moduleName,
                    $restriction->name,
                    $restriction->description
                );
            }
        }
    }

    /**
     * Show all permissions provided by your modules
     *
     * Asks each enabled module for it's available permission names and
     * descriptions and shows a quick overview
     *
     * Usage: icingaweb module permissions
     */
    public function permissionsAction()
    {
        printf("%-14s %-24s %s\n", 'MODULE', 'PERMISSION', 'DESCRIPTION');
        foreach ($this->modules->listEnabledModules() as $moduleName) {
            $module = $this->modules->loadModule($moduleName)->getModule($moduleName);
            foreach ($module->getProvidedPermissions() as $restriction) {
                printf(
                    "%-14s %-24s %s\n",
                    $moduleName,
                    $restriction->name,
                    $restriction->description
                );
            }
        }
    }

    /**
     * Search for a given module
     *
     * Does a lookup against your configured IcingaWeb app stores and tries to
     * find modules matching your search string
     *
     * Usage: icingaweb module search <search-string>
     */
    public function searchAction()
    {
        $this->fail("Not implemented yet");
    }

    /**
     * Install a given module
     *
     * Downloads a given module or installes a module from a given archive
     *
     * Usage: icingaweb module install <module-name>
     *        icingaweb module install </path/to/archive.tar.gz>
     */
    public function installAction()
    {
        $this->fail("Not implemented yet");
    }

    /**
     * Remove a given module
     *
     * Removes the given module from your disk. Module configuration will be
     * preserved
     *
     * Usage: icingaweb module remove <module-name>
     */
    public function removeAction()
    {
        $this->fail("Not implemented yet");
    }

    /**
     * Purge a given module
     *
     * Removes the given module from your disk. Also wipes configuration files
     * and other data stored and/or generated by this module
     *
     * Usage: icingaweb module remove <module-name>
     */
    public function purgeAction()
    {
        $this->fail("Not implemented yet");
    }
}
