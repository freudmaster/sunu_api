<?php

function database_connect(){
    static $connection;   // Avoids multiple connections
    if(!isset($connection)) {
      $config = parse_ini_file(__DIR__.'/../../config.ini');
      $connection = mysqli_connect(
        $config['host'],
        $config['username'],
        $config['password'],
        $config['db_name']
      );
    }
    // Return error or connection
    if($connection === false) return mysqli_connect_error();
    return $connection;
}
$row = 1;
$id_cli = 11;
$id_plc = 6;
if (($handle = fopen("http://localhost/sunu_api/application/controllers/Assure.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
        $num = count($data);
        echo "<p> $num fields in line $row: <br /></p>\n";
        
        for ($c=0; $c < $num; $c++) {
            $data[$c] = utf8_encode($data[$c]);
            //echo $data[$c] . "<br />\n";
            $infos = explode(';', $data[$c]);
            $connection = database_connect();

            //clients
            $nom = $infos[13];	
            $genre = $infos[12];	
            $num_souscripteur = '32097';	
            $num_assure = $infos[11];	
            $adresse = addslashes($infos[14]);	
            $ville = $infos[15];	
            $date_naiss = date("Y-m-d",strtotime(str_replace('/','-', $infos[16])));	
            $profession = $infos[17];	
            $email = $infos[18];	
            $tel_sousc = '07-31-09-07 / 06-30-17-73';	
            $num_compte = '4000304140110179260 11';	
            $tel_assure= '0'.$infos[20];

            
            $query = "INSERT INTO clients (nom, genre, num_souscripteur, num_assure, adresse, ville, date_naiss, profession, email, tel_sousc, num_compte, tel_assure) VALUES ('".$nom."', '".$genre."', '".$num_souscripteur."', '".$num_assure."', '".$adresse."', '".$ville."', '".$date_naiss."', '".$profession."', '".$email."', '".$tel_sousc."', '".$num_compte."', '".$tel_assure."') ";
            
            if (mysqli_query($connection, $query)) {
                    //police
                    $mode_comptabilisation = $infos[2];
                    $categorie = $infos[3];
                    $classification = $infos[4];
                    $num_police = $infos[5];
                    $autre_num_police = $infos[6];
                    $num_compte = $infos[7];
                    $mode_paiement = $infos[25];
                    $organisme_payeur = $infos[26];	
                    $periodicite = $infos[27];	
                    $prime_totale = $infos[28];	
                    $prime_nette = $infos[29];	
                    $next_prime = $infos[30];	
                    $taxe = $infos[35];	
                    $capital_dc = $infos[39];	
                    $capital_terme = $infos[40];	
                    $etat_police = $infos[41];	
                    $date_etat = date("Y-m-d",strtotime(str_replace('/','-', $infos[42])));	
                    $total_encaissement = $infos[48];	
                    $nom_agent = $infos[85];	
                    $beneficiaire = $infos[75];	
                    $Clients_id = $id_cli;	
                    $produit_id = 1;

                    $query2 = "INSERT INTO police (mode_comptabilisation, categorie, classification, num_police, autre_num_police, num_compte, mode_paiement, organisme_payeur, periodicite, prime_totale, prime_nette, next_prime, taxe, capital_dc, capital_terme, etat_police, date_etat, total_encaissement, nom_agent, beneficiaire, Clients_id, produit_id) 
                    VALUES ('".$mode_comptabilisation."', '".$categorie."', '".$classification."', '".$num_police."', '".$autre_num_police."', '".$num_compte."', '".$mode_paiement."', '".$organisme_payeur."', '".$periodicite."', ".$prime_totale.", ".$prime_nette.", ".$next_prime.", ".$taxe.", ".$capital_dc.", ".$capital_terme.", '".$etat_police."', '".$date_etat."', ".$total_encaissement.", '".$nom_agent."', '".$beneficiaire."', ".$Clients_id.", ".$produit_id.") ";
                    
                    if (mysqli_query($connection, $query2)) {
                        $id_cli++;
                        //info_contrats
                        $date_deb = date("Y-m-d",strtotime(str_replace('/','-', $infos[21])));	
                        $date_fin = date("Y-m-d",strtotime(str_replace('/','-', $infos[22])));	
                        $date_signature = date("Y-m-d",strtotime(str_replace('/','-', $infos[23])));
                        $date_reception = date("Y-m-d",strtotime(str_replace('/','-', $infos[24])));
                        $police_id = $id_plc;

                        $query3 = "INSERT INTO info_contrats (date_deb, date_fin, date_signature, date_reception, police_id ) 
                        VALUES ('".$date_deb."', '".$date_fin."', '".$date_signature."', '".$date_reception."', '".$police_id."') ";
                        if (mysqli_query($connection, $query3)) {
                            $id_plc++;
                            echo "insert Ok ligne ".$row;
                        }else{
                            echo "erreur insertion info_contrats";
                            printf("Errormessage: %s\n", mysqli_error($connection));
                        }
                    }else{
                        echo "erreur insertion police";
                        printf("Errormessage: %s\n", mysqli_error($connection));
                    }
            }else{
                echo "erreur insertion client";
                printf("Errormessage: %s\n", mysqli_error($connection));
            }
        }
        $row++;
    fclose($handle);
}
}
?>