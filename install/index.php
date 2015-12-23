<?
use Bitrix\Main\Application;

include __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'include.php';

Class ws_migrations extends CModule {
    const MODULE_ID = 'ws.migrations';
    var $MODULE_ID = 'ws.migrations';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $PARTNER_NAME = 'WorkSolutions';
    var $PARTNER_URI = 'http://worksolutions.ru';
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $strError = '';

    function __construct() {
        $arModuleVersion = array();
        include(dirname(__FILE__) . "/version.php");
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        $localization = \WS\Migrations\Module::getInstance()->getLocalization('info');
        $this->MODULE_NAME = $localization->getDataByPath("name");
        $this->MODULE_DESCRIPTION = $localization->getDataByPath("description");
        $this->PARTNER_NAME = GetMessage('PARTNER_NAME');
        $this->PARTNER_NAME = $localization->getDataByPath("partner.name");
        $this->PARTNER_URI = 'http://worksolutions.ru';
    }

    function InstallDB($arParams = array()) {
        RegisterModuleDependences('main', 'OnPageStart', self::MODULE_ID, 'WS\Migrations\Module', 'listen');
        RegisterModuleDependences('main', 'OnAfterEpilog', self::MODULE_ID, 'WS\Migrations\Module', 'commitDutyChanges');
        global $DB;
        $DB->RunSQLBatch(Application::getDocumentRoot().'/'.Application::getPersonalRoot() . "/modules/".$this->MODULE_ID."/install/db/install.sql");
        return true;
    }

    function UnInstallDB($arParams = array()) {
        UnRegisterModuleDependences('main', 'OnPageStart', self::MODULE_ID, 'WS\Migrations\Module', 'listen');
        UnRegisterModuleDependences('main', 'OnAfterEpilog', self::MODULE_ID, 'WS\Migrations\Module', 'commitDutyChanges');
        global $DB;
        $DB->RunSQLBatch(Application::getDocumentRoot().'/'.Application::getPersonalRoot()."/modules/".$this->MODULE_ID."/install/db/uninstall.sql");
        return true;
    }

    function InstallFiles() {
        $rootDir = Application::getDocumentRoot().'/'.Application::getPersonalRoot();
        $adminGatewayFile = '/admin/ws_migrations.php';
        copy(__DIR__. $adminGatewayFile, $rootDir . $adminGatewayFile);
        return true;
    }

    function UnInstallFiles() {
        $rootDir = Application::getDocumentRoot().'/'.Application::getPersonalRoot();
        $adminGatewayFile = '/admin/ws_migrations.php';
        unlink($rootDir . $adminGatewayFile);
        return true;
    }

    function DoInstall() {
        global $APPLICATION, $data;
        $loc = \WS\Migrations\Module::getInstance()->getLocalization('setup');
        $options = \WS\Migrations\Module::getInstance()->getOptions();
        $this->createPlatformDirIfNotExists();
        global $errors;
        $errors = array();
        if ($data['catalog']) {
            $dir = $_SERVER['DOCUMENT_ROOT'].$data['catalog'];
            if (!is_dir($dir)) {
                mkdir($dir);
            }
            if (!is_dir($dir)) {
                $errors[] = $loc->getDataByPath('error.notCreateDir');
            }
            if (!$errors) {
                $options->catalogPath = $data['catalog'];
            }
            $this->InstallFiles();
            $this->InstallDB();
            RegisterModule(self::MODULE_ID);
            \Bitrix\Main\Loader::includeModule(self::MODULE_ID);
            \Bitrix\Main\Loader::includeModule('iblock');
            $this->module()->install();

            foreach ($this->module()->getSubjectHandlers() as $handler) {
                $handlerClass = get_class($handler);
                $handlerClassValue = (bool)$data['handlers'][$handlerClass];
                $handlerClassValue && $this->module()->enableSubjectHandler($handlerClass);
                !$handlerClassValue && $this->module()->disableSubjectHandler($handlerClass);
            }
        }
        if (!$data || $errors) {
            $APPLICATION->IncludeAdminFile($loc->getDataByPath('title'), __DIR__.'/form.php');
            return;
        }
    }

    function DoUninstall() {
        global $APPLICATION, $data;
        global $errors;
        $errors = array();
        $loc = $this->module()->getLocalization('uninstall');

        if (!$data || $errors) {
            $APPLICATION->IncludeAdminFile($loc->getDataByPath('title'), __DIR__.'/uninstall.php');
            return;
        }
        if ($data['removeAll'] == "Y") {
            $this->removeFiles();
            $this->UnInstallDB();
            $this->removeOptions();
            $this->removePlatformDir();
        }
        $this->UnInstallFiles();
        UnRegisterModule(self::MODULE_ID);
    }

    /**
     * @return \WS\Migrations\Module
     */
    private function module() {
        return WS\Migrations\Module::getInstance();
    }

    private function removeFiles() {
        $options = $this->module()->getOptions();
        $dir = $_SERVER['DOCUMENT_ROOT'].($options->catalogPath ?: 'migrations');
        is_dir($dir) && \Bitrix\Main\IO\Directory::deleteDirectory($dir);
    }

    private function removeOptions() {
        COption::RemoveOption("ws.migrations");
    }

    private function createPlatformDirIfNotExists() {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . \COption::GetOptionString("main", "upload_dir", "upload");
        if (is_dir($uploadDir.'/ws.migrations')) {
            return;
        }
        CopyDirFiles(__DIR__.'/upload', $uploadDir, false, true);
    }

    private function removePlatformDir() {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . \COption::GetOptionString("main", "upload_dir", "upload");
        \Bitrix\Main\IO\Directory::deleteDirectory($uploadDir.'/ws.migrations');
    }
}
