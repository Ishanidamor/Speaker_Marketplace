<?php
// Compatibility redirect: some links include a duplicate `admin` segment.
// Redirect to the actual admin login page.
header('Location: ../login.php');
exit;
