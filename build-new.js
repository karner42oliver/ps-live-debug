#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

// HEADER
const HEADER = `/*!
 * WPMU DEV Shared UI
 * Copyright 2018 Incsub (https://incsub.com)
 * Licensed under GPL v2 (http://www.gnu.org/licenses/gpl-2.0.html)
 */
`;

const srcDir = 'assets/sui/js/_src';
const distDir = 'assets/sui/js';

const files = [
  'a11y-dialog.js',
  'accordion.js',
  'clipboard.js',
  'code-snippet.js',
  'dropdowns.js',
  'modals.js',
  'notifications.js',
  'password.js',
  'scores.js',
  'select.js',
  'select2.js',
  'tabs.js',
  'upload.js'
];

// Build non-minified version
let content = HEADER + '\n';
files.forEach(file => {
  const srcPath = path.join(srcDir, file);
  if (fs.existsSync(srcPath)) {
    const fileContent = fs.readFileSync(srcPath, 'utf8');
    content += fileContent + '\n\n';
    console.log(`Added: ${file}`);
  }
});

// Write non-minified version
const outputPath = path.join(distDir, 'shared-ui.js');
fs.writeFileSync(outputPath, content, 'utf8');
console.log(`✓ Created: ${outputPath}`);

// For minified version, try to use terser if available, otherwise just use regex-based simple minify
const minOutPath = path.join(distDir, 'shared-ui.min.js');

try {
  const terser = require('terser');
  const result = terser.minify(content, {
    output: {
      comments: false
    }
  });
  if (!result.error) {
    fs.writeFileSync(minOutPath, HEADER + '\n' + result.code, 'utf8');
    console.log(`✓ Created: ${minOutPath} (with terser)`);
  } else {
    throw result.error;
  }
} catch (e) {
  // Fallback: simple minification
  let minified = content
    .replace(/\/\*[\s\S]*?\*\//g, '') // Remove block comments
    .replace(/\/\/.*/g, '')            // Remove line comments
    .replace(/\n\s+/g, '\n')           // Remove indentation
    .replace(/\n+/g, '')               // Remove newlines
    .replace(/\s+/g, ' ')              // Collapse whitespace
    .replace(/\s*([{}();,:])\s*/g, '$1'); // Remove spaces around syntax
  
  fs.writeFileSync(minOutPath, HEADER + minified, 'utf8');
  console.log(`✓ Created: ${minOutPath} (fallback minify)`);
}

console.log('\n✓ Build complete!');
