<?php

return[
    'weights' => [
        'distance' => 0.3,
        'travel_time' => 0.3,
        'coverage' => 0.3,
        'cold_chain' => 0.2,
    ],
    'eta' => [
        'bandwith_hours' => 3.0,  // how quickly a record's influence fades with time-of-week gap
        'prior_weight' => 1.0,    // pull toward the warehouse's overall average
    ],
    'new_shipment_penalty' => 0.15,  // score lost when a line opens an extra shipment
    'consolidation_bonus'  => 0.10,  // score gained for a warehouse that can also cover the other lines
    'urgent_eta_tolerance' => 15,    // minutes an urgent line may be slower before the order is split
];

?>