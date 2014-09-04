<?php

namespace gdenhancer\models;

class LayerText {

   const TYPE = 'text';

   public $resource;
   public $width;
   public $height;
   public $alignment = 'topleft';
   public $x = 0;
   public $y = 0;
   protected $text;
   protected $fontfile;
   protected $fontsize;
   protected $fontcolor;
   protected $angle;
   protected $linespacing;
   protected $blockpadding = array(0, 0, 0, 0);
   protected $blockcolor;

   public function __construct($actions) {
      $this->runActions($actions);
      $this->setResource();
   }

   public function __destruct() {
      @imagedestroy($this->resource);
   }

   protected function runActions($actions) {
      $this->runText($actions['text']);
      foreach ($actions as $actionname => $action) {
         switch ($actionname) {
            case 'text':
               $this->runText($action);
               break;
            case 'textblock':
               $this->runTextBlock($action);
               break;
            case 'move':
               $this->runMove($action);
               break;
         }
      }
   }

   protected function runText($action) {
      $this->text = trim($action['text']);
      $this->fontfile = $action['fontfile'];
      $this->fontcolor = $action['fontcolor'];
      $this->angle = $action['angle'];
      $this->linespacing = $action['linespacing'];
      $this->fontsize = $action['fontsize'];
   }

   protected function runTextBlock($action) {
      $this->blockpadding = $action['blockpadding'];
      $this->blockcolor = $action['blockcolor'];
   }

   protected function runMove($action) {
      $this->alignment = $action['alignment'];
      $this->x = $action['x'];
      $this->y = $action['y'];
   }

   protected function setResource() {
      $ftbbox = imageftbbox($this->fontsize, 0, $this->fontfile, $this->text, array('linespacing' => $this->linespacing));
      $bugfix = ceil($this->fontsize/5);                                        
      $ftboxwidth = abs($ftbbox[0] - $ftbbox[2]) + $bugfix; 
      $ftboxheight = abs($ftbbox[1] - $ftbbox[7]);
      $blockwidth = $ftboxwidth + $this->blockpadding[1] + $this->blockpadding[3];
      $blockheight = $ftboxheight + $this->blockpadding[0] + $this->blockpadding[2];
      $texttempx = $this->blockpadding[3];
      $texttempy = $this->blockpadding[0] - $ftbbox[7] - 1;
      $anglerad = deg2rad($this->angle);
      $textradius = sqrt(pow(abs($texttempx - $blockwidth / 2), 2) + pow(abs($texttempy - $blockheight / 2), 2));
      $blockradius = sqrt(pow($blockwidth, 2) + pow($blockheight, 2)) / 2;
      $blockx[0] = cos(atan2($blockheight / 2, -$blockwidth / 2) - $anglerad) * $blockradius + $blockwidth / 2;
      $blockx[1] = cos(atan2($blockheight / 2, $blockwidth / 2) - $anglerad) * $blockradius + $blockwidth / 2;
      $blockx[2] = cos(atan2(-$blockheight / 2, $blockwidth / 2) - $anglerad) * $blockradius + $blockwidth / 2;
      $blockx[3] = cos(atan2(-$blockheight / 2, -$blockwidth / 2) - $anglerad) * $blockradius + $blockwidth / 2;
      $blocky[0] = sin(atan2($blockheight / 2, -$blockwidth / 2) - $anglerad) * $blockradius + $blockheight / 2;
      $blocky[1] = sin(atan2($blockheight / 2, $blockwidth / 2) - $anglerad) * $blockradius + $blockheight / 2;
      $blocky[2] = sin(atan2(-$blockheight / 2, $blockwidth / 2) - $anglerad) * $blockradius + $blockheight / 2;
      $blocky[3] = sin(atan2(-$blockheight / 2, -$blockwidth / 2) - $anglerad) * $blockradius + $blockheight / 2;
      $minblockx = min($blockx[0], $blockx[1], $blockx[2], $blockx[3]);
      $minblocky = min($blocky[0], $blocky[1], $blocky[2], $blocky[3]);
      $blockx[0] = (int) round($blockx[0] - $minblockx);
      $blockx[1] = (int) round($blockx[1] - $minblockx);
      $blockx[2] = (int) round($blockx[2] - $minblockx);
      $blockx[3] = (int) round($blockx[3] - $minblockx);
      $blocky[0] = (int) round($blocky[0] - $minblocky);
      $blocky[1] = (int) round($blocky[1] - $minblocky);
      $blocky[2] = (int) round($blocky[2] - $minblocky);
      $blocky[3] = (int) round($blocky[3] - $minblocky);
      $textx = (int) round(cos(atan2($texttempy - $blockheight / 2, $texttempx - $blockwidth / 2) - $anglerad) * $textradius + $blockwidth / 2 - $minblockx);
      $texty = (int) round(sin(atan2($texttempy - $blockheight / 2, $texttempx - $blockwidth / 2) - $anglerad) * $textradius + $blockheight / 2 - $minblocky);
      $this->width = max(abs($blockx[0] - $blockx[2]), abs($blockx[1] - $blockx[3]));
      $this->height = max(abs($blocky[0] - $blocky[2]), abs($blocky[1] - $blocky[3]));
      $this->resource = imagecreatetruecolor($this->width, $this->height);
      $transparentcolor = imagecolorallocatealpha($this->resource, 255, 255, 255, 127);
      imagefill($this->resource, 0, 0, $transparentcolor);
      if (isset($this->blockcolor) === true) {
         $blockcolor = imagecolorallocate($this->resource, $this->blockcolor['red'], $this->blockcolor['green'], $this->blockcolor['blue']);
         if (function_exists('imageantialias')) {
             imageantialias($this->resource, true);
         }
         imagefilledpolygon($this->resource, array($blockx[0], $blocky[0], $blockx[1], $blocky[1], $blockx[2], $blocky[2], $blockx[3], $blocky[3]), 4, $blockcolor);
      }
      $fontcolor = imagecolorallocate($this->resource, $this->fontcolor['red'], $this->fontcolor['green'], $this->fontcolor['blue']);
      imagefttext($this->resource, $this->fontsize, $this->angle, $textx, $texty, $fontcolor, $this->fontfile, $this->text, array('linespacing' => $this->linespacing));
   }

}

?>
