#!/bin/bash

# Combine all source files
HEADER="/*!
 * WPMU DEV Shared UI
 * Copyright 2018 Incsub (https://incsub.com)
 * Licensed under GPL v2 (http://www.gnu.org/licenses/gpl-2.0.html)
 */
"

echo "$HEADER" > assets/sui/js/shared-ui.temp.js

# Add all source files in the correct order
for file in \
  assets/sui/js/_src/a11y-dialog.js \
  assets/sui/js/_src/accordion.js \
  assets/sui/js/_src/clipboard.js \
  assets/sui/js/_src/code-snippet.js \
  assets/sui/js/_src/dropdowns.js \
  assets/sui/js/_src/modals.js \
  assets/sui/js/_src/notifications.js \
  assets/sui/js/_src/password.js \
  assets/sui/js/_src/scores.js \
  assets/sui/js/_src/select.js \
  assets/sui/js/_src/select2.js \
  assets/sui/js/_src/tabs.js \
  assets/sui/js/_src/upload.js
do
  if [ -f "$file" ]; then
    cat "$file" >> assets/sui/js/shared-ui.temp.js
    echo "" >> assets/sui/js/shared-ui.temp.js
  fi
done

# Minify with terser
terser assets/sui/js/shared-ui.temp.js -c -m -o assets/sui/js/shared-ui.min.js

# Cleanup
rm assets/sui/js/shared-ui.temp.js

echo "Build complete! Minified: assets/sui/js/shared-ui.min.js"
