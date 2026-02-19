<?php
// Backwards-compatible redirect: some templates link to register.php
// Redirect users to the main signup page.
header('Location: signup.php');
exit;
