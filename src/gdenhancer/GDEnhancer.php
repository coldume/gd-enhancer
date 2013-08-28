<?php

/**
 * GD Enhancer is a class for PHP that offers an object oriented interface for images edit.
 *
 * @package    GD Enhancer
 * @author     Coldume <coldume@gmail.com>
 * @copyright  2013 Coldume
 * @license    GNU GENERAL PUBLIC LICENSE Version 3
 * @version    GD Enhancer 3.08
 * @link       http://www.gdenhancer.com/
 */

namespace gdenhancer;

use \gdenhancer\models\Actions;
use \gdenhancer\models\Run;

include 'models' . DIRECTORY_SEPARATOR . 'Actions.php';
include 'models' . DIRECTORY_SEPARATOR . 'Run.php';

class GDEnhancer {

   protected $actions;

   public function __construct($images) {
      $this->actions = new Actions($images);
   }

   public function backgroundResize($width, $height, $option = 'shrink') {
      $this->actions->backgroundResize($width, $height, $option);
   }

   public function backgroundFill($color) {
      $this->actions->backgroundFill($color);
   }

   public function layerText($text, $fontfile, $fontsize, $fontcolor, $angle = 0, $linespacing = 1) {
      $this->actions->layerText($text, $fontfile, $fontsize, $fontcolor, $angle, $linespacing);
   }

   public function layerImage($image) {
      $this->actions->layerImage($image);
   }

   public function layerMove($key, $alignment, $x = 0, $y = 0) {
      $this->actions->layerMove($key, $alignment, $x, $y);
   }

   public function layerTextBlock($key, $blockpadding, $blockcolor) {
      $this->actions->layerTextBlock($key, $blockpadding, $blockcolor);
   }

   public function layerImageResize($key, $width, $height, $option = 'shrink') {
      $this->actions->layerImageResize($key, $width, $height, $option);
   }

   public function save($format = 'default', $flag = true) {
      $this->actions->saveFormat($format);
      $this->actions->GIFFlag($flag);
      $run = new Run($this->actions);
      return $run->save;
   }

}

?>