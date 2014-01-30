<?php

namespace gdenhancer\models;

class GIFSplit {

   public $frames;
   protected $gif;

   public function __construct($fileobject) {
      $this->setGIF($fileobject);
      $this->setFrames();
   }

   protected function setGIF($fileobject) {
      include_once 'GIFParse.php';
      $this->gif = new GIFParse($fileobject, true);
   }

   protected function setFrames() {
      foreach ($this->gif->frames as $framekey => $frame) {
         $contents = "\x47\x49\x46\x38\x39\x61";
         $contents .= $this->gif->logicalscreendescriptor;
         if ($this->gif->globalcolortableflag === '1') {
            $contents .= $this->gif->globalcolortable;
         }
         $contents .= $frame['graphiccontrolextension'];
         $contents .= $frame['imagedescriptor'];
         if ($frame['localcolortableflag'] === '1') {
            $contents .= $frame['localcolortable'];
         }
         $contents .= $frame['imagedata'];
         $contents .= "\x3B";
         $oldimage = @imagecreatefromstring($contents);
         if ($oldimage === false) {
            throw new \exception('Malformed GIF');
         }
         $width = Library::getUnsignedShortFromBinaryString($this->gif->logicalscreenwidth);
         $height = Library::getUnsignedShortFromBinaryString($this->gif->logicalscreenheight);
         $newimage = imagecreatetruecolor($width, $height);
         $transparentcolorindex = imagecolorallocatealpha($newimage, 225, 225, 225, 127);
         imagefill($newimage, 0, 0, $transparentcolorindex);
         imagecolortransparent($newimage, $transparentcolorindex);
         $dst_x = Library::getUnsignedShortFromBinaryString($frame['leftposition']);
         $dst_y = Library::getUnsignedShortFromBinaryString($frame['topposition']);
         $src_w = Library::getUnsignedShortFromBinaryString($frame['width']);
         $src_h = Library::getUnsignedShortFromBinaryString($frame['height']);
         imagecopy($newimage, $oldimage, $dst_x, $dst_y, 0, 0, $src_w, $src_h);
         $this->frames[$framekey]['resource'] = $newimage;
         imagedestroy($oldimage);
         $this->frames[$framekey]['delaytime'] = $frame['delaytime'];
         $this->frames[$framekey]['disposalmethod'] = $frame['disposalmethod'];
      }
   }

}

?>
