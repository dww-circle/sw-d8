<?php

// Clean out everything already in the table.
\Drupal::database()->delete('entity_subqueue__items')->execute();
