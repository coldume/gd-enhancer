<?php

namespace gdenhancer\models;

include_once 'Library.php';

class Actions {

   public $background;
   public $layers;
   public $output;
   public $gifflag;

   public function __construct($image) {
      $this->backgroundImage($image);
   }

   protected function backgroundImage($image) {
      $contents = Library::getContentsFromImage($image);
      $format = Library::getFormatFromContents($contents);
      $resource = Library::getGDResourceFromContents($contents);
      $width = imagesx($resource);
      $height = imagesy($resource);
      $this->background['image'] = array(
          'contents' => $contents,
          'format'   => $format,
          'resource' => $resource,
          'width'    => $width,
          'height'   => $height,
      );
   }

   public function backgroundResize($width, $height, $option) {
      $this->background['resize'] = array(
          'width'  => $width,
          'height' => $height,
          'option' => $option
      );
   }

   public function layerText($text, $fontfile, $fontsize, $fontcolor, $angle, $linespacing) {
      include_once 'LayerText.php';
      $fontcolor = Library::getRGBFromHex($fontcolor);
      $this->layers[]['text'] = array(
          'text'        => $text,
          'fontfile'    => $fontfile,
          'fontsize'    => $fontsize,
          'fontcolor'   => $fontcolor,
          'angle'       => $angle,
          'linespacing' => $linespacing,
      );
   }

   public function layerImage($image) {
      include_once 'LayerImage.php';
      $contents = Library::getContentsFromImage($image);
      $format = Library::getFormatFromContents($contents);
      $resource = Library::getGDResourceFromContents($contents);
      $width = imagesx($resource);
      $height = imagesy($resource);
      $this->layers[]['image'] = array(
          'format'   => $format,
          'resource' => $resource,
          'width'    => $width,
          'height'   => $height,
      );
   }

   public function layerTextBlock($key, $blockpadding, $blockcolor) {
      $blockcolor = Library::getRGBFromHex($blockcolor);
      $this->layers[$key]['textblock'] = array(
          'blockcolor'   => $blockcolor,
          'blockpadding' => $blockpadding
      );
   }

   public function layerMove($key, $alignment, $x, $y) {
      $this->layers[$key]['move'] = array(
          'alignment' => $alignment,
          'x'         => $x,
          'y'         => $y
      );
   }

   public function layerImageResize($key, $width, $height, $option) {
      $this->layers[$key]['resize'] = array(
          'width'  => $width,
          'height' => $height,
          'option' => $option
      );
   }

   public function backgroundFill($color) {
      $this->output['fill'] = Library::getRGBFromHex($color);
   }

   public function saveFormat($format) {
      if ($format === 'default') {
         $this->output['format'] = $this->background['image']['format'];
      } else {
         $this->output['format'] = $format;
      }
   }

   public function GIFFlag($flag) {
      if ($this->output['format'] === 'gif' && $this->background['image']['format'] === 'gif' && $flag === true) {
         $this->gifflag = true;
      } else {
         $this->gifflag = false;
      }
   }

}

?>
