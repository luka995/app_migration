<?php

const ADMIN = 'root';
const ADMIN_PASS = '';

const WF_HOME = '/home/nasaskola';
const WF_USER = 'nasaskola';
const WF_PASS = 'nasaskola2016';


function WFlistaSajtova()
{
    $sourceDir = __DIR__;
    $destDir = WF_HOME;
    $cmd = <<<CMD
            scp $sourceDir/generator_liste_sajtova.php nasaskola:$destDir;
            ssh nasaskola "/usr/local/bin/php" $destDir/generator_liste_sajtova.php
CMD;
    $json = shell_exec($cmd);
    return json_decode($json, true);
}

function getDumpFromWF($username, $password, $db)
{
    echo "Dump na webfaction-u $db";
    $pass = escapeshellcmd($password);
    $WFDumpDir = WF_HOME . "/dbdump";
    
    $destDir = __DIR__ ."/dbdump";
    
    $cmd = <<<CMD
            ssh nasaskola mkdir -p $WFDumpDir;
            ssh nasaskola mysqldump --user=$username --password=$pass --result-file=$WFDumpDir/$db.dump $db;
            ssh nasaskola tar czf $WFDumpDir/$db.tar.gz -C $WFDumpDir/ $db.dump;
            scp nasaskola:$WFDumpDir/$db.tar.gz $destDir/$db.tar.gz;
            tar xzf $destDir/$db.tar.gz -C $destDir;
            ssh nasaskola rm $WFDumpDir/$db.tar.gz;
CMD;
    shell_exec($cmd);
}

/**
$sajt [
    'db' =>,
    'dbpass' =>,
    'putanja' =>,
    
]
    
*/
function restoreDb(array $sajt) 
{
    echo "restauracija baze {$sajt['db']} \n";
    $output = [];
    $admin = ADMIN;
    //$adminPass = escapeshellcmd(ADMIN_PASS);
    $db = $sajt['db'];
    $user = $sajt['db'];
    $password = escapeshellcmd($sajt['dbpass']); 
    
    $dbDir = __DIR__ ."/dbdump";
            
    createDB($db, $user, $password);
    $komandaRestore = "mysql -h localhost -u $admin  $db < $dbDir/$db.dump";
    $res = exec($komandaRestore, $output);
    if ($res === false) {
        throw new \Exception("neuspesan restore - $db");
    }
}


function createDB($db, $user, $password)
{
    echo "kreiranje baze i usera $db \n";
    $output = [];
    $admin = ADMIN;
    $adminPass = escapeshellcmd(ADMIN_PASS);
    
    //$dropUser = "DROP USER '$user'@'localhost'; FLUSH PRIVILEGES;";
    $createUser = "CREATE USER '$user'@'localhost' IDENTIFIED BY '$password'; FLUSH PRIVILEGES;";    
    $createDatabase = "CREATE DATABASE $db CHARACTER SET utf8 COLLATE utf8_unicode_ci;  FLUSH PRIVILEGES;";
    $grant = "GRANT ALL PRIVILEGES ON $db.* TO '$user'@'localhost'; FLUSH PRIVILEGES;";

    $komanda = "mysql --user=\"$admin\" --password=\"$adminPass\" --database=\"mysql\" --execute=\"$createUser; $createDatabase; $grant\"";
    $res = exec($komanda, $output);
    if ($res === false) {
        throw new \Exception("neuspesno kreiranje baze i usera - $db");
    }

}


function kopirajSaServera(array $sajt)
{
    echo "kopiram sa servera folder {$sajt['putanja']} \n";
    $WFSitesDir = WF_HOME;    
    $WFSitesDirTar = $WFSitesDir . '/sajtoviTar';
    $destDir = "/var/www";
    
    $folder = $sajt['putanja'];
    //malo prostora je ostalo na serveru * moglo bi da se taruje pa da se odmah nakon kopiranja obrise tar sa servera da bi bilo prostora za tarovanje sledeceg sajta
    $cmd = <<<CMD
            ssh nasaskola mkdir -p $WFSitesDirTar;
            ssh nasaskola tar cpzf $WFSitesDirTar/$folder.tar.gz -C $WFSitesDir/webapps/ $folder;
            scp nasaskola:$WFSitesDirTar/$folder.tar.gz $destDir/$folder.tar.gz;
            tar xzf $destDir/$folder.tar.gz -C $destDir;            
            ssh nasaskola rm $WFSitesDirTar/$folder.tar.gz;            
CMD;
    shell_exec($cmd);
}

function render($file, $data)
{
    extract($data);
    ob_start();
    include __DIR__ . $file;
    return ob_get_clean();
}

function configNginx($sajt)
{
    echo "konfiguracija nginx {$sajt['putanja']} \n";
    $sajtnaziv = $sajt['putanja'];
    $domenSaWWW = rtrim(substr($sajt['url'], strlen('http://')), '/');
    $domen = preg_replace('/^www\./i', '', $domenSaWWW);
    $data = ['sajtnaziv' => $sajtnaziv, 'sajtdomen' => $domen];
    $sadrzaj = render('/nginx_templejt.php', $data);
    $configFajl = "/etc/nginx/sites-available/$sajtnaziv";
    $handle = fopen($configFajl, 'w');
    if (fwrite($handle, $sadrzaj) === false) {
        throw new \Exception("Greska prilikom upisa u fajl $configFajl");
    }
    fclose($handle);
    if (exec("ln -s $configFajl /etc/nginx/sites-enabled/$sajtnaziv") === false) {
        throw new \Exception("Greska prilikom generisanja simbolickog linka za sajt - $sajtnaziv");
    }
}

function configFpm($sajt)
{
    echo "konfiguracija fpm {$sajt['putanja']} \n";
    $sajtnaziv = $sajt['putanja'];
    $data = ['sajtnaziv' => $sajtnaziv];
    $sadrzaj = render('/fpm_templejt.php', $data);
    $fpmFajl = "/etc/php/5.6/fpm/pool.d/$sajtnaziv.conf";
    $handle = fopen($fpmFajl, 'w');
    if (fwrite($handle, $sadrzaj) === false) {
        throw new \Exception("Greska prilikom upisa u fajl $fpmFajl");
    }    
    fclose($handle);
}

//useradd -r nalog
//proveriti prava pristupa nakon svega
function siteUsers($sajt)
{
    $destDir = "/var/www";
    $sajtnaziv = $sajt['putanja'];
    echo "useradd $sajtnaziv \n";
    
    $cmd = <<<CMD
        useradd -r $sajtnaziv;          
        chown -R $sajtnaziv:www-data $destDir/$sajtnaziv;            

CMD;
   shell_exec($cmd);
}

function restartNginx()
{
    $komanda = "service nginx restart";
    echo "$komanda \n";
    $res = exec($komanda);
    if ($res === false) {
        throw new \Exception("neuspesno restartovanje nginx-a");
    }
}

function restartFpm()
{
    $komanda = "service php5.6-fpm restart";
    echo "$komanda \n";
    $res = exec($komanda);
    if ($res === false) {
        throw new \Exception("neuspesno restartovanje php5.6-fpm");
    }
}

//MAIN
//proveriti prava pristupa nakon svega
function main()
{
    foreach (WFlistaSajtova() as $sajt) {
        echo "RB. 1 - Restauriram {$sajt['putanja']}\n";
        getDumpFromWF($sajt['db'], $sajt['dbpass'], $sajt['db']);
        restoreDb($sajt);
        kopirajSaServera($sajt);
        siteUsers($sajt);
        configNginx($sajt);
        configFpm($sajt);          
    }
    restartNginx();
    restartFpm();
}

main();

//print_r(WFlistaSajtova());