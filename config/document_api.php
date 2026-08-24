<?php

return [

    'key' => env('DOCUMENT_API_KEY'),

    'user_id' => ($id = env('DOCUMENT_API_USER_ID')) !== null && $id !== ''
        ? (int) $id
        : null,

];
