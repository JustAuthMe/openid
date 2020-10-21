<?php

namespace PitouFW\Entity;

class IdToken {
    private string $iss;
    private string $sub;
    private string $aud;
    private int $exp;
    private int $iat;
    private int $auth_time;
    private string $nonce;
}