#!/bin/bash
header="/**\n * @since 1.2.0\n * @version 1.2.0\n */\n"

find app routes resources -type f \( -name "*.php" -o -name "*.js" -o -name "*.sass" -o -name "*.css" \) | while read file; do
  if ! grep -q "@since" "$file"; then
    if [[ "$file" == *.php ]]; then
      # Insert after <?php
      sed -i '' -e '/^<?php/a\'$'\n'"$header" "$file"
    else
      # Insert at top
      sed -i '' '1i\'$'\n'"$header" "$file"
    fi
  fi
done
echo "Injection complete."
