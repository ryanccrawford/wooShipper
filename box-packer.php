<?php

use DVDoug;

class Box_Packer extends \DVDoug\BoxPacker\Packer
{



    public $items_that_ship_as_they_are;



    public function __construct()
    {



        $this->items_that_ship_as_they_are = new \DVDoug\BoxPacker\ItemList();



        parent::__construct();
    }





    function pack()
    {



        $packedBoxes = $this->doVolumePacking();



        //If we have multiple boxes, try and optimise/even-out weight distribution



        if ($packedBoxes->count() > 1 && $packedBoxes->count() < static::MAX_BOXES_TO_BALANCE_WEIGHT) {



            $redistributor = new \DVDoug\BoxPacker\WeightRedistributor($this->boxes);



            $redistributor->setLogger($this->logger);



            $packedBoxes = $redistributor->redistributeWeight($packedBoxes);
        }

        //  $this->logger->log(LogLevel::INFO, "packing completed, {$packedBoxes->count()} boxes");



        return $packedBoxes;
    }



    public function doVolumePacking()
    {



        $packedBoxes = new \DVDoug\BoxPacker\PackedBoxList;

        //Keep going until everything packed



        while ($this->items->count()) {





            $boxesToEvaluate = clone $this->boxes;



            $packedBoxesIteration = new \DVDoug\BoxPacker\PackedBoxList;



            //Loop through boxes starting with smallest, see what happens



            while (!$boxesToEvaluate->isEmpty()) {



                $box = $boxesToEvaluate->extract();



                $volumePacker = new \DVDoug\BoxPacker\VolumePacker($box, clone $this->items);



                $volumePacker->setLogger($this->logger);



                $packedBox = $volumePacker->pack();



                if ($packedBox->getItems()->count()) {



                    $packedBoxesIteration->insert($packedBox);



                    //Have we found a single box that contains everything?



                    if ($packedBox->getItems()->count() === $this->items->count()) {



                        break;
                    }
                }
            }

            //Check iteration was productive



            if ($packedBoxesIteration->isEmpty()) {



                $this->items_that_ship_as_they_are->insert($this->items->top());



                break;
            }



            //Find best box of iteration, and remove packed items from unpacked list



            $bestBox = $packedBoxesIteration->top();



            $unPackedItems = $this->items->asArray();



            foreach (clone $bestBox->getItems() as

                $packedItem) {











                foreach ($unPackedItems as $unpackedKey => $unpackedItem) {

                    if ($packedItem === $unpackedItem) {

                        unset($unPackedItems[$unpackedKey]);
                        break;
                    }
                }
            }

            $unpackedItemList = new \DVDoug\BoxPacker\ItemList();

            foreach ($unPackedItems as  $unpackedItem) {

                $unpackedItemList->insert($unpackedItem);
            }



            $this->items = $unpackedItemList;



            $packedBoxes->insert($bestBox);
        }

        $unpacked_boxes = $this->pack_unpacked_items_individually();
        if (count($unpacked_boxes) > 0) {

            foreach ($unpacked_boxes as $pbox) {
                $packedBoxes->insert($pbox);
            }
        }

        return $packedBoxes;
    }

    public function pack_unpacked_items_individually()
    {
        if (count($this->items) < 1) {
            return new \DVDoug\BoxPacker\PackedBoxList();
        }
        return $this->convert_items_to_boxes($this->items);
    }

    public function convert_items_to_boxes(DVDoug\BoxPacker\ItemList $items)
    {


        $packedBoxlist = new \DVDoug\BoxPacker\PackedBoxList();

        if (count(!($items->isEmpty())) > 0) {




            while (!($items->isEmpty())) {

                $extract = $items->extract();

                $outerL = $extract->getLength();

                $outerW = $extract->getWidth();

                $outerD = $extract->getDepth();

                $itemwt = $extract->getWeight();

                $emptyWeight = 0;

                $reference = $extract->getDescription();

                $box = new packing_box($reference, $outerW, $outerL, $outerD, $emptyWeight, $outerL, $outerW, $outerD, $itemwt);

                $itemListTemp = new DVDoug\BoxPacker\ItemList();

                $itemListTemp->insert($extract);

                /* @var $outerW type                                         $box,     $itemList, $remainingWidth, $remainingLength,$remainingDepth, $remainingWeight, $usedWidth,$usedLength, $usedDepth */
                $packedbox = new \DVDoug\BoxPacker\PackedBox($box, $itemListTemp, 0,    0,                   0,                  0,             $outerW, $outerL, $outerD);

                $packedBoxlist->insert($packedbox);
            }
        }

        return  $packedBoxlist;
    }
}
