<?php

use PitouFW\Core\Data;

Data::get()->add('issuer', APP_URL);
Data::get()->add('authorization_endpoint', APP_URL . 'authorization');
Data::get()->add('token_endpoint', APP_URL . 'token');
Data::get()->add('jwks_uri', APP_URL . 'jwks');
Data::get()->add('registration_endpoint', APP_URL . 'registration');
Data::get()->add('scopes_supported', ['profile', 'email']);
Data::get()->add('response_types_supported', ['code']);
Data::get()->add('grant_types_supported', ['authorization_code']);
Data::get()->add('subject_types_supported', ['pairwise']);
Data::get()->add('id_token_signing_alg_values_supported', ['RS256']);
Data::get()->add('token_endpoint_auth_methods_supported', ['client_secret_basic', 'client_secret_post']);
Data::get()->add('claims_supported', ['sub', 'name', 'given_name', 'family_name', 'picture', 'email', 'email_verified', 'birthdate', 'locale']);
Data::get()->add('service_documentation', 'https://docs.justauth.me');
Data::get()->add('op_policy_uri', 'https://justauth.me/p/privacy-policy');
Data::get()->add('op_tos_uri', 'https://justauth.me/p/terms-of-service');
