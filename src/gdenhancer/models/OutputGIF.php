<?php

namespace gdenhancer\models;

class OutputGIF extends Output {

   protected $layersresource;
   protected $layerscoordinates;
   protected $frames;

   public function __construct($actions, $background, $layers) {
      $this->runActions($actions);
      $this->setBackground($background);
      if (isset($layers) === true) {
         $this->setLayers($layers);
         $this->setLayersResource();
      }
      $this->setFrames();
      $this->setContents();
   }

   public function __destruct() {
      @imagedestroy($this->layersresource);
   }

   public function save($quality = 100) {
      $contents = $this->contents;
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

   protected function setlayersResource() {
      $this->layersresource = imagecreatetruecolor($this->background->width, $this->background->height);
      $backgroundindex = imagecolorallocatealpha($this->layersresource, 255, 255, 255, 127);
      imagecolortransparent($this->layersresource, $backgroundindex);
      imagefill($this->layersresource, 0, 0, $backgroundindex);
      foreach ($this->layers as $layer) {
         $args = $this->getCopyArgs($this->background->width, $this->background->height, $layer->width, $layer->height, $layer->alignment, $layer->x, $layer->y);
         if ($args === false) {
            continue;
         }
         if ($layer::TYPE === 'image') {
            if ($this->format === 'gif' && $layer->format === 'png' && imageistruecolor($layer->resource) === true) {
               $this->renderAlphaForPalette($this->layersresource, $layer->resource, $args['dstx'], $args['dsty'], $args['srcx'], $args['srcy'], $args['srcw'], $args['srch']);
            }
         }
         imagecopy($this->layersresource, $layer->resource, $args['dstx'], $args['dsty'], $args['srcx'], $args['srcy'], $args['srcw'], $args['srch']);
      }
   }

   protected function setLayersCoordinates() {
      $this->layerscoordinates = array();
      $width = $this->background->width - 1;
      $height = $this->background->height - 1;
      $x = 0;
      $y = 0;
      while ($y <= $height) {
         $index = imagecolorat($this->layersresource, $x, $y);
         $color = imagecolorsforindex($this->layersresource, $index);
         if ($color['alpha'] !== 127) {
            $this->layerscoordinates[] = array('x' => $x, 'y' => $y);
         }
         if ($x < $width) {
            $x++;
         } else if ($x === $width && $y < $height) {
            $x = 0;
            $y++;
         } else if ($x === $width && $y === $height) {
            break;
         }
      }
   }

   protected function setFrames() {
      foreach ($this->background->frames as $framekey => $frame) {
         if ($framekey === 0 || $frame['disposalmethod'] === '010' || $this->background->frames[$framekey - 1]['disposalmethod'] === '010') {
            $resource = imagecreatetruecolor($this->background->width, $this->background->height);
            if (isset($this->fill) === true) {
               $backgroundindex = imagecolorallocate($resource, $this->fill['red'], $this->fill['green'], $this->fill['blue']);
               imagefill($resource, 0, 0, $backgroundindex);
            } else {
               $backgroundindex = imagecolorallocatealpha($resource, 255, 255, 255, 127);
               imagefill($resource, 0, 0, $backgroundindex);
               imagecolortransparent($resource, $backgroundindex);
            }
            imagecopy($resource, $frame['resource'], 0, 0, 0, 0, $this->background->width, $this->background->height);
            if (isset($this->layersresource) === true) {
               imagecopy($resource, $this->layersresource, 0, 0, 0, 0, $this->background->width, $this->background->height);
            }
         } else {
            $resource = imagecreatetruecolor($this->background->width, $this->background->height);
            $transparentindex = imagecolorallocatealpha($resource, 255, 255, 255, 127);
            imagefill($resource, 0, 0, $transparentindex);
            imagecolortransparent($resource, $transparentindex);
            imagecopy($resource, $frame['resource'], 0, 0, 0, 0, $this->background->width, $this->background->height);
            if (isset($this->layersresource) === true) {
               if (isset($this->layerscoordinates) === false) {
                  $this->setLayersCoordinates();
               }
               imagealphablending($resource, false);
               foreach ($this->layerscoordinates as $layerscoordinate) {
                  imagesetpixel($resource, $layerscoordinate['x'], $layerscoordinate['y'], $transparentindex);
               }
            }
         }
         $this->frames[$framekey]['disposalmethod'] = $frame['disposalmethod'];
         $this->frames[$framekey]['delaytime'] = $frame['delaytime'];
         $contents = Library::getContentsFromGDResource($resource, 'gif');
         $this->frames[$framekey]['fileobject'] = Library::getFileObjectFromContents($contents);
      }
   }

   protected function setContents() {
      include_once 'GIFCreate.php';
      $gif = new GIFCreate($this->frames);
      $this->contents = $gif->contents;
   }

}

?>
