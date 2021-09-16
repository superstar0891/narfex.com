const fetch = require('node-fetch');
const fs = require('fs');

const branch = process.argv[2];
const schemaPath = './src/services/apiSchema.js';

const domain = branch ? `api-${branch}.narfex.dev` : `api.findiri.com`;
console.log('\x1b[36m Get schema from ' + domain + '\x1b[0m');
fetch(`https://${domain}/api/v1/documentation/schema`)
  .then(res => res.json())
  .then(schema => {
    console.log(`\x1b[32m Success! path: \x1b[37m${schemaPath}\x1b[0m`);
    const content = '// Файл был сгенерирован автоматически командой npm run getSchema\n// eslint-disable-next-line\nexport default ' + JSON.stringify(schema, null, 2);
    fs.writeFileSync(schemaPath, content);
  }).catch(err => {
    console.log(`\x1b[31m Error: ${err.type}\x1b[0m`);
  });
