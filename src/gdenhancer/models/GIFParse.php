<?php

namespace gdenhancer\models;

class GIFParse {

   public $header;
   public $logicalscreendescriptor;
   public $logicalscreenwidth;
   public $logicalscreenheight;
   public $globalcolortableflag;
   public $globalcolortablesize;
   public $globalcolortable;
   public $netscape2;
   public $frames;
   protected $animationcheckflag;
   protected $fileobject;
   protected $framekey = 0;

   public function __construct($fileobject, $animationcheckflag) {
      $this->setFileObject($fileobject);
      $this->setAnimationCheckFlag($animationcheckflag);
      $this->readGIF();
   }

   protected function setFileObject($fileobject) {
      $this->fileobject = $fileobject;
   }

   protected function setAnimationCheckFlag($animationcheckflag) {
      $this->animationcheckflag = $animationcheckflag;
   }

   protected function readGIF() {
      $this->readHeader();
      if ($this->animationcheckflag === true) {
         $this->checkAnimationHeader();
      }
      $this->readLogicalScreenDescriptor();
      if ($this->globalcolortableflag === '1') {
         $this->readGlobalColorTable();
      }
      while ($this->fileobject->valid() === true) {
         switch ($this->readBytesByLength(1)) {
            case "\x21":
               switch ($this->readBytesByLength(1)) {
                  case "\xFF":
                     if ($this->readBytesByLength(12) === "\x0B\x4E\x45\x54\x53\x43\x41\x50\x45\x32\x2E\x30") {
                        $this->readNetscape2();
                     } else {
                        $this->skipApplicationExtension();
                     }
                     break;
                  case "\xF9":
                     $this->readGraphicControlExtension();
                     break;
                  case "\x01":
                     $this->skipPlainTextExtension();
                     break;
                  case "\xFE":
                     $this->skipCommentExtension();
                     break;
                  default:
                     throw new \Exception();
               }
               break;
            case "\x2C":
               if (isset($this->frames[$this->framekey]['imagedescriptor']) === false) {
                  $this->readImageDLD();
               } else {
                  $this->skipImageDLD();
               }
               break;
            case "\x3B":
               break 2;
            default:
               throw new \Exception();
         }
      }
      if ($this->animationcheckflag === true) {
         $this->checkAnimationNetscape2();
         $this->checkAnimationFrames();
      }
   }

   protected function readHeader() {
      $this->header = $this->readBytesByLength(6);
   }

   protected function readLogicalScreenDescriptor() {
      $this->logicalscreendescriptor = $this->readBytesByLength(7);
      $this->logicalscreenwidth = substr($this->logicalscreendescriptor, 0, 2);
      $this->logicalscreenheight = substr($this->logicalscreendescriptor, 2, 2);
      $bitstring = Library::get8BitStringFromBinaryString(substr($this->logicalscreendescriptor, 4, 1));
      $this->globalcolortableflag = substr($bitstring, 0, 1);
   }

   protected function readGlobalColorTable() {
      $bitstring = Library::get8BitStringFromBinaryString(substr($this->logicalscreendescriptor, 4, 1));
      $this->globalcolortablesize = substr($bitstring, -3);
      $globalcolortablelength = 3 * pow(2, base_convert($this->globalcolortablesize, 2, 10) + 1);
      $this->globalcolortable = $this->readBytesByLength($globalcolortablelength);
   }

   protected function readNetscape2() {
      $this->netscape2 = "\x21\xFF\x0B\x4E\x45\x54\x53\x43\x41\x50\x45\x32\x2E\x30" . $this->readBytesByLength(5);
   }

   protected function readGraphicControlExtension() {
      if (isset($this->frames[$this->framekey]['graphiccontrolextension']) === false) {
         $key = $this->framekey;
         $this->frames[$key] = null;
      } else {
         $key = ++$this->framekey;
      }
      $this->frames[$key]['graphiccontrolextension'] = "\x21\xF9" . $this->readBytesByLength(6);
      $this->frames[$key]['delaytime'] = substr($this->frames[$key]['graphiccontrolextension'], -4, 2);
      $bitstring = Library::get8BitStringFromBinaryString(substr($this->frames[$key]['graphiccontrolextension'], 3, 1));
      $this->frames[$key]['disposalmethod'] = substr($bitstring, 3, 3);
      $this->frames[$key]['transparentcolorflag'] = substr($bitstring, -1);
      if ($this->frames[$key]['transparentcolorflag'] === '1') {
         $this->frames[$key]['transparentcolorindex'] = substr($this->frames[$key]['graphiccontrolextension'], -2, 1);
      }
   }

   protected function readImageDLD() {
      $key = $this->framekey;
      $this->frames[$key]['imagedescriptor'] = "\x2C" . $this->readBytesByLength(9);
      $this->frames[$key]['leftposition'] = substr($this->frames[$key]['imagedescriptor'], 1, 2);
      $this->frames[$key]['topposition'] = substr($this->frames[$key]['imagedescriptor'], 3, 2);
      $this->frames[$key]['width'] = substr($this->frames[$key]['imagedescriptor'], 5, 2);
      $this->frames[$key]['height'] = substr($this->frames[$key]['imagedescriptor'], 7, 2);
      $bitstring = Library::get8BitStringFromBinaryString(substr($this->frames[$key]['imagedescriptor'], -1));
      $this->frames[$key]['localcolortableflag'] = substr($bitstring, 0, 1);
      if ($this->frames[$key]['localcolortableflag'] === '1') {
         $this->frames[$key]['localcolortablesize'] = substr($bitstring, -3);
         $localcolortablelength = 3 * pow(2, base_convert($this->frames[$key]['localcolortablesize'], 2, 10) + 1);
         $this->frames[$key]['localcolortable'] = $this->readBytesByLength($localcolortablelength);
      }
      $this->frames[$key]['imagedata'] = $this->readBytesByLength(1) . $this->readDataSubblocks();
   }

   protected function readDataSubblocks() {
      $datasubblocks = null;
      while (($datasubblocksize = $this->readBytesByLength(1)) !== "\x00") {
         $datasubblocklength = Library::getUnsignedCharFromBinaryString($datasubblocksize);
         $datasubblocks .= $datasubblocksize . $this->readBytesByLength($datasubblocklength);
      }
      $datasubblocks .= "\x00";
      return $datasubblocks;
   }

   protected function skipApplicationExtension() {
      $this->skipDataSubblocks();
   }

   protected function skipPlainTextExtension() {
      $this->skipBytesByLength(13);
      $this->skipDataSubblocks();
   }

   protected function skipCommentExtension() {
      $this->skipDataSubblocks();
   }

   protected function skipDataSubblocks() {
      while (($datasubblocksize = $this->readBytesByLength(1)) !== "\x00") {
         $datasubblocklength = Library::getUnsignedCharFromBinaryString($datasubblocksize);
         $this->skipBytesByLength($datasubblocklength);
      }
   }

   protected function skipImageDLD() {
      $this->skipBytesByLength(8);
      $bitstring = Library::get8BitStringFromBinaryString($this->readBytesByLength(1));
      if (substr($bitstring, 0, 1) === '1') {
         $localcolortablelength = 3 * pow(2, base_convert(substr($bitstring, 2, 1), 2, 10) + 1);
         $this->skipBytesByLength($localcolortablelength);
      }
      $this->skipBytesByLength(1);
      $this->skipDataSubblocks();
   }

   protected function readBytesByLength($length) {
      $i = 0;
      $string = null;
      while ($i < $length) {
         $char = $this->fileobject->fgetc();
         if ($char === false) {
             throw new \Exception('Malformed GIF');
         }
         $string = $string . $char;

         $i++;
      }
      return $string;
   }

   protected function skipBytesByLength($length) {
      $this->fileobject->fseek($length, SEEK_CUR);
   }

   protected function checkAnimationHeader() {
      if ($this->header !== "\x47\x49\x46\x38\x39\x61") {
         throw new \Exception();
      }
   }

   protected function checkAnimationNetscape2() {
      if (isset($this->netscape2) === false) {
         throw new \Exception();
      }
   }

   protected function checkAnimationFrames() {
      if (count($this->frames) < 2) {
         throw new \Exception();
      }
   }

}

?>
