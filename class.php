<?php

class PokemonException extends Exception {}
class PokemonNotFoundException extends PokemonException {}
class PokemonCombatEndException extends PokemonException {}

class Pokemon {

    public $id;
    public $espece;
    public $nom;
    public $image;
    public $niveau = 1;
    public $type;
    public $vie;
    public $vie_max;
    public $attaque;
    public $defense;
    public $experience = 0;
    public $experience_max = 300;
    public $affinites = [
        'feu' => ['feu' => 1, 'eau' => .5, 'plante' => 2, 'electrik' => 1],
        'eau' => ['feu' => 2, 'eau' => 1, 'plante' => .5, 'electrik' => .5],
        'plante' => ['feu' => .5, 'eau' => 2, 'plante' => 1, 'electrik' => 2],
        'electrik' => ['feu' => 1, 'eau' => 2, 'plante' => .5, 'electrik' => 1]
       ];

    public function __construct($id) {
        if(is_numeric($id)) {
            $pokemon = $this->getById($id);
        } elseif(is_string($id)) {
            $pokemon = $this->getByName($id);
        } else {
            throw new InvalidArgumentException('L’id doit être un nom ou un nombre');
        }
        if(!$pokemon) {
            throw new PokemonNotFoundException($id);
        }

        foreach($pokemon as $cle => $valeur) {
            $this->$cle = $valeur;
        }
    }

    private function getById($id) {
        global $db;
        $pokemon = $db->query('SELECT p.id,
                                      p.nom,
                                      p.niveau,
                                      p.vie,
                                      p.vie_max,
                                      p.xp,
                                      p.xp_max,
                                      p.attaque,
                                      p.defense,
                                      e.nom AS espece,
                                      e.type,
                                      e.image
                               FROM pokemon p
                               JOIN espece e
                                   ON p.espece = e.id
                               WHERE p.id = '.$db->quote($id));
        return $pokemon->fetch();
    }

    private function getByName($name) {
        global $db;
        $pokemon = $db->query('SELECT p.id,
                                      p.nom,
                                      p.niveau,
                                      p.vie,
                                      p.vie_max,
                                      p.xp,
                                      p.xp_max,
                                      p.attaque,
                                      p.defense,
                                      e.nom AS espece,
                                      e.type,
                                      e.image
                               FROM pokemon p
                               JOIN espece e
                                   ON p.espece = e.id
                               WHERE p.nom = '.$db->quote($name));
        return $pokemon->fetch();
    }

    public function attaque($cible) {
        if($this->vie <= 0) {
            throw new PokemonCombatEndException($this->nom.' est KO.');
        }
        if($cible->vie <= 0) {
            throw new PokemonCombatEndException($cible->nom.' est KO.');
        }
        $combat = $this->nom.' attaque '.$cible->nom.' !<br />'.PHP_EOL;
        // on calcule les dégâts de $this sur $cible
        $coef = $this->affinites[$this->type][$cible->type];
        if($coef > 1) $combat .= 'C’est super efficace !<br />'.PHP_EOL;
        elseif($coef < 1) $combat .= 'Ce n’est pas très efficace…<br />'.PHP_EOL;
        $degats = round(($this->attaque * $coef) - $cible->defense);
        if($degats < 0) {
            $degats = 0;
            $combat .= 'Mais ça n’affecte pas le '.$cible->nom.' ennemi.<br />'.PHP_EOL;
        } else {
            $combat .= $this->nom.' inflige '.$degats.' points de dégâts à '.$cible->nom.'<br />'.PHP_EOL;
        }
        //$cible->vie -= $degats;
        $cible->baisser_vie($degats);
        if($cible->vie <= 0) {
            $combat .= $cible->nom.' est KO !<br />'.PHP_EOL;
            // $this gagne de l’expérience
            $combat .= $this->gagne_experience($cible);
        }
        return $combat;
    }

    private function gagne_experience($cible) {
        global $db;
        $points_gagnes = round(100 * ($cible->niveau / $this->niveau));
        $combat = $this->nom.' gagne '.$points_gagnes.' points d’expérience<br />'.PHP_EOL;
        $this->experience += $points_gagnes;
        if($this->experience >= $this->experience_max) {
            // on gagne un niveau \o/
            $this->niveau++;
            $this->vie++;
            $this->vie_max++;
            $this->attaque++;
            $this->defense++;
            $this->experience = $this->experience_max - $this->experience;
            $this->experience_max = 100 * 1.5 * ($this->niveau + 1);
            $combat .= $this->nom.' gagne un niveau ! '.$this->nom.' est maintenant au niveau '.$this->niveau.'<br />'.PHP_EOL;
        }
        $query = $db->prepare('UPDATE pokemon SET xp = :xp,
                                                  xp_max = :xp_max,
                                                  niveau = :niveau,
                                                  vie = :vie,
                                                  vie_max = :vie_max,
                                                  attaque = :attaque,
                                                  defense = :defense
                               WHERE id = :id');
        $result = $query->execute([':xp' => $this->experience,
                                   ':xp_max' => $this->experience_max,
                                   ':niveau' => $this->niveau,
                                   ':vie' => $this->vie,
                                   ':vie_max' => $this->vie_max,
                                   ':attaque' => $this->attaque,
                                   ':defense' => $this->defense,
                                   ':id' => $this->id]);
        if(!$result) {
            $err = $query->errorInfo();
            throw new PokemonException('Erreur lors de la modification : '.$err[2]);
        }

        return $combat;
    }

    public function en_combat() {
        return (isset($_SESSION['combat']['pkmn1']) &&
                    $_SESSION['combat']['pkmn1']->nom == $this->nom)
               ||
               (isset($_SESSION['combat']['pkmn2']) &&
                    $_SESSION['combat']['pkmn2']->nom == $this->nom);
    }

    public function baisser_vie($degats) {
        global $db;
        if($degats > 0) {
            $this->vie -= $degats;
            if($this->vie < 0) $this->vie = 0;
            $result = $db->exec('UPDATE pokemon SET vie = '.$db->quote($this->vie).' WHERE id = '.$db->quote($this->id));
            if(!$result) {
                $err = $db->errorInfo();
                throw new PokemonException('Erreur lors de la mise à jour : '.$err[2]);
            }
        }
    }

    static function get_all_pokemons() {
        global $db;
        $requete = $db->query('SELECT id FROM pokemon ORDER BY id ASC');
        $pokemons = [];
        foreach($requete as $pkmn) {
            $pokemons[] = new Pokemon($pkmn['id']);
        }
        return $pokemons;
    }

    static function create_random_pokemon() {
        global $db;
        $espece = $db->query('SELECT id, nom, type, vie, attaque, defense, image
                              FROM espece
                              ORDER BY RAND()
                              LIMIT 1')->fetch();
        $pokemon = [':nom' => $espece['nom'].' - '.date('Y-m-d H:i:s'),
                    ':espece' => $espece['id'],
                    ':niveau' => 1,
                    ':vie' => $espece['vie'],
                    ':vie_max' => $espece['vie'],
                    ':xp' => 0,
                    ':xp_max' => 300,
                    ':attaque' => $espece['attaque'],
                    ':defense' => $espece['defense']
                   ];
        $query = $db->prepare('INSERT INTO pokemon (nom, espece, niveau, vie, vie_max, xp, xp_max, attaque, defense)
                               VALUES (:nom, :espece, :niveau, :vie, :vie_max, :xp, :xp_max, :attaque, :defense)');
        $result = $query->execute($pokemon);
        if($result) {
            return new Pokemon($db->lastInsertId());
        } else {
            $err = $query->errorInfo();
            throw new PokemonException('Erreur dans la création : '.$err[2]);
        }
    }

    static function get_especes() {
        global $db;
        $especes = $db->query('SELECT id, nom FROM espece ORDER BY nom ASC');
        return $especes;
    }

    static function creer($nom, $espece) {
        global $db;
        $nom = htmlspecialchars($nom);

        $espece_data = $db->query('SELECT id, nom, type, vie, attaque, defense, image
                                   FROM espece
                                   WHERE id = '.$db->quote($espece).'
                                   LIMIT 1')->fetch();
        if(!$espece_data) throw new PokemonNotFoundException('Espèce introuvable');

        $pokemon = [':nom' => $nom,
                    ':espece' => $espece,
                    ':niveau' => 1,
                    ':vie' => $espece_data['vie'],
                    ':vie_max' => $espece_data['vie'],
                    ':xp' => 0,
                    ':xp_max' => 300,
                    ':attaque' => $espece_data['attaque'],
                    ':defense' => $espece_data['defense']
                   ];
        $query = $db->prepare('INSERT INTO pokemon (nom, espece, niveau, vie, vie_max, xp, xp_max, attaque, defense)
                               VALUES (:nom, :espece, :niveau, :vie, :vie_max, :xp, :xp_max, :attaque, :defense)');
        $result = $query->execute($pokemon);
        if($result) {
            return new Pokemon($db->lastInsertId());
        } else {
            $err = $query->errorInfo();
            throw new PokemonException('Erreur dans la création : '.$err[2]);
        }
    }
}
