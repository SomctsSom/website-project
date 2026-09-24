<?php
declare(strict_types=1);

require_method('GET');
json_ok(get_navbar_colors(db()));
