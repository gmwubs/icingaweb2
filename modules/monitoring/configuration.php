<?php

// TODO: We need to define a useful permission set for this module, the
//       list provided here is just an example
$this->providePermission('commands/all', 'Allow to send all commands');
$this->providePermission('commands/safe', 'Allow to to send a subset of "safe" commands');
$this->providePermission('log', 'Allow full log access');

