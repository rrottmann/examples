<?php
namespace EXAMPLE\vbc\pres\taglib;

/**
 * <!--
 * This file is part of the adventure php framework (APF) published under
 * http://adventure-php-framework.org.
 *
 * The APF is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * The APF is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with the APF. If not, see http://www.gnu.org/licenses/lgpl-3.0.txt.
 * -->
 */
use APF\core\pagecontroller\ImportTemplateTag;
use APF\tools\cache\CacheManager;
use APF\tools\cache\CacheManagerFabric;
use APF\tools\cache\key\SimpleCacheKey;

/**
 * Implements the view based caching taglib, based on the core:importdesign
 * tag. The tag expects the following parameters:
 * <ul>
 * <li>namespace: the namespace of the template to include</li>
 * <li>template: the name of the template to include</li>
 * <li>cachekey: the cachekey used for caching</li>
 * <li>cacheconfig: the cache configuration key</li>
 * </ul>
 *
 * @author Christian Achatz
 * @version
 * Version 0.1, 29.11.2008<br />
 */
class CachedImportTemplateTag extends ImportTemplateTag {

   /**
    * @private
    * Stores the cache content if applicable.
    */
   private $cacheContent = null;

   /**
    * @public
    *
    * Re-implements the onParseTime() method. Validates the tag attributes and tries
    * to fetch the content from the cache. If ni cache content is applicable, the
    * parent class' functionality is called.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 29.11.2008<br />
    */
   public function onParseTime() {

      $cM = $this->getCache();

      // clear the cache if desired
      $cacheKey = $this->getCacheKey();
      if (isset($_REQUEST['clearcache']) && $_REQUEST['clearcache'] == 'true') {
         $cM->clearCache($cacheKey);
      }

      // try to read from the cache
      $this->cacheContent = $cM->getFromCache($cacheKey);

      // check if the document was cached before. If not
      // execute the parent's onParseTime()
      if ($this->cacheContent === null) {
         parent::onParseTime();
      }
   }

   /**
    * @return CacheManager
    */
   protected function getCache() {
      /* @var $fabric CacheManagerFabric */
      $fabric = $this->getServiceObject(CacheManagerFabric::class);

      return $fabric->getCacheManager($this->getAttribute('cacheconfig'));
   }

   /**
    * @return SimpleCacheKey
    */
   protected function getCacheKey() {
      return new SimpleCacheKey($this->getAttribute('cachekey'));
   }

   /**
    * @public
    *
    * Re-implements the transform() method. Returns the cache content loaded within the
    * onParseTime() method or executes the parent class' functionality.
    *
    * @author Christian Achatz
    * @version
    * Version 0.1, 29.11.2008<br />
    */
   public function transform() {

      // generate the node's output or return the cached content
      if ($this->cacheContent === null) {

         // generate output and cache it
         $output = parent::transform();
         $this->getCache()->writeToCache($this->getCacheKey(), $output);

         // return the tag's output
         return $output;
      }

      return $this->cacheContent;
   }

}