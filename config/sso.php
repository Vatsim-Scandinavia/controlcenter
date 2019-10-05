<?php

return [

    /*
     * DO NOT PUBLISH THE KEY, SECRET AND CERT TO CODE REPOSITORIES
     * FOR SECURITY.
     */

    /*
     * The location of the VATSIM OAuth interface
     */
    'base' => env('SSO_URL', 'http://sso.hardern.net/server/'),

    /*
     * The consumer key for your organisation (provided by VATSIM)
     */
    'key' => env('SSO_KEY', 'SSO_DEMO_VACC'),

    /*
     * The secret key for your organisation (provided by VATSIM)
     * Do not give this to anyone else or display it to your users. It must be kept server-side
     */
    'secret' => env('SSO_SECRET','04i_~ruVUE.1-do1--sc'),

    /*
     * The signing method you are using to encrypt your request signature.
     * Different options must be enabled on your account at VATSIM.
     * Options: RSA / HMAC
     */
    'method' => env('SSO_METHOD', 'RSA'),

    /*
     * Your RSA **PRIVATE** key
     * If you are not using RSA, this value can be anything (or not set)
     */
    'cert' => env('SSO_CERT', "-----BEGIN RSA PRIVATE KEY-----
MIIEpgIBAAKCAQEA2S5RckDw7SnEoZDmjaQHAQGajVlb7iwKIAX6nXbZBO7Uo3pN
ItjmAbfkMqKBgWDVowM3UjbKivZNWGzkmxirArpbw9q7JhcX2LW6RfXx+5zn2+zW
m58nQtnEgZtj8U9z3yjJEwfGbiJHEt56pNY0VFV5sDbEiQ52d/bPHlH17j/SUfm6
eWCbUWW5S8kI8LDuN40qtxCZ0InTfRvcI3bx0+UBf9T6SYQWK2DsS+bz2YtKxVom
Os9NdLbcPDK1rKPCJ+gvvmhCCt7jDbf1oFUzhPb6hjsIl1uRyjdtjhDb5FIokH+O
3LuZdvSGF/SkoBnkfnqg5yTjC0GrnPg+Dr++1QIDAQABAoIBAQDIAisJwJrgnx2x
+WMKQGwe1h5CXHAYOMCeW0NBLsmQDG8RmrldBUlVfcgPha8kukwlEvooocMIFOqI
K8iguSgMnBmUlmTSIGRatIm2kljm8spotIWzze93VlvtTHDPM++vLb135CovFSxF
SVTDZ23L2Of3i4iV/BbIRijacHq/jJ605OBcHhgW0ONCPUxL+uUd7siD68Y/BcYu
km1OfQaxxryKdnE4UWzVKm0fwIzGvS/Baraek3kQCqOs7+OixV2YWFw6Xafq3WAp
Pe5I/pJSevu90dGN01k84fVS6q3q419Z+VxarPYYznLrGGgUxM5zKlU4VHGwvA2p
857ydg3hAoGBAPFuOulYQW8DIas4rlPPGofQI+dT0w8xf/YB1WmCtlt0GkSmEzd+
JJZtcJiQSlTC4BuACvTBoIgo3vUC2wM5gZLz9NCeUHrwW0558q1YnGx1GNKcWgKK
LrYvWPCrOKVnDvfhSQ4P3CPeUyks4OUTiPHY+5QlBpY7c1hSBnJWSNKZAoGBAOZJ
dtle62ZK6S3TlIgbElaa1h8J5QyEFmcCPl47B4+SUNIljccO55OQhe89paMD2EH6
Tbz9eP/s4U7X1tTb2onYtd7g3ldod/RBhrRHg7oXTmQj9wXopJsHwgNnYG59BPt2
xpnB7aTmMZCXTO2YRxR4CCTtnOO/TZeNZV/xIK+dAoGBAJQ2sJHZ7WmiSYQcquCm
jsn7nF8CFdsI715uJ767UQn5z7p/HeL+XKXAj9QJGKjKbdxUEeXKDKwqMx3E4AEt
x38Ypx1/Yzbl4Zfew31pnbXzeQaql5Nhk2Wi0X4GDyNzjjvcoQWx9NpMPU9Uzsey
42pdY6zBwjZuTtRUnsKId/JZAoGBALzXVXyfF85Ec76+mDicaodWZWwCgy+mSXCj
KF3BbkvPojMR1Jd9o20gwJQVK3ToPDiud30ZJlZH++LZoDPhLe6IJWvlXq6y3lsQ
ONQxKNY7Mm9wBqtzwTfYPsLnzO4N2z4Sgn2nx6bHlbGKQO09SFyCqbsOlu8z+v7i
VlU8uJ8JAoGBAOmzlKBcEjJdlD0ZxkgMxp+YqpKkC+ojzf4tORn6jo2d/aKUOIAR
bfRCMTmDmqyVoUH/SYgQWzD36zAy8HyHEz0U1k6+QMzWPbsEGQSQrk0DgnlOBPWo
O0gQ0RDS3gD8C5XHvy5vryYjUOB10rUn9A2xLQw4sqKv2suHvIhc0Eit
-----END RSA PRIVATE KEY-----"),

    /*
     * The URL users will be redirected to after they log in, this should
     * be on the same server as the request
     */
    'return' => env('SSO_RETURN', 'http://localhost:8000/validate'),

    'additionalConfig' => [
        'allow_suspended' => true,
        'allow_inactive' => true,
    ]

];