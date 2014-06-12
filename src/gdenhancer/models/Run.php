<?php

namespace gdenhancer\models;

include_once 'Background.php';
include_once 'Output.php';

class Run {

   public $save;
   protected $actions;
   protected $layers;
   protected $background;
   protected $canvas;

   public function __construct($actions, $quality = 100) {
      $this->setActions($actions);
      if (empty($this->actions->layers) === false) {
         $this->setLayers();
      }
      if ($this->actions->gifflag === true) {
         try {
            $this->setBackgroundGIF();
            $this->setSaveGIF();
         } catch (\Exception $e) {
            $this->setBackground();
            $this->setSave($quality);
         }
      } else {
         $this->setBackground();
         $this->setSave($quality);
      }
   }

   protected function setActions($actions) {
      $this->actions = $actions;
   }

   protected function setLayers() {
      foreach ($this->actions->layers as $layerkey => $layeraction) {
         if (isset($layeraction['image']) === true) {
            $this->layers[$layerkey] = new LayerImage($layeraction);
         } else if (isset($layeraction['text']) === true) {
            $this->layers[$layerkey] = new LayerText($layeraction);
         }
      }
   }

   protected function setBackground() {
      $this->background = new Background($this->actions->background);
   }

   protected function setBackgroundGIF() {
      include_once 'BackgroundGIF.php';
      $this->background = new BackgroundGIF($this->actions->background);
   }

   protected function setSave($quality = 100) {
      $output = new Output($this->actions->output, $this->background, $this->layers);
      $this->save = $output->save($quality);
   }

   protected function setSaveGIF() {
      include_once 'OutputGIF.php';
      $output = new OutputGIF($this->actions->output, $this->background, $this->layers);
      $this->save = $output->save();
   }

}

?>
