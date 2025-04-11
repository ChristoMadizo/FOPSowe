<?php

exec("echo Test", $output, $returnCode);
echo implode("\n", $output);

?>