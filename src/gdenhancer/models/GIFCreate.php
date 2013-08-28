<?php

namespace gdenhancer\models;

class GIFCreate {

   public $contents;
   protected $logicalscreenwidth;
   protected $logicalscreenheight;
   protected $frames;

   public function __construct($frames) {
      $this->framesParse($frames);
      $this->setContents();
   }

   protected function framesParse($frames) {
      foreach ($frames as $framekey => $frame) {
         $this->frames[$framekey]['disposalmethod'] = $frame['disposalmethod'];
         $this->frames[$framekey]['delaytime'] = $frame['delaytime'];
         $gif = new GIFParse($frame['fileobject'], false);
         if ($framekey === 0) {
            $this->logicalscreenwidth = $gif->logicalscreenwidth;
            $this->logicalscreenheight = $gif->logicalscreenheight;
         }
         if (@$gif->frames[0]['localcolortableflag'] === '1') {
            $this->frames[$framekey]['localcolortable'] = $gif->frames[0]['localcolortable'];
            $this->frames[$framekey]['localcolortablesize'] = $gif->frames[0]['localcolortablesize'];
         } else {
            $this->frames[$framekey]['localcolortable'] = $gif->globalcolortable;
            $this->frames[$framekey]['localcolortablesize'] = $gif->globalcolortablesize;
         }
         if (isset($gif->frames[0]['graphiccontrolextension']) === true) {
            $this->frames[$framekey]['transparentcolorflag'] = $gif->frames[0]['transparentcolorflag'];
            if ($this->frames[$framekey]['transparentcolorflag'] === '1') {
               $this->frames[$framekey]['transparentcolorindex'] = $gif->frames[0]['transparentcolorindex'];
            }
         } else {
            $this->frames[$framekey]['transparentcolorflag'] = '0';
         }
         $this->frames[$framekey]['imagedata'] = $gif->frames[0]['imagedata'];
      }
   }

   protected function setContents() {
      $this->contents = "\x47\x49\x46\x38\x39\x61";
      $this->contents .= $this->logicalscreenwidth;
      $this->contents .= $this->logicalscreenheight;
      $this->contents .= "\x11\x00\x00";
      $this->contents .= "\x21\xFF\x0B\x4E\x45\x54\x53\x43\x41\x50\x45\x32\x2E\x30\x03\x01\x00\x00\x00";
      foreach ($this->frames as $frame) {
         $this->contents .= "\x21\xF9\x04";
         $this->contents .= Library::getBinaryStringFrom8BitString($frame['disposalmethod'] . '0' . $frame['transparentcolorflag']);
         $this->contents .= $frame['delaytime'];
         if ($frame['transparentcolorflag'] === '1') {
            $this->contents .= $frame['transparentcolorindex'];
         } else {
            $this->contents .= "\x00";
         }
         $this->contents .= "\x00";
         $this->contents .= "\x2C\x00\x00\x00\x00";
         $this->contents .= $this->logicalscreenwidth;
         $this->contents .= $this->logicalscreenheight;
         $this->contents .= Library::getBinaryStringFrom8BitString('10000' . $frame['localcolortablesize']);
         $this->contents .= $frame['localcolortable'];
         $this->contents .= $frame['imagedata'];
      }
      $this->contents .= "\x3B";
   }
}

?>
