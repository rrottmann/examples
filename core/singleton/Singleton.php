<?php
   /**
   *  @package core::singleton
   *  @class Singleton
   *  @static
   *
   *  Abstrakte Implementierung des Singleton-Patterns. Als Cache wird<br />
   *  der Offset 'SINGLETON_CACHE' im $GLOBALS-Array verwendet.<br />
   *  <br />
   *  Verwendung:<br />
   *  $oObject = &Singleton::getInstance('<ClassName>');<br />
   *
   *  @author Christian Achatz
   *  @version
   *  Version 0.1, 12.04.2006<br />
   */
   class Singleton
   {

      function Singleton(){
      }


      /**
      *  @public
      *  @static
      *
      *  Gibt die Instanz des mit $className angegebenen Objekts zur�ck.<br />
      *  Wird dies bereits im Singleton-Cache gefunden, gibt die Methode<br />
      *  das bereits gecachete zur�ck, ist es noch nicht erzeugt, so wird<br />
      *  eine neue Instanz erstellt und in den Singleton-Cache geschreiben.<br />
      *
      *  @author Christian Achatz
      *  @version
      *  Version 0.1, 12.04.2006<br />
      *  Version 0.2, 21.08.2007 (Es wird nun gepr�ft, ob die Klasse existiert)<br />
      */
      function &getInstance($className){

         // Pr�fen, ob Instanz des Objekt bereits existiert
         if(!Singleton::isInSingletonCache($className)){

            // Pr�fen, ob Klasse vorhanden
            if(!class_exists($className)){
               trigger_error('[Singleton::getInstance()] Class "'.$className.'" cannot be found! Maybe the class name is misspelt!',E_USER_ERROR);
               exit(1);
             // end if
            }

            // Erzeugt Klasse $className singleton
            $GLOBALS[Singleton::showCacheContainerOffset()][Singleton::createCacheObjectName($className)] = new $className;

          // end if
         }

         // Gibt Instanz aus Singleton-Cache zur�ck
         return $GLOBALS[Singleton::showCacheContainerOffset()][Singleton::createCacheObjectName($className)];

       // end function
      }


      /**
      *  @public
      *  @static
      *
      *  L�scht die Instanz eines �bergebenen Objekts aus dem Singleton-Cache.<br />
      *
      *  @author Christian Achatz
      *  @version
      *  Version 0.1, 12.04.2006<br />
      */
      function clearInstance($className){
         unset($GLOBALS[Singleton::showCacheContainerOffset()][Singleton::createCacheObjectName($className)]);
       // end function
      }


      /**
      *  @public
      *  @static
      *
      *  Setzt den Singleton-Cache f�r alle Objekte zur�ck.<br />
      *
      *  @author Christian Achatz
      *  @version
      *  Version 0.1, 12.04.2006<br />
      */
      function clearAll(){
         $GLOBALS[Singleton::showCacheContainerOffset()] = array();
       // end function
      }


      /**
      *  @public
      *  @static
      *
      *  Pr�ft, ob ein Objekt bereits im Singleton-Cache vorhanden ist.<br />
      *
      *  @author Christian Achatz
      *  @version
      *  Version 0.1, 12.04.2006<br />
      */
      function isInSingletonCache($className){

         if(isset($GLOBALS[Singleton::showCacheContainerOffset()][Singleton::createCacheObjectName($className)])){
            return true;
          // end if
         }
         else{
            return false;
          // end else
         }

       // end function
      }


      /**
      *  @public
      *  @static
      *
      *  Erzeugt den Cache-Namen der Klasse.<br />
      *
      *  @author Christian Achatz
      *  @version
      *  Version 0.1, 12.04.2006<br />
      */
      function createCacheObjectName($className){
         return strtoupper($className);
       // end function
      }


      /**
      *  @public
      *  @static
      *
      *  Gibt den Offset des $GLOBALS-Array zur�ck, in dem der Singleton-Cache<br />
      *  gehalten wird.<br />
      *
      *  @author Christian Achatz
      *  @version
      *  Version 0.1, 12.04.2006<br />
      */
      function showCacheContainerOffset(){
         return (string)'SINGLETON_CACHE';
       // end function
      }

    // end class
   }
?>