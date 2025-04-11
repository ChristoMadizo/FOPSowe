<?php

$host = '192.168.101.203';
$user = 'kmadzia';
$password = 'Zima2024';


function ssh_connect_vm($host, $user, $password) {
    $connection = ssh2_connect($host, 22);
    if (ssh2_auth_password($connection, $user, $password)) {
        return $connection;
    }
    return false;
}

function run_ssh_command($connection, $command) {
    $stream = ssh2_exec($connection, $command);
    stream_set_blocking($stream, true);
    return stream_get_contents($stream);
}

function close_ssh_connection($connection) {
    ssh2_disconnect($connection);
}

$connection = ssh_connect_vm($host, $user, $password);
















?>


