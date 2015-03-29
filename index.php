<?php

require_once('init.php');

define('APPLICATION_PATH', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

/**
 * @route /
 * @view /views/index.html
 */
function index() {
    $pokemons = Pokemon::get_all_pokemons();
    return ['pokemons' => $pokemons, 'combat' => $_SESSION['combat'], 'messages' => get_messages()];
}

/**
 * @route /ajout-pokemon/:nom
 * @view /views/index.html
 */
function ajout_pokemon($nom) {
    global $response;
    try {
        $pokemon = new Pokemon($nom);
        if(!isset($_SESSION['combat']['pkmn1'])) {
            if($pokemon->en_combat()) {
                // ce pokémon est déjà en combat
                add_message($pokemon->nom.' est déjà en combat !');
            } else {
                $_SESSION['combat']['pkmn1'] = $pokemon;
            }
        } elseif(!isset($_SESSION['combat']['pkmn2'])) {
            if($pokemon->en_combat()) {
                // ce pokémon est déjà en combat
                add_message($pokemon->nom.' est déjà en combat !');
            } else {
                $_SESSION['combat']['pkmn2'] = $pokemon;
            }
        } else {
            // on a déjà 2 pokémons sur l’arène
            add_message('Il y a déjà deux Pokémon sur l’arène !');
        }
    } catch(PokemonNotFoundException $e) {
        add_message('Ce Pokémon n’existe pas !');
    }

    return $response->redirect('index');
}

/**
 * @route /sortir-pokemon/:nom
 * @view /views/index.html
 */
function sortir_pokemon($nom) {
    global $response;
    try {
        $pokemon = new Pokemon($nom);
        if($pokemon->en_combat()) {
            if(isset($_SESSION['combat']['pkmn1']) && $_SESSION['combat']['pkmn1']->nom == $pokemon->nom) {
                unset($_SESSION['combat']['pkmn1']);
            } else {
                unset($_SESSION['combat']['pkmn2']);
            }
        } else {
            // le pokémon demandé n’est pas en combat
            add_message($pokemon->nom.' n’est pas en combat !');
        }
    } catch(PokemonNotFoundException $e) {
        add_message('Ce Pokémon n’existe pas !');
    }

    return $response->redirect('index');
}

/**
 * @route /combat
 * @view /views/index.html
 */
function combat() {
    global $response;
    if(isset($_SESSION['combat']['pkmn1'], $_SESSION['combat']['pkmn2'])) {
        $combat = '';
        try {
        $combat .= $_SESSION['combat']['pkmn1']->attaque($_SESSION['combat']['pkmn2']);
        $combat .= $_SESSION['combat']['pkmn2']->attaque($_SESSION['combat']['pkmn1']);
        } catch(PokemonException $e) {
            $combat .= $e->getMessage();
        }
        $_SESSION['combat']['journal'] = $combat;
    } else {
        // on ne peut pas combattre : il n’y a pas 2 pokémons dans l’arène
        add_message('L’arène est incomplète !');
    }
    return $response->redirect('index');
}

/**
 * @route /creer-un-pokemon
 * @view /views/creer.html
 */
function creer() {
    global $request, $response;
    $especes = Pokemon::get_especes();
    $data = $request->getParams();
    $erreur = null;
    if(isset($data['nom'], $data['espece'])) {
        try {
            $retour = Pokemon::creer($data['nom'], $data['espece']);
            add_message('Le pokémon '.$retour->nom.' a été créé.');
            return $response->redirect('index');
        } catch (PokemonException $e) {
            $erreur = 'L’insertion a échoué : '.$e->getMessage();
        }
    }
    return ['data' => $data, 'especes' => $especes, 'erreur' => $erreur];
}

/**
 * @route /reinit
 * @view /views/index.html
 */
function reinit() {
    global $response;
    session_destroy();
    return $response->redirect('index');
}

require_once('bottle.phar');
