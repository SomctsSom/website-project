<?php
declare(strict_types=1);

wa_require_login();
wa_logout();
wa_redirect('/login');
