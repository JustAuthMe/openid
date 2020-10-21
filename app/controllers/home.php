<?php
use PitouFW\Core\Controller;
use PitouFW\Core\Data;

Data::get()->add('openid_configuration_uri', APP_URL . '.well-known/openid-configuration');
Controller::renderApiSuccess();
