<?php
require_once __DIR__ . '/auth/helper.php';

http_response_code(410);
json_error('Endpoint legacy sudah dinonaktifkan. Gunakan API di folder api/.', 410);
