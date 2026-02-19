<?php
// Backwards-compatible redirect: some links may reference admin.php
// Redirect to the admin panel index.
header('Location: admin/index.php');
exit;
