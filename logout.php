<?php
ob_start();
session_start();
session_unset();
session_destroy();
ob_end_clean();
header('Location: index.php');
exit;