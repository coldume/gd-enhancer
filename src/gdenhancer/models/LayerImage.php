<?php

namespace gdenhancer\models;

class LayerImage {

   const TYPE = 'image';

   public $format;
   public $resource;
   public $width;
   public $height;
   public $alignment = 'topleft';
   public $x = 0;
   public $y = 0;

   public function __construct($actions) {
      $this->runActions($actions);
   }

   public function __destruct() {
      @imagedestroy($this->resource);
   }

   protected function runActions($actions) {
      $this->runImage($actions['image']);
      foreach ($actions as $actionname => $action) {
         switch ($actionname) {
            case 'resize':
               $this->runResize($action);
               break;
            case 'move':
               $this->runMove($action);
               break;
         }
      }
   }

   protected function runImage($action) {
      $this->format = $action['format'];
      $this->resource = $action['resource'];
      $this->width = $action['width'];
      $this->height = $action['height'];
   }

   protected function runMove($action) {
      $this->alignment = $action['alignment'];
      $this->x = $action['x'];
      $this->y = $action['y'];
   }

   protected function runResize($action) {
      $args = Library::getResizeArgs($this->width, $this->height, $action['width'], $action['height'], $action['option']);
      if ($args === false) {
         return;
      }
      $newimage = imagecreatetruecolor($args['dst_w'], $args['dst_h']);
      if ($this->format === 'png') {
         imagealphablending($newimage, false);
         imagesavealpha($newimage, true);
         $transparentindex = imagecolorallocatealpha($newimage, 255, 255, 255, 127);
         imagefill($newimage, 0, 0, $transparentindex);
      } else if ($this->format === 'gif') {
         $transparentindex = imagecolorallocatealpha($newimage, 255, 255, 255, 127);
         imagefill($newimage, 0, 0, $transparentindex);
      }
      imagecopyresampled($newimage, $this->resource, $args['dst_x'], $args['dst_y'], $args['src_x'], $args['src_y'], $args['dst_w'], $args['dst_h'], $args['src_w'], $args['src_h']);
      imagedestroy($this->resource);
      $this->resource = $newimage;
      $this->width = $args['dst_w'];
      $this->height = $args['dst_h'];
   }

}

?>