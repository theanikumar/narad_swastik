<?php

return [
    'secret'     => $_ENV['JWT_SECRET'] ?? 'change-this-secret-in-production',
    'algorithm'  => 'HS256',
    'expiry'     => 86400, // 24 hours
    'issuer'     => 'narad-swastik',
];
