<?php

namespace Sprint\Migration;

class Console
{

    protected $versionManager = null;

    protected $script = 'migrate.php';

    public function __construct() {
        $this->versionManager = new VersionManager();
    }

    public function executeConsoleCommand($args) {
        $this->script = array_shift($args);

        if (empty($args) || count($args) <= 0) {
            $this->commandHelp();
            return false;
        }

        $method = array_shift($args);

        $method = str_replace(array('_', '-', ' '), '*', $method);
        $method = explode('*', $method);
        $tmp = array();
        foreach ($method as $val) {
            $tmp[] = ucfirst(strtolower($val));
        }

        $method = 'command' . implode('', $tmp);


        if (!method_exists($this, $method)) {
            Out::out('Command not found, see help');
            return false;
        }

        call_user_func_array(array($this, $method), $args);
        return true;
    }

    public function commandCreate($descr = '', $prefix = '') {
        $meta = $this->versionManager->createVersionFile($descr, $prefix);
        $this->outVersionMeta($meta);
    }

    public function commandList() {
        $versions = $this->versionManager->getVersions('all');
        Out::initTable(array(
            'Version',
            'Status',
            'Description',
            //'Location'
        ));
        foreach ($versions as $aItem){
            Out::addTableRow(array(
                $aItem['version'],
                $this->getTypeTitle($aItem['type']),
                $aItem['description'],
                //$aItem['location'],
            ));
        }

        Out::outTable();
    }

    public function commandStatus($version = '') {
        if ($version){
            $meta = $this->versionManager->getVersionMeta($version);
            $this->outVersionMeta($meta);
        } else {
            $status = $this->versionManager->getStatus();
            Out::initTable(array('Status', 'Count'));
            foreach ($status as $k => $v){
                Out::addTableRow(array($this->getTypeTitle($k), $v));
            }
            Out::outTable();
        }
    }

    public function commandMigrate($up = '--up') {
        if ($up == '--up') {
            $this->executeAll('up');

        } elseif ($up == '--down') {
            $this->executeAll('down');

        } else {
            $this->outParamsError();
        }
    }

    public function commandUp($var = 1) {
        if ($this->versionManager->checkVersionName($var)){
            $this->executeOnce($var, 'up');
        } elseif ($var == '--all') {
            $this->executeAll('up');
        } elseif (is_numeric($var) && intval($var) > 0){
            $this->executeAll('up', intval($var));
        } else {
            $this->outParamsError();
        }
    }

    public function commandDown($var = 1) {
        if ($this->versionManager->checkVersionName($var)){
            $this->executeOnce($var, 'down');
        } elseif ($var == '--all') {
            $this->executeAll('down');
        } elseif (is_numeric($var) && intval($var) > 0){
            $this->executeAll('down', intval($var));
        } else {
            $this->outParamsError();
        }
    }

    public function commandExecute($version = '', $up = '--up') {
        if ($version && $up == '--up') {
            $this->executeOnce($version, 'up');

        } elseif ($version && $up == '--down') {
            $this->executeOnce($version, 'down');

        } else {
            $this->outParamsError();
        }
    }

    public function commandRedo($version = '') {
        if ($version) {
            $this->executeOnce($version, 'down');
            $this->executeOnce($version, 'up');
        } else {
            $this->outParamsError();
        }
    }

    public function commandForce($version = '', $up = '--up') {
        $this->versionManager->checkPermissions(0);
        $this->commandExecute($version, $up);
    }

    public function commandHelp() {
        Out::out('Директория с миграциями:'.PHP_EOL.'   %s'.PHP_EOL, Module::getMigrationDir());
        Out::out('Запуск:'.PHP_EOL.'   php %s <command> [<args>]'.PHP_EOL, $this->script);

        $cmd = Module::getModuleDir() . '/commands.txt';

        if (is_file($cmd)){
            $msg = file_get_contents($cmd);
            if (Module::isWin1251()){
                $msg = iconv('utf-8', 'windows-1251//IGNORE', $msg);
            }

            Out::out($msg);
        }
    }

    protected function executeAll($action = 'up', $limit = 0) {
        $action = ($action == 'up') ? 'up' : 'down';
        $limit = (int)$limit;

        $success = 0;

        $versions = $this->versionManager->getVersions($action);
        foreach ($versions as $aItem) {
            if ($this->executeOnce($aItem['version'], $action)) {
                $success++;
            }

            if ($limit > 0 && $limit == $success) {
                break;
            }
        }

        Out::out('migrations (%s): %d', $action, $success);
        return $success;
    }

    protected function executeOnce($version, $action = 'up') {
        $action = ($action == 'up') ? 'up' : 'down';
        $params = array();

        do {
            $restart = 0;
            $ok = $this->versionManager->startMigration($version, $action, $params);
            if ($this->versionManager->needRestart($version)) {
                $params = $this->versionManager->getRestartParams($version);
                $restart = 1;
            }

        } while ($restart == 1);

        return $ok;
    }

    protected function outParamsError(){
        Out::out('Required params not found, see help');
    }

    protected function outVersionMeta($meta = false){
        if ($meta) {
            Out::initTable();
            foreach (array('version', 'type', 'description', 'location') as $val){
                if (!empty($meta[$val])){
                    $meta[$val] = ($val == 'type') ? $this->getTypeTitle($meta[$val]) : $meta[$val];
                    Out::addTableRow(array(ucfirst($val), $meta[$val]));
                }
            }
            Out::outTable();
        } else {
            Out::out('Version not found!');
        }
    }

    protected function getTypeTitle($type){
        $titles = array(
            'is_new' => 'New',
            'is_installed' => 'Installed',
            'is_unknown' => 'Unknown',
        );

        return isset($titles[$type]) ? $titles[$type] : $type;
    }
}
