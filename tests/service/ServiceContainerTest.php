<?php

namespace Neo\Service;

use PHPUnit\Framework\TestCase;

class ServiceContainerTest extends TestCase {
   /**
    * @var ServiceContainer
    */
   private $container;

   public function __construct(?string $name = null, array $data = [], string $dataName = '') {
      parent::__construct($name, $data, $dataName);

      $this->container = ServiceContainer::getContainer();
      $this->container[Service::class] = ServiceImpl::class;
      $this->container[Service::class . "2"] = ServiceImpl2::class;
      $this->container[Service::class . "3"] = [$this, "createService"];
   }

   public function testService() {
      $this->assertInstanceOf(Service::class, $this->container[Service::class]);
      $this->assertInstanceOf(ServiceImpl::class, $this->container[Service::class]);
      $this->assertInstanceOf(Service::class, $this->container[Service::class . "2"]);
      $this->assertInstanceOf(ServiceImpl2::class, $this->container[Service::class . "2"]);
      $this->assertInstanceOf(Service::class, $this->container[Service::class . "3"]);
      $this->assertInstanceOf(ServiceImpl::class, $this->container[Service::class . "3"]);

      // Quick implementation test
      $this->assertEquals("blue", $this->container[Service::class]->call("blue"));
      $this->assertEquals("car", $this->container[Service::class]->call("car"));
      $this->assertEquals("always 1", $this->container[Service::class . "2"]->call("blue"));
      $this->assertEquals("always 1", $this->container[Service::class . "2"]->call("car"));
      $this->assertEquals("blue", $this->container[Service::class . "3"]->call("blue"));
      $this->assertEquals("car", $this->container[Service::class . "3"]->call("car"));

      // Assert that implementations are not the same instance.
      $this->assertNotSame($this->container[Service::class], $this->container[Service::class . "3"]);
      // Assert multiple calls are same instance
      $this->assertSame($this->container[Service::class], $this->container[Service::class]);

      // Check unregistered exception
      try {
         $this->container["unregistered"];
         // Fail test
         $this->fail("Should throw UnregisteredServiceException");
      } catch (UnregisteredServiceException $exception) {
         // Nothing, everything is fine
      }
   }

   public function testPrototype() {
      $this->container[Service::class] = [ServiceImpl::class, ServiceContainer::PROTOTYPE];
      $copy1 = $this->container[Service::class];
      $copy2 = $this->container[Service::class];
      $this->assertNotSame($copy1, $copy2);
   }

   public function createService() {
      return new ServiceImpl();
   }
}

interface Service {
   function call($o);
}

class ServiceImpl implements Service {

   function call($o) {
      return $o;
   }
}

class ServiceImpl2 implements Service {

   function call($o) {
      return "always 1";
   }
}