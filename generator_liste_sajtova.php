<?php

$special = [
    #Akademija
    [
        'db'      => 'nasaskola_akad',
        'dbpass'  => 'c42d1a5f',
        'putanja' => 'akademija',
        'url'     => 'http://akademijafilipovic.com/'
    ],
    #buvlja
    [
        'db'      => 'nasaskola_buvlja',
        'dbpass'  => '49206a34',
        'putanja' => 'buvljapijaca',
        'url'     => 'https://buvljapijaca.rs/'
    ],
           
    #fbsoft
    [
        'db'      => 'nasaskola_fbsoft',
        'dbpass'  => '7efa8538',
        'putanja' => 'fbsoft',
        'url'     => 'http://fbsoft.rs/'
    ],
    #nasaskola
    [
        'db'      => 'nasaskola_glavna',
        'dbpass'  => 'da90ce7a',
        'putanja' => 'htdocs',  
        'url'     => 'http://nasaskoola.rs/'
    ],
    #pial
    [
        'db'      => 'nasaskola_pial',
        'dbpass'  => 'da681986',
        'putanja' => 'pial',
        'url'     => 'http://pial.rs/'
    ],
    #pes
    [
        'db'      => 'nasaskola_pes',
        'dbpass'  => '4f91efe6',
        'putanja' => 'pes_org_rs',
        'url'     => 'http://pes.org.rs/'
    ],
];

$conn = new \PDO('mysql:host=localhost;dbname=nasaskola_glavna', 'nasaskola_glavna', 'da90ce7a');
$stm = $conn->query("SELECT * FROM instalacija");
$dbs = $stm->fetchAll(PDO::FETCH_ASSOC);
$dbs = array_merge($special, $dbs);

echo json_encode($dbs);