<?php
require_once __DIR__ . '/../config/database.php';

session_destroy();
jsonResponse(['success' => true]);
