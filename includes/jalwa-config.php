<?php

/* Jalwa API settings — keep credentials in Vercel Environment Variables. */
define('JALWA_BASE_URL', rtrim(getenv('JALWA_BASE_URL') ?: '', '/'));
define('JALWA_USERNAME', getenv('JALWA_USERNAME') ?: '');
define('JALWA_PASSWORD', getenv('JALWA_PASSWORD') ?: '');
define('JALWA_LANGUAGE', (int)(getenv('JALWA_LANGUAGE') ?: 0));
