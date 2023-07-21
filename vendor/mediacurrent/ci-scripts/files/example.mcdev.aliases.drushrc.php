<?php

/**
 * @file
 * Vagrant local development vm.
 */

$aliases['example.mcdev'] = [
  'uri' => 'example.mcdev',
  'root' => '/home/vagrant/docroot/web',
  'path-aliases' => [
    '%drush-script' => '/home/vagrant/docroot/bin/drush',
    '%dump-dir' => '/tmp',
  ],
];

if ('vagrant' !== getenv('USER')) {
  $aliases['example.mcdev']['remote-host'] = 'example.mcdev';
  $aliases['example.mcdev']['remote-user'] = 'vagrant';
  $aliases['example.mcdev']['ssh-options'] = '-o PasswordAuthentication=no -i ${HOME}/.vagrant.d/insecure_private_key';
}
