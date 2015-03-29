<?php

function add_message($message, $pile = 'messages') {
    if(!isset($_SESSION['messages'])) $_SESSION['messages'] = [];
    $_SESSION['messages'][] = $message;
}

function get_messages() {
    if(!isset($_SESSION['messages'])) return [];
    $messages = $_SESSION['messages'];
    $_SESSION['messages'] = [];
    return $messages;
}
