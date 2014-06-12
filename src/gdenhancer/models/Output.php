<?php

namespace gdenhancer\models;

class Output {

   protected $background;
   protected $layers;
   protected $format;
   protected $fill;
   protected $resource;

   public function __construct($actions, $background, $layers = null) {
      $this->runActions($actions);
      $this->setBackground($background);
      $this->setLayers($layers);
      $this->setResource();
      $this->merge();
   }

   public function __destruct() {
      @imagedestroy($this->resource);
   }

   public function save($quality = 100) {
      $contents = Library::getContentsFromGDResource($this->resource, $this->format, $quality);
      $format = $this->format;
      $extension = $this->getExtensionFromFormat($format);
      $mime = $this->getMimeFromFormat($format);
      return array(
          'contents'  => $contents,
          'format'    => $format,
          'extension' => $extension,
          'mime'      => $mime
      );
   }

   protected function runActions($actions) {
      $this->runFormat($actions['format']);
      foreach ($actions as $actionname => $action) {
         switch ($actionname) {
            case 'fill':
               $this->runFill($action);
               break;
         }
      }
   }

   protected function runFormat($action) {
      $this->format = $action;
   }

   protected function runFill($action) {
      $this->fill = $action;
   }

   protected function setBackground($background) {
      $this->background = $background;
   }

   protected function setLayers($layers) {
      $this->layers = $layers;
   }

   protected function setResource() {
      $this->resource = imagecreatetruecolor($this->background->width, $this->background->height);
      if (isset($this->fill) === true) {
         $backgroundindex = imagecolorallocate($this->resource, $this->fill['red'], $this->fill['green'], $this->fill['blue']);
         imagefill($this->resource, 0, 0, $backgroundindex);
      } else if ($this->format === 'gif') {
         $backgroundindex = imagecolorallocatealpha($this->resource, 255, 255, 255, 127);
         imagefill($this->resource, 0, 0, $backgroundindex);
         imagecolortransparent($this->resource, $backgroundindex);
      } else if ($this->format === 'jpeg') {
         $backgroundindex = imagecolorallocate($this->resource, 255, 255, 255);
         imagefill($this->resource, 0, 0, $backgroundindex);
      } else if ($this->format === 'png') {
         imagealphablending($this->resource, false);
         imagesavealpha($this->resource, true);
         $backgroundindex = imagecolorallocatealpha($this->resource, 255, 255, 255, 127);
         imagefill($this->resource, 0, 0, $backgroundindex);
         imagealphablending($this->resource, true);
      }
   }

   protected function merge() {
      if (isset($this->fill) === false) {
         if ($this->format === 'gif' && $this->background->format === 'png' && imageistruecolor($this->background->resource) === true) {
            $this->renderAlphaForPalette($this->resource, $this->background->resource, 0, 0, 0, 0, $this->background->dimensions['width'], $this->background->dimensions['height']);
         }
      }
      imagecopy($this->resource, $this->background->resource, 0, 0, 0, 0, $this->background->width, $this->background->height);
      if (isset($this->layers) === true) {
         foreach ($this->layers as $layer) {
            $args = $this->getCopyArgs($this->background->width, $this->background->height, $layer->width, $layer->height, $layer->alignment, $layer->x, $layer->y);
            if ($args === false) {
               continue;
            }
            if (isset($this->fill) === false && $this->format === 'gif') {
               if ($layer::TYPE === 'text') {
                  $this->renderAlphaForPalette($this->resource, $layer->resource, $args['dstx'], $args['dsty'], $args['srcx'], $args['srcy'], $args['srcw'], $args['srch']);
               } else if ($layer::TYPE === 'image') {
                  if ($layer->format === 'png' && imageistruecolor($layer->resource) === true) {
                     $this->renderAlphaForPalette($this->resource, $layer->resource, $args['dstx'], $args['dsty'], $args['srcx'], $args['srcy'], $args['srcw'], $args['srch']);
                  }
               }
            }
            imagecopy($this->resource, $layer->resource, $args['dstx'], $args['dsty'], $args['srcx'], $args['srcy'], $args['srcw'], $args['srch']);
         }
      }
   }

   protected function getCopyArgs($backgroundwidth, $backgroundheight, $layerwidth, $layerheight, $alignment, $x, $y) {
      switch ($alignment) {
         case 'topleft':
            $x += 0;
            $y += 0;
            break;
         case 'topcenter':
            $x += round(($backgroundwidth - $layerwidth) / 2);
            $y += 0;
            break;
         case 'topright':
            $x += $backgroundwidth - $layerwidth;
            $y += 0;
            break;
         case 'centerleft':
            $x += 0;
            $y += round(($backgroundheight - $layerheight) / 2);
            break;
         case 'center':
            $x += round(($backgroundwidth - $layerwidth) / 2);
            $y += round(($backgroundheight - $layerheight) / 2);
            break;
         case 'centerright':
            $x += $backgroundwidth - $layerwidth;
            $y += round(($backgroundheight - $layerheight) / 2);
            break;
         case 'bottomleft':
            $x += 0;
            $y += $backgroundheight - $layerheight;
            break;
         case 'bottomcenter':
            $x += round(($backgroundwidth - $layerwidth) / 2);
            $y += $backgroundheight - $layerheight;
            break;
         case 'bottomright':
            $x += $backgroundwidth - $layerwidth;
            $y += $backgroundheight - $layerheight;
      }
      if ($x <= -$layerwidth || $x >= $backgroundwidth || $y <= -$layerheight || $y >= $backgroundheight) {
         return false;
      }
      if ($x <= 0) {
         $dstx = 0;
         $srcx = -$x;
         $srcw = min(($layerwidth + $x), $backgroundwidth);
      } else {
         $dstx = $x;
         $srcx = 0;
         $srcw = min(($backgroundwidth - $x), $layerwidth);
      }
      if ($y <= 0) {
         $dsty = 0;
         $srcy = -$y;
         $srch = min(($layerheight + $y), $backgroundheight);
      } else {
         $dsty = $y;
         $srcy = 0;
         $srch = min(($backgroundheight - $y), $layerheight);
      }
      return array('dstx' => $dstx, 'dsty' => $dsty, 'srcx' => $srcx, 'srcy' => $srcy, 'srcw' => $srcw, 'srch' => $srch);
   }

   protected function renderAlphaForPalette(&$dst, $src, $dstx, $dsty, $srcx, $srcy, $srcw, $srch) {
      $partialtransparent = array();
      imagealphablending($src, false);
      imagesavealpha($src, true);
      $width = $srcx + $srcw - 1;
      $height = $srcy + $srch - 1;
      $x = $srcx;
      $y = $srcy;
      while ($y <= $height) {
         $index = imagecolorat($src, $x, $y);
         $color = imagecolorsforindex($src, $index);
         if ($color['alpha'] > 0 && $color['alpha'] < 127) {
            $partialtransparent[] = array('x' => ($x - $srcx), 'y' => ($y - $srcy));
         }
         if ($x < $width) {
            $x++;
         } else if ($x === $width && $y < $height) {
            $x = $srcx;
            $y++;
         } else if ($x === $width && $y === $height) {
            break;
         }
      }
      $whiteindex = imagecolorresolve($dst, 255, 255, 255);
      foreach ($partialtransparent as $value) {
         $index = imagecolorat($dst, $value['x'] + $dstx, $value['y'] + $dsty);
         $color = imagecolorsforindex($dst, $index);
         if ($color['alpha'] === 127) {
            imagesetpixel($dst, $value['x'] + $dstx, $value['y'] + $dsty, $whiteindex);
         }
      }
   }

   protected function getMimeFromFormat($format) {
      switch ($format) {
         case 'gif':
            return 'image/gif';
            break;
         case 'jpeg':
            return 'image/jpeg';
            break;
         case 'png':
            return'image/png';
      }
   }

   protected function getExtensionFromFormat($format) {
      switch ($format) {
         case 'gif':
            return 'gif';
            break;
         case 'jpeg':
            return 'jpg';
            break;
         case 'png':
            return 'png';
      }
   }

}

?>
