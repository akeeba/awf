<?php
/**
 * Fixture template that throws at render time (the file DOES exist and resolves fine).
 * Used to prove View::loadTemplate() propagates genuine render-time exceptions instead of
 * swallowing them and masking the failure by falling back to the 'default' layout.
 */
throw new \RuntimeException('Boom from throwing layout');
