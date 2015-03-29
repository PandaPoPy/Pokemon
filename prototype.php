<!DOCTYPE html>
<html>
	<head>
        <meta charset="utf-8" />
		<title>test</title>
	</head>
	<body>
<?php

require_once('class.php');

$carapuce = new Carapuce();
$salameche = new Salameche();

try {
    for($i=0; $i<3; $i++) {
        echo '<h2>Tour de jeu</h2>'.PHP_EOL;
        echo '<p>'.$carapuce->attaque($salameche).'</p>'.PHP_EOL;
        echo '<h3>Contre-attaque !</h3>'.PHP_EOL;
        echo $salameche->attaque($carapuce);
    }
} catch(PokemonCombatEndException $e) {
    echo '<p>Le combat est fini ! '.$e->getMessage().'</p>';
}
?>
</body>
</html>
