<?php

namespace Neo\Annotations;

class Annotations {
   private static $annotationCache = [];

   /**
    * Reads the annotations from the given class (instance or name).
    *
    * @param object|string|array $class Instance of the class to read the annotations or name of the class. Alternatively, a class method can be given.
    * @return array|bool Assoc array containing the annotation name (without @) and the text after the annotation.
    * Returns false if the given class does not exist.
    */
   public static function getAnnotationsForClass($class) {
      try {
         // Try to get method first. If it succeeds, get the class reflection from that
         $reflection = self::getMethodReflection($class);
         if ($reflection instanceof \ReflectionMethod) {
            $reflection = $reflection->getDeclaringClass();
         } else {
            $reflection = new \ReflectionClass($class);
         }
      } catch (\Exception $e) {
         return false;
      }
      $classname = $reflection->getName();
      if (key_exists($classname, self::$annotationCache)) {
         // Retrieve the cached annotations
         return self::$annotationCache[$classname];
      }
      // Parse annotations
      $comment = $reflection->getDocComment();
      if (is_string($comment)) {
         $annotations = self::parseAnnotations($comment);

      } else {
         // No comment, so no annotation
         $annotations = [];
      }
      self::$annotationCache[$classname] = $annotations;
      return $annotations;
   }

   /**
    * Retrieve a single annotation of the class.
    *
    * @param object|string|array $class Instance or name of the class to retrieve the annotation.
    * @param string $name Name of the annotation to get the value.
    * @return string|bool Value of the annotation found, or false, if the annotation was not found.
    * Returns false if the given class does not exist.
    * Returns false if the given annotation does not exist.
    */
   public static function getAnnotationForClass($class, $name) {
      $annotations = self::getAnnotationsForClass($class);
      if ($annotations === false) {
         return null;
      }
      return @$annotations[$name] ?: false;
   }

   /**
    * Reads the annotations from the given class (instance or name).
    *
    * @param array|string Array containing class instance and method name or direct method name (such as \substr or Cls::staticMethod).
    * @return array|bool Assoc array containing the annotation name (without @) and the text after the annotation.
    * Returns false if the given method does not exist or had an invalid format.
    */
   public static function getAnnotationsForMethod($method) {
      $reflection = self::getMethodReflection($method);
      if (!isset($reflection)) {
         return [];

      } elseif ($reflection === false) {
         return false;
      }
      // Create key for cache
      $key = $reflection->getDeclaringClass()->getName() . "::" . $reflection->getName();
      if (key_exists($key, self::$annotationCache)) {
         // Retrieve the cached annotations
         return self::$annotationCache[$key];
      }
      // Parse annotations
      $comment = $reflection->getDocComment();
      if (is_string($comment)) {
         $annotations = self::parseAnnotations($comment);

      } else {
         // No comment, so no annotation
         $annotations = [];
      }
      self::$annotationCache[$key] = $annotations;
      return $annotations;
   }

   /**
    * Retrieve a single annotation of the method.
    *
    * @param array|string Array containing class instance and method name or direct method name (such as \substr or Cls::staticMethod).
    * @param string $name Name of the annotation to get the value.
    * @return string|bool Value of the annotation found, or false, if the annotation was not found.
    * Returns null if the given method does not exist or had an invalid format.
    * Returns false if the given annotation does not exist.
    */
   public static function getAnnotationForMethod($method, $name) {
      $annotations = self::getAnnotationsForMethod($method);
      if ($annotations === false) {
         return null;
      }
      return @$annotations[$name] ?: false;
   }

   /**
    * Retrieves the reflection method.
    *
    * @param string|array $method Method to get the reflection instance.
    * @return array|bool|\ReflectionMethod
    */
   public static function getMethodReflection($method) {
      try {
         if (is_array($method)) {
            if (count($method) === 2) {
               $reflection = new \ReflectionMethod($method[0], $method[1]);
            } else {
               // Empty array, because wrong method.
               return null;
            }
         } elseif (is_string($method)) {
            $reflection = new \ReflectionMethod($method);
         } else {
            // Invalid method
            return null;
         }
      } catch (\Exception $exception) {
         return false;
      }
      return $reflection;
   }

   /**
    * Parses all annotations from the given comment.
    *
    * @param string $comment PHP Doc comment to parse.
    * @return array Assoc array containing the annotation names and their values.
    */
   private static function parseAnnotations($comment) {
      // Remove prepending and appending '/' from comment first.
      if (mb_substr($comment, 0, 1) === "/") {
         $comment = mb_substr($comment, 1);
      }
      if (mb_substr($comment, -1) === "/") {
         $comment = mb_substr($comment, 0, -1);
      }
      $annotations = [];
      $offset = 0;
      while (($pos = mb_strpos($comment, "@", $offset)) !== false) {
         // Read until new line before searching for the next
         $offset = mb_strpos($comment, "\n", $pos);
         if ($offset === false) {
            // Annotation is the rest of the string
            $line = mb_substr($comment, $pos);

         } elseif (($offset = mb_strpos($comment, "@", $offset)) === false) {
            // Annotation is the rest of the string.
            $line = mb_substr($comment, $pos);
            $offset = $pos + 1;

         } else {
            // Parse annotation to the next @
            $line = mb_substr($comment, $pos, $offset - $pos);
            $offset = $pos + 1;
         }
         $annotation = self::parseAnnotation($line);
         $annotations[$annotation[0]] = $annotation[1];
         continue;
      }
      return $annotations;
   }

   /**
    * Parses a single annotation.
    *
    * @param string $line String from annotation start to the next annotation or end comment.
    * @return array Array containing name (0) and value (1) of the annotation.
    */
   private static function parseAnnotation($line) {
      $spacePosition = mb_strpos($line, " ");
      $value = trim(mb_substr($line, $spacePosition));
      $name = trim(mb_substr($line, 1, $spacePosition - 1));
      return [$name, self::cleanAnnotationValue($value)];
   }

   /**
    * Removes the '*' after a new line and new line to simple spaces.
    *
    * @param string $value Annotation value.
    * @return string Cleaned annotation value.
    */
   private static function cleanAnnotationValue($value) {
      // Explode the value and remove \n and * at the start of a line
      $values = explode("\n", $value);
      $cleanValue = "";
      foreach ($values as $value) {
         $value = trim($value);
         while (mb_substr($value, 0, 1) === "*") {
            $value = trim(mb_substr($value, 1));
         }
         $cleanValue .= " $value";
      }
      // Finish by replacing multispaces and other new lines to single whitespace
      return preg_replace("#[\n\r\t ]+#", " ", trim($cleanValue));

   }
}