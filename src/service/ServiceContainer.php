<?php

namespace Neo\Service;

class ServiceContainer implements \ArrayAccess {
   /* Static fields */
   public const PROTOTYPE = "prototype";
   /**
    * @var ServiceContainer[]
    */
   private static $containers = [];
   /* Instance fields */
   private $services = [];

   /**
    * Retrieve a ServiceContainer instance, or creates one, if it does not exist.
    *
    * @param string $name Name of the ServiceContainer to retrieve.
    *       If not set, the name will be default and always return the same default ServiceContainer.
    * @return ServiceContainer ServiceContainer to use to register or retrieve services.
    */
   public static function getContainer($name = "default") {
      if (empty($name)) {
         $name = "default";
      }
      if (!isset(self::$containers[$name])) {
         self::$containers[$name] = new static();
      }
      return self::$containers[$name];
   }

   /**
    * Alias for __set
    *
    * @param string $name Name of the service (for best usability, use interface names, so that services
    *       may not interfere with other services).
    * @param mixed|callable $value Value or callable to use as service. The value can be a directly initialized service,
    *       but a callable that initializes the service when needed would be better.
    */
   public function registerService($name, $value) {
      $this->__set($name, $value);
   }

   /**
    * Alias for __get
    *
    * @param string $name Name of the service.
    * @return mixed Service registered for the given name.
    */
   public function getService($name) {
      return $this->__get($name);
   }

   //
   // MAGIC METHODS
   //

   /**
    * Registers a service in the service container.
    *
    * @param string $name Name of the service (for best usability, use interface names, so that services
    *       may not interfere with other services).
    * @param mixed|callable $value Value or callable to use as service. The value can be a directly initialized service,
    *       but a callable that initializes the service when needed would be better.
    */
   public function __set($name, $value) {
      $this->services[$name] = $value;
   }

   /**
    * @inheritdoc
    * @return mixed Service registered for the given name.
    */
   public function __get($name) {
      if (!key_exists($name, $this->services)) {
         throw new UnregisteredServiceException("Service \"$name\" was not registered in this ServiceContainer");
      }
      $value = $this->services[$name];
      if (is_callable($value)) {
         // Initialize the service and set it directly into services
         $value = call_user_func_array($value, [$this]);
         $this->services[$name] = $value;

      } elseif (is_string($value) && class_exists($value)) {
         // If the given value is a class name, will try to create an instance
         $value = new $value();
         $this->services[$name] = $value;
      } elseif (is_array($value) && count($value) == 2) {
         // Check for prototype options
         if (class_exists($value[0]) && $value[1] === self::PROTOTYPE) {
            $value = new $value[0]();
         }
      }
      // And return the initialized service
      return $value;
   }

   /**
    * @inheritdoc
    */
   public function __unset($name) {
      if (key_exists($name, $this->services)) {
         unset($this->services[$name]);
      }
   }

   /**
    * @inheritdoc
    */
   public function __isset($name) {
      return isset($this->services[$name]);
   }

   //
   // INTERFACE IMPLEMENTATIONS
   //

   /**
    * @inheritdoc
    */
   public function offsetExists($offset) {
      return $this->__isset($offset);
   }

   /**
    * @inheritdoc
    */
   public function offsetGet($offset) {
      return $this->__get($offset);
   }

   /**
    * @inheritdoc
    */
   public function offsetSet($offset, $value) {
      $this->__set($offset, $value);
   }

   /**
    * @inheritdoc
    */
   public function offsetUnset($offset) {
      $this->__unset($offset);
   }
}