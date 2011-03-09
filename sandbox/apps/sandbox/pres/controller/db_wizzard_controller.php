<?php
   import('tools::http', 'HeaderManager');
   
   class db_wizzard_controller extends base_controller {

      private static $CONFIG_SECTION_NAME = 'Sandbox-MySQL';
      private static $CONFIG_SUB_SECTION_NAME = 'DB';

      private static $TABLE_NAME = 'sandbox_content';

      public function transformContent() {

         // step 1: create database config file
         $formNewConfig = &$this->__getForm('new-db-config');

         if($formNewConfig->isSent() && $formNewConfig->isValid()) {

            // rerieve the form values
            $host = $formNewConfig->getFormElementByName('db-host')->getAttribute('value');
            $port = $formNewConfig->getFormElementByName('db-port')->getAttribute('value');
            $user = $formNewConfig->getFormElementByName('db-user')->getAttribute('value');
            $pass = $formNewConfig->getFormElementByName('db-pass')->getAttribute('value');
            $name = $formNewConfig->getFormElementByName('db-name')->getAttribute('value');

            // create configuration and save it!
            $dbSection = new IniConfiguration();

            $section = new IniConfiguration();
            $section->setValue('Host', $host.':'.$port);
            $section->setValue('User', $user);
            $section->setValue('Pass', $pass);
            $section->setValue('Name', $name);
            $section->setValue('Collation', 'utf8_general_ci');
            $section->setValue('Charset', 'utf8');
            $section->setValue('Type', 'MySQLx');

            $dbSection->setSection('DB', $section);

            $config = new IniConfiguration();
            $config->setSection(self::$CONFIG_SECTION_NAME, $dbSection);

            $this->saveConfiguration('core::database', 'connections.ini', $config);

            HeaderManager::forward('./?page=db-wizzard#step-2');
            return;

         }

         $configAvailable = false;
         try {
            $config = $this->getConfiguration('core::database', 'connections.ini');
            $tmpl = &$this->__getTemplate('db-config-exists');

            $section = $config->getSection(self::$CONFIG_SECTION_NAME)->getSection(self::$CONFIG_SUB_SECTION_NAME);

            $rawHost = $section->getValue('Host');
            $colon = strpos($rawHost, ':');
            $host = substr($rawHost, 0, $colon);
            $port = substr($rawHost, $colon + 1);

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

         // step 2: create the database table
         $tableExists = false;
         if($configAvailable) {

            // evaluate, whether the table is already existing
            try {

               $conn = &$this->getConnection();
               $select = 'SHOW TABLES';

               $result = $conn->executeTextStatement($select);
               $tables = array();
               while ($data = $conn->fetchData($result)) {
                  $keys = array_keys($data);
                  if (self::$TABLE_NAME == $data[$keys[0]]) {
                     $tableExists = true;
                  }
               }

               if ($tableExists) {
                  $this->__getTemplate('table-exists')->transformOnPlace();
               } else {
                  $create = 'CREATE TABLE `'.self::$TABLE_NAME.'` (
`id` INT(5) NOT NULL AUTO_INCREMENT,
`urlname` VARCHAR(30) NOT NULL,
`content` TEXT NOT NULL,
PRIMARY KEY (`id`),
UNIQUE (`urlname`)
) ENGINE = MYISAM;';

                  $formCreateTable = &$this->__getForm('create-table');

                  if($formCreateTable->isSent()){
                     $conn->executeTextStatement($create);

                     // add initial text
                     $insert = 'INSERT INTO `' . self::$TABLE_NAME . '` (`urlname`, `content`) VALUES (\'hello-world\', \'This is displayed on the hello-world page. / Dieser Text wird auf der Hallo-Welt!-Seite angezeigt.\');';
                     $conn->executeTextStatement($insert);

                     HeaderManager::forward('./?page=db-wizzard#step-3');
                  } else {
                     $tmpl = &$this->__getTemplate('step-2');
                     $tmpl->setPlaceHolder('statement', $create);
                     $tmpl->transformOnPlace();

                     $formCreateTable->transformOnPlace();
                  }
               }

            } catch (DatabaseHandlerException $e) {
               $tmpl = &$this->__getTemplate('db-conn-error');
               $tmpl->setPlaceHolder('exception', $e->getMessage());
               $tmpl->transformOnPlace();
            }

         } else {
            $this->__getTemplate('step-1-req')->transformOnPlace();
         }

         // step 3: create some contents
         if ($configAvailable && $tableExists) {

            // display entry text
            $this->__getTemplate('step-3')->transformOnPlace();

            // handle for behaviour
            $formCreateContent = &$this->__getForm('add-content');
            if ($formCreateContent->isSent() && $formCreateContent->isValid()) {

               $urlName = $formCreateContent->getFormElementByName('content-urlname')->getAttribute('value');
               $content = $formCreateContent->getFormElementByName('content-text')->getContent();

               $content = $conn->escapeValue(html_entity_decode($content));

               $insert = 'INSERT INTO `' . self::$TABLE_NAME . '` (`urlname`, `content`) VALUES (\'' . $urlName . '\', \'' . $content . '\');';
               $conn->executeTextStatement($insert);

               HeaderManager::forward('./?page=db-wizzard#step-4');
            }

            $formCreateContent->transformOnPlace();

         } else {
            $this->__getTemplate('step-1-2-req')->transformOnPlace();
         }

         // step 4: display content
         if ($configAvailable && $tableExists) {

            $tmpl = &$this->__getTemplate('step-4');

            $select = 'SELECT * FROM `'.self::$TABLE_NAME.'` ORDER BY `urlname` ASC';
            $result = $conn->executeTextStatement($select);

            $buffer = '';
            while($data = $conn->fetchData($result)){
               $buffer .= '<li><a href="?page=db-content&name='.$data['urlname'].'">'.$data['urlname'].'</a></li>';
            }

            $tmpl->setPlaceHolder('pages', $buffer);

            $tmpl->transformOnPlace();

         } else {
            $this->__getTemplate('step-1-2-3-req')->transformOnPlace();
         }

      }

      /**
       * @return MySQLxHandler The database connection.
       */
      private function &getConnection(){
         return $this->getServiceObject('core::database', 'ConnectionManager')->getConnection(self::$CONFIG_SECTION_NAME);
      }

   }
?>