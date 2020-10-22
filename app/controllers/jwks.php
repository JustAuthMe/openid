<?php

use PitouFW\Cache\OIDC;
use PitouFW\Core\Data;
use PitouFW\Model\JWKS;

Data::get()->add('keys', JWKS::getPubKeys());
