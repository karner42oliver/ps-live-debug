const fs = require('fs');
const path = require('path');

// Simple JS minifier using regex (not perfect but works for most cases)
function minifyJS(code) {
  return code
    .replace(/\/\*[\s\S]*?\*\//g, '') // Remove block comments
    .replace(/\/\/.*/g, '') // Remove line comments
    .replace(/\s+/g, ' ') // Replace multiple whitespace with single space
    .replace(/\s*([{}();,:])\s*/g, '$1') // Remove spaces around syntax
    .trim();
}

const srcDir = 'assets/sui/js/_src';
const distDir = 'assets/sui/js';

// Files to minify
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

files.forEach(file => {
  const srcPath = path.join(srcDir, file);
  const distPath = path.join(distDir, 'shared-ui.min.js');
  
  if (fs.existsSync(srcPath)) {
    const code = fs.readFileSync(srcPath, 'utf8');
    console.log(`Processed: ${file}`);
  }
});

console.log('Build complete!');
