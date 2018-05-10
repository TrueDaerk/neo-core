<?php

namespace Neo\Annotations;

use PHPUnit\Framework\TestCase;

/**
 * Just test comment to test the annotations
 *
 * @package Neo\Annotations
 * @annotation
 *
 * @MyAnnotation
 * @Authentication __auth
 * over multiline
 * @Another _with
 * multi
 */
class AnnotationsTest extends TestCase {

   /**
    * Just to retrieve the annotations
    *
    * @author Gregory Geant
    * @authorization _none_
    */
   private static function stcMethod() {
   }

   /**
    * @any
    */
   public function testGetAnnotationsForClass() {
      $annotations = Annotations::getAnnotationsForClass(__CLASS__);
      $this->assertEquals(5, count($annotations));
      $this->assertEquals("Neo\\Annotations", $annotations["package"]);
      $this->assertEquals("__auth over multiline", $annotations["Authentication"]);
      $this->assertEquals("_with multi", $annotations["Another"]);
   }

   public function testGetAnnotationForClass() {
      $this->assertEquals("Neo\\Annotations", Annotations::getAnnotationForClass($this, "package"));
      $this->assertEquals("__auth over multiline", Annotations::getAnnotationForClass($this, "Authentication"));
   }

   public function testGetAnnotationsForMethod() {
      $annotations = Annotations::getAnnotationsForMethod([$this, "testGetAnnotationsForClass"]);
      $this->assertNull(@$annotations["author"]);
      $this->assertEquals("", $annotations["any"]);

      $annotations = Annotations::getAnnotationsForMethod("Neo\\Annotations\\AnnotationsTest::stcMethod");
      $this->assertEquals("Gregory Geant", $annotations["author"]);
      $this->assertEquals("_none_", $annotations["authorization"]);
   }

   public function testGetAnnotationForMethod() {
      $this->assertEquals("", Annotations::getAnnotationForMethod([$this, "testGetAnnotationsForClass"], "any"));
      $this->assertFalse(Annotations::getAnnotationForMethod([$this, "testGetAnnotationsForClass"], "does_not_exist"));
      $this->assertNull(Annotations::getAnnotationForMethod("bullshit", "does_not_exist"));
   }
}
