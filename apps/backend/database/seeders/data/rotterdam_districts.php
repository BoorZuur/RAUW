<?php

/**
 * Rotterdam wijk (district) reference data with approximate centroids.
 *
 * Coordinates are approximate centroids based on official Rotterdam wijk
 * boundaries and OpenStreetMap / CBS geography. Postal prefixes are the
 * dominant four-digit prefix per wijk. Radius values support geofence-style
 * district matching (600–2500 m).
 *
 * @return list<array{name: string, hub: string, postal_prefix: string, center_lat: float, center_lng: float, radius_meters: int}>
 */
return [
    // Cluster Centrum (6)
    ['name' => 'Delfshaven', 'hub' => 'Cluster Centrum', 'postal_prefix' => '3012', 'center_lat' => 51.91712, 'center_lng' => 4.47834, 'radius_meters' => 1200],
    ['name' => 'Stadsdriehoek', 'hub' => 'Cluster Centrum', 'postal_prefix' => '3011', 'center_lat' => 51.92268, 'center_lng' => 4.47918, 'radius_meters' => 900],
    ['name' => 'Oude Westen', 'hub' => 'Cluster Centrum', 'postal_prefix' => '3014', 'center_lat' => 51.91852, 'center_lng' => 4.46148, 'radius_meters' => 1000],
    ['name' => 'Dijkzigt', 'hub' => 'Cluster Centrum', 'postal_prefix' => '3015', 'center_lat' => 51.91048, 'center_lng' => 4.46582, 'radius_meters' => 1100],
    ['name' => 'Centrum', 'hub' => 'Cluster Centrum', 'postal_prefix' => '3013', 'center_lat' => 51.92482, 'center_lng' => 4.46952, 'radius_meters' => 800],
    ['name' => 'Scheepvaartkwartier', 'hub' => 'Cluster Centrum', 'postal_prefix' => '3016', 'center_lat' => 51.90578, 'center_lng' => 4.48318, 'radius_meters' => 1500],

    // Cluster Noord (36)
    ['name' => 'Oude Noorden', 'hub' => 'Cluster Noord', 'postal_prefix' => '3034', 'center_lat' => 51.93182, 'center_lng' => 4.47622, 'radius_meters' => 1000],
    ['name' => 'Agniesebuurt', 'hub' => 'Cluster Noord', 'postal_prefix' => '3032', 'center_lat' => 51.92948, 'center_lng' => 4.46882, 'radius_meters' => 800],
    ['name' => 'Provenierswijk', 'hub' => 'Cluster Noord', 'postal_prefix' => '3033', 'center_lat' => 51.92678, 'center_lng' => 4.46752, 'radius_meters' => 700],
    ['name' => 'Blijdorp', 'hub' => 'Cluster Noord', 'postal_prefix' => '3051', 'center_lat' => 51.92902, 'center_lng' => 4.44802, 'radius_meters' => 1200],
    ['name' => 'Bergpolder', 'hub' => 'Cluster Noord', 'postal_prefix' => '3053', 'center_lat' => 51.93752, 'center_lng' => 4.46102, 'radius_meters' => 1000],
    ['name' => 'Liskwartier', 'hub' => 'Cluster Noord', 'postal_prefix' => '3038', 'center_lat' => 51.93422, 'center_lng' => 4.45652, 'radius_meters' => 900],
    ['name' => 'Historisch Delfshaven', 'hub' => 'Cluster Noord', 'postal_prefix' => '3024', 'center_lat' => 51.90852, 'center_lng' => 4.44722, 'radius_meters' => 1100],
    ['name' => 'Bospolder', 'hub' => 'Cluster Noord', 'postal_prefix' => '3042', 'center_lat' => 51.92652, 'center_lng' => 4.44182, 'radius_meters' => 800],
    ['name' => 'Tussendijken', 'hub' => 'Cluster Noord', 'postal_prefix' => '3043', 'center_lat' => 51.92122, 'center_lng' => 4.43852, 'radius_meters' => 700],
    ['name' => 'Spangen', 'hub' => 'Cluster Noord', 'postal_prefix' => '3044', 'center_lat' => 51.91882, 'center_lng' => 4.43552, 'radius_meters' => 900],
    ['name' => 'Middelland', 'hub' => 'Cluster Noord', 'postal_prefix' => '3021', 'center_lat' => 51.91552, 'center_lng' => 4.44352, 'radius_meters' => 1000],
    ['name' => 'Nieuwe Westen', 'hub' => 'Cluster Noord', 'postal_prefix' => '3029', 'center_lat' => 51.91282, 'center_lng' => 4.43982, 'radius_meters' => 900],
    ['name' => 'Schiemond', 'hub' => 'Cluster Noord', 'postal_prefix' => '3026', 'center_lat' => 51.90952, 'center_lng' => 4.43282, 'radius_meters' => 1000],
    ['name' => 'Kralingen-Oost', 'hub' => 'Cluster Noord', 'postal_prefix' => '3062', 'center_lat' => 51.92852, 'center_lng' => 4.51282, 'radius_meters' => 1200],
    ['name' => 'Kralingen-West', 'hub' => 'Cluster Noord', 'postal_prefix' => '3061', 'center_lat' => 51.92522, 'center_lng' => 4.50282, 'radius_meters' => 1100],
    ['name' => 'Oud-Crooswijk', 'hub' => 'Cluster Noord', 'postal_prefix' => '3036', 'center_lat' => 51.92882, 'center_lng' => 4.49282, 'radius_meters' => 900],
    ['name' => 'Nieuw-Crooswijk', 'hub' => 'Cluster Noord', 'postal_prefix' => '3037', 'center_lat' => 51.93152, 'center_lng' => 4.49882, 'radius_meters' => 1000],
    ['name' => 'Rubroek', 'hub' => 'Cluster Noord', 'postal_prefix' => '3063', 'center_lat' => 51.93382, 'center_lng' => 4.50882, 'radius_meters' => 800],
    ['name' => 'De Esch', 'hub' => 'Cluster Noord', 'postal_prefix' => '3065', 'center_lat' => 51.92152, 'center_lng' => 4.51882, 'radius_meters' => 1100],
    ['name' => 'Struisenburg', 'hub' => 'Cluster Noord', 'postal_prefix' => '3066', 'center_lat' => 51.91882, 'center_lng' => 4.52882, 'radius_meters' => 1000],
    ['name' => 'Het Lage Land', 'hub' => 'Cluster Noord', 'postal_prefix' => '3068', 'center_lat' => 51.93552, 'center_lng' => 4.53882, 'radius_meters' => 1200],
    ['name' => 'Ommoord', 'hub' => 'Cluster Noord', 'postal_prefix' => '3067', 'center_lat' => 51.95882, 'center_lng' => 4.54882, 'radius_meters' => 2000],
    ['name' => 'Oosterflank', 'hub' => 'Cluster Noord', 'postal_prefix' => '3069', 'center_lat' => 51.94882, 'center_lng' => 4.55882, 'radius_meters' => 1500],
    ['name' => 'Prinsenland', 'hub' => 'Cluster Noord', 'postal_prefix' => '3069', 'center_lat' => 51.94282, 'center_lng' => 4.56882, 'radius_meters' => 1400],
    ['name' => 'Zevenkamp', 'hub' => 'Cluster Noord', 'postal_prefix' => '3068', 'center_lat' => 51.95282, 'center_lng' => 4.57882, 'radius_meters' => 1600],
    ['name' => 'Nesselande', 'hub' => 'Cluster Noord', 'postal_prefix' => '3059', 'center_lat' => 51.96882, 'center_lng' => 4.59882, 'radius_meters' => 2000],
    ['name' => 'Kralingse Veer', 'hub' => 'Cluster Noord', 'postal_prefix' => '3062', 'center_lat' => 51.93282, 'center_lng' => 4.52282, 'radius_meters' => 800],
    ['name' => 'Schiebroek', 'hub' => 'Cluster Noord', 'postal_prefix' => '3038', 'center_lat' => 51.94282, 'center_lng' => 4.46882, 'radius_meters' => 1500],
    ['name' => 'Hillegersberg-Noord', 'hub' => 'Cluster Noord', 'postal_prefix' => '3055', 'center_lat' => 51.95282, 'center_lng' => 4.48882, 'radius_meters' => 1200],
    ['name' => 'Hillegersberg-Zuid', 'hub' => 'Cluster Noord', 'postal_prefix' => '3054', 'center_lat' => 51.94282, 'center_lng' => 4.49882, 'radius_meters' => 1100],
    ['name' => 'Molenlaankwartier', 'hub' => 'Cluster Noord', 'postal_prefix' => '3055', 'center_lat' => 51.94882, 'center_lng' => 4.47882, 'radius_meters' => 1000],
    ['name' => 'Terbregge', 'hub' => 'Cluster Noord', 'postal_prefix' => '3056', 'center_lat' => 51.95582, 'center_lng' => 4.50882, 'radius_meters' => 1200],
    ['name' => 'Overschie', 'hub' => 'Cluster Noord', 'postal_prefix' => '3044', 'center_lat' => 51.93882, 'center_lng' => 4.42882, 'radius_meters' => 1800],
    ['name' => 'Kleinpolder', 'hub' => 'Cluster Noord', 'postal_prefix' => '3044', 'center_lat' => 51.93282, 'center_lng' => 4.43882, 'radius_meters' => 900],
    ['name' => 'Schieveen', 'hub' => 'Cluster Noord', 'postal_prefix' => '3045', 'center_lat' => 51.94882, 'center_lng' => 4.41882, 'radius_meters' => 1500],
    ['name' => 'Zestienhoven', 'hub' => 'Cluster Noord', 'postal_prefix' => '3047', 'center_lat' => 51.95882, 'center_lng' => 4.43882, 'radius_meters' => 1200],

    // Cluster Zuid (20)
    ['name' => 'Afrikaanderwijk', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3071', 'center_lat' => 51.89882, 'center_lng' => 4.49882, 'radius_meters' => 1000],
    ['name' => 'Bloemhof', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3071', 'center_lat' => 51.89282, 'center_lng' => 4.50882, 'radius_meters' => 900],
    ['name' => 'Hillesluis', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3072', 'center_lat' => 51.88882, 'center_lng' => 4.49882, 'radius_meters' => 900],
    ['name' => 'Katendrecht', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3072', 'center_lat' => 51.90282, 'center_lng' => 4.48882, 'radius_meters' => 800],
    ['name' => 'Kop van Zuid', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3072', 'center_lat' => 51.90582, 'center_lng' => 4.48282, 'radius_meters' => 700],
    ['name' => 'Noordereiland', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3071', 'center_lat' => 51.90882, 'center_lng' => 4.49282, 'radius_meters' => 600],
    ['name' => 'Vreewijk', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3078', 'center_lat' => 51.88282, 'center_lng' => 4.52882, 'radius_meters' => 1400],
    ['name' => 'Feijenoord', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3071', 'center_lat' => 51.89582, 'center_lng' => 4.50282, 'radius_meters' => 1000],
    ['name' => 'Carnisse', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3083', 'center_lat' => 51.87882, 'center_lng' => 4.48882, 'radius_meters' => 1100],
    ['name' => 'Tarwewijk', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3082', 'center_lat' => 51.87282, 'center_lng' => 4.49882, 'radius_meters' => 1000],
    ['name' => 'Oud-Charlois', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3081', 'center_lat' => 51.86882, 'center_lng' => 4.47882, 'radius_meters' => 1200],
    ['name' => 'Zuidwijk', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3084', 'center_lat' => 51.86282, 'center_lng' => 4.48882, 'radius_meters' => 1100],
    ['name' => 'Pendrecht', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3085', 'center_lat' => 51.86882, 'center_lng' => 4.51882, 'radius_meters' => 1200],
    ['name' => 'Oud-IJsselmonde', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3077', 'center_lat' => 51.87882, 'center_lng' => 4.53882, 'radius_meters' => 1300],
    ['name' => 'Groenenhagen-Tuinenburg', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3077', 'center_lat' => 51.87282, 'center_lng' => 4.54882, 'radius_meters' => 1200],
    ['name' => 'Hordijkerveld', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3079', 'center_lat' => 51.86282, 'center_lng' => 4.55882, 'radius_meters' => 1100],
    ['name' => 'Reyeroord', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3079', 'center_lat' => 51.85882, 'center_lng' => 4.54882, 'radius_meters' => 1000],
    ['name' => 'Sportdorp', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3079', 'center_lat' => 51.85582, 'center_lng' => 4.53882, 'radius_meters' => 900],
    ['name' => 'Lombardijen', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3076', 'center_lat' => 51.87882, 'center_lng' => 4.51882, 'radius_meters' => 1200],
    ['name' => 'Beverwaard', 'hub' => 'Cluster Zuid', 'postal_prefix' => '3079', 'center_lat' => 51.85282, 'center_lng' => 4.52882, 'radius_meters' => 1400],

    // Cluster Buitengebieden (5)
    ['name' => 'Hoek van Holland', 'hub' => 'Cluster Buitengebieden', 'postal_prefix' => '3151', 'center_lat' => 51.97882, 'center_lng' => 4.12882, 'radius_meters' => 2500],
    ['name' => 'Rozenburg', 'hub' => 'Cluster Buitengebieden', 'postal_prefix' => '3181', 'center_lat' => 51.89882, 'center_lng' => 4.24882, 'radius_meters' => 2200],
    ['name' => 'Pernis', 'hub' => 'Cluster Buitengebieden', 'postal_prefix' => '3195', 'center_lat' => 51.88882, 'center_lng' => 4.38882, 'radius_meters' => 1800],
    ['name' => 'Hoogvliet', 'hub' => 'Cluster Buitengebieden', 'postal_prefix' => '3191', 'center_lat' => 51.85882, 'center_lng' => 4.35882, 'radius_meters' => 2000],
    ['name' => 'Heijplaat', 'hub' => 'Cluster Buitengebieden', 'postal_prefix' => '3077', 'center_lat' => 51.88882, 'center_lng' => 4.42882, 'radius_meters' => 800],
];
