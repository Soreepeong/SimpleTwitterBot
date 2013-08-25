<?php
unset($_SESSION['users'][$cur_user]);
header("HTTP/1.1 302 Moved");
header("Status: 302 Moved");
header("Location: /");
die();