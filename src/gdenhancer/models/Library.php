<?php

namespace gdenhancer\models;

class Library {

   public static function getUnsignedCharFromBinaryString($binarystring) {
      $unpack = unpack('C', $binarystring);
      return $unpack[1];
   }

   public static function getUnsignedShortFromBinaryString($binarystring) {
      $unpack = unpack('v', $binarystring);
      return $unpack[1];
   }

   public static function get8BitStringFromBinaryString($binarystring) {
      $unpack = unpack('C', $binarystring);
      return sprintf('%08b', $unpack[1]);
   }

   public static function getBinaryStringFrom8BitString($bitstring) {
      return pack('C', base_convert($bitstring, 2, 10));
   }

   public static function getFormatFromContents($contents) {
      $finfo = new \finfo();
      $mimetype = $finfo->buffer($contents, FILEINFO_MIME_TYPE);
      switch ($mimetype) {
         case 'image/jpeg':
            return 'jpeg';
            break;
         case 'image/png':
            return 'png';
            break;
         case 'image/gif':
            return 'gif';
            break;
         default:
            throw new \Exception('Unknown or unsupported image format');
      }
   }

   public static function getContentsFromImage($image) {
      if (is_string($image) === false) {
         throw new \Exception('Invalid image');
      }
      if (@is_file($image) === true) {
         return file_get_contents($image);
      } else {
         return $image;
      }
   }

   public static function getGDResourceFromContents($contents) {
      $resource = @imagecreatefromstring($contents);
      if ($resource === false) {
         throw new \Exception('Cannot process image');
      }
      return $resource;
   }

   public static function getContentsFromGDResource($resource, $format) {
      switch ($format) {
         case 'gif':
            $imagexxx = 'imagegif';
            break;
         case 'jpeg':
            $imagexxx = 'imagejpeg';
            break;
         case 'png':
            $imagexxx = 'imagepng';
      }
      ob_start();
      $imagexxx($resource);
      $contents = ob_get_contents();
      ob_end_clean();
      return $contents;
   }

   public static function getFileObjectFromContents($contents) {
      $fileobject = new \SplTempFileObject();
      $fileobject->fwrite($contents);
      $fileobject->rewind();
      return $fileobject;
   }

   public static function getResizeArgs($oldwidth, $oldheight, $newwidth, $newheight, $option) {
      if ($option === 'stretch') {
         if ($oldwidth === $newwidth && $oldheight === $newheight) {
            return false;
         }
         $dst_w = $newwidth;
         $dst_h = $newheight;
         $src_w = $oldwidth;
         $src_h = $oldheight;
         $src_x = 0;
         $src_y = 0;
      } else if ($option === 'shrink') {
         if ($oldwidth <= $newwidth && $oldheight <= $newheight) {
            return false;
         } else if ($oldwidth / $oldheight >= $newwidth / $newheight) {
            $dst_w = $newwidth;
            $dst_h = (int) round(($newwidth * $oldheight) / $oldwidth);
         } else {
            $dst_w = (int) round(($newheight * $oldwidth) / $oldheight);
            $dst_h = $newheight;
         }
         $src_x = 0;
         $src_y = 0;
         $src_w = $oldwidth;
         $src_h = $oldheight;
      } else if ($option === 'fill') {
         if ($oldwidth === $newwidth && $oldheight === $newheight) {
            return false;
         }
         if ($oldwidth / $oldheight >= $newwidth / $newheight) {
            $src_w = (int) round(($newwidth * $oldheight) / $newheight);
            $src_h = $oldheight;
            $src_x = (int) round(($oldwidth - $src_w) / 2);
            $src_y = 0;
         } else {
            $src_w = $oldwidth;
            $src_h = (int) round(($oldwidth * $newheight) / $newwidth);
            $src_x = 0;
            $src_y = (int) round(($oldheight - $src_h) / 2);
         }
         $dst_w = $newwidth;
         $dst_h = $newheight;
      }
      if ($src_w < 1 || $src_h < 1) {
         throw new \Exception('Image width or height is too small');
      }
      return array(
          'dst_x' => 0,
          'dst_y' => 0,
          'src_x' => $src_x,
          'src_y' => $src_y,
          'dst_w' => $dst_w,
          'dst_h' => $dst_h,
          'src_w' => $src_w,
          'src_h' => $src_h
      );
   }

   public static function getRGBFromHex($hex) {
      $hex = str_replace("#", "", $hex);
      if (strlen($hex) == 3) {
         $red = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
         $green = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
         $blue = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
      } else {
         $red = hexdec(substr($hex, 0, 2));
         $green = hexdec(substr($hex, 2, 2));
         $blue = hexdec(substr($hex, 4, 2));
      }
      return array('red'   => $red, 'green' => $green, 'blue'  => $blue);
   }

}

?>