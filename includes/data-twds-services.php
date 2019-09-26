<?php

/** * EasyPost Carrier Services and subservices */
return array(
   // Domestic & International  
   'USPS' => array(
      "First" => "First-Class Mail",
      "Priority" => "Priority Mail&#0174;",
      "ParcelSelect" => "USPS Parcel Select",
   ),   'FedEx' => array('services' => array("INTERNATIONAL_ECONOMY" => "FedEx International Economy")),   'UPS' => array('services' => array("Ground" => "Ground (UPS)",                  "3DaySelect" => "3 Day Select (UPS)",                  "2ndDayAirAM" => "2nd Day Air AM (UPS)",                  "2ndDayAir" => "2nd Day Air (UPS)",                  "NextDayAirSaver" => "Next Day Air Saver (UPS)",                  "NextDayAirEarlyAM" => "Next Day Air Early AM (UPS)",                  "NextDayAir" => "Next Day Air (UPS)",                  "Express" => "Express (UPS)",                  "Expedited" => "Expedited (UPS)",                  "ExpressPlus" => "Express Plus (UPS)",                  "UPSSaver" => "UPS Saver (UPS)",                  "UPSStandard" => "UPS Standard (UPS)"))
);
