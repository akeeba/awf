<?php
/**
 * Fixture template that echoes view properties via $this->get().
 * Used to test forced-param injection and view property access.
 */
echo $this->testVar ?? 'MISSING';
