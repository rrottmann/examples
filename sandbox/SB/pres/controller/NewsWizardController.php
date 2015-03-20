<?php
namespace SB\pres\controller;

use APF\core\configuration\ConfigurationException;
use APF\core\configuration\provider\ini\IniConfiguration;
use APF\core\database\AbstractDatabaseHandler;
use APF\core\database\ConnectionManager;
use APF\core\pagecontroller\BaseDocumentController;
use APF\modules\genericormapper\data\tools\GenericORMapperManagementTool;
use Exception;

class NewsWizardController extends BaseDocumentController {

   private static $CONFIG_SECTION_NAME = 'Sandbox-News';

   public function transformContent() {

      // step 1: create database config file
      $formNewConfig = &$this->getForm('new-db-config');
      if ($formNewConfig->isSent() && $formNewConfig->isValid()) {

         // retrieve the form values
         $host = $formNewConfig->getFormElementByName('db-host')->getAttribute('value');
         $port = $formNewConfig->getFormElementByName('db-port')->getAttribute('value');
         $user = $formNewConfig->getFormElementByName('db-user')->getAttribute('value');
         $pass = $formNewConfig->getFormElementByName('db-pass')->getAttribute('value');
         $name = $formNewConfig->getFormElementByName('db-name')->getAttribute('value');

         // create configuration and save it!
         $section = new IniConfiguration();
         $section->setValue('Host', $host);
         $section->setValue('User', $user);
         $section->setValue('Pass', $pass);
         $section->setValue('Name', $name);
         $section->setValue('Port', $port);
         $section->setValue('Collation', 'utf8_general_ci');
         $section->setValue('Charset', 'utf8');
         $section->setValue('Type', 'APF\core\database\MySQLiHandler');

         // load existing configuration or create new one
         try {
            $config = $this->getConfiguration('APF\core\database', 'connections.ini');
         } catch (ConfigurationException $e) {
            $config = new IniConfiguration();
         }

         $config->setSection(self::$CONFIG_SECTION_NAME, $section);
         $this->saveConfiguration('APF\core\database', 'connections.ini', $config);

         self::getResponse()->forward('./?page=news-wizard#step-2');

         return;
      }

      $configAvailable = false;
      try {
         $config = $this->getConfiguration('APF\core\database', 'connections.ini');
         $tmpl = &$this->getTemplate('db-config-exists');

         if (!$config->hasSection(self::$CONFIG_SECTION_NAME)) {
            throw new ConfigurationException('Section "' . self::$CONFIG_SECTION_NAME
                  . '" is not contained in the current configuration!', E_USER_ERROR);
         }

         $section = $config->getSection(self::$CONFIG_SECTION_NAME);

         $rawHost = $section->getValue('Host');
         $colon = strpos($rawHost, ':');
         if ($colon) {
            $host = substr($rawHost, 0, $colon);
            $port = substr($rawHost, $colon + 1);
         } else {
            $host = $section->getValue('Host');
            $port = $section->getValue('Port');
         }

         $tmpl->setPlaceHolder('host', $host);
         $tmpl->setPlaceHolder('port', $port);
         $tmpl->setPlaceHolder('user', $section->getValue('User'));
         $tmpl->setPlaceHolder('pass', $section->getValue('Pass'));
         $tmpl->setPlaceHolder('name', $section->getValue('Name'));
         $tmpl->setPlaceHolder('collation', $section->getValue('Collation'));
         $tmpl->setPlaceHolder('charset', $section->getValue('Charset'));
         $tmpl->setPlaceHolder('type', $section->getValue('Type'));

         $tmpl->transformOnPlace();

         $configAvailable = true;
      } catch (ConfigurationException $e) {
         $formNewConfig->transformOnPlace();
      }

      // step 2: create database layout
      $databaseLayoutInitialized = false;
      if ($configAvailable) {

         $formInitDb = &$this->getForm('init-db');
         try {
            /* @var $connMgr ConnectionManager */
            $connMgr = $this->getServiceObject('APF\core\database\ConnectionManager');
            /* @var $conn AbstractDatabaseHandler */
            $conn = $connMgr->getConnection(self::$CONFIG_SECTION_NAME);

            // check for db layout...
            $result = $conn->executeTextStatement('SHOW TABLES');
            $setupDone = false;
            $offsetName = 'Tables_in_' . $section->getValue('Name');
            while ($data = $conn->fetchData($result)) {
               if ($data[$offsetName] == 'ent_news') {
                  $setupDone = true;
               }
            }

            if ($setupDone) {
               $this->getTemplate('tables-exist')->transformOnPlace();
               $databaseLayoutInitialized = true;
            } else {
               if ($formInitDb->isSent()) {

                  // setup database layout
                  /* @var $setup GenericORMapperManagementTool */
                  $setup = &$this->getServiceObject('APF\modules\genericormapper\data\tools\GenericORMapperManagementTool');
                  $setup->addMappingConfiguration('APF\extensions\news', 'news');
                  $setup->addRelationConfiguration('APF\extensions\news', 'news');
                  $setup->setConnectionName(self::$CONFIG_SECTION_NAME);
                  $setup->run(true);

                  self::getResponse()->forward('?page=news-wizard#step-3');
               } else {
                  $formInitDb->transformOnPlace();
               }
            }
         } catch (Exception $e) {
            $tmplDbConnErr = &$this->getTemplate('db-conn-error');
            $tmplDbConnErr->setPlaceHolder('exception', $e->getMessage());
            $tmplDbConnErr->transformOnPlace();
         }
      } else {
         $this->getTemplate('step-1-req')->transformOnPlace();
      }

      // step 3: call news backend
      if ($databaseLayoutInitialized) {
         $this->getTemplate('step-3')->transformOnPlace();
      } else {
         $this->getTemplate('step-2-req')->transformOnPlace();
      }
   }

}
