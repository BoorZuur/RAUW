/**
 * Move components.schemas from docs/openapi.yaml into docs/openapi/schemas.yaml
 * and rewrite $ref pointers in the main spec to the external file.
 */
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..');
const mainPath = path.join(root, 'docs', 'openapi.yaml');
const schemasPath = path.join(root, 'docs', 'openapi', 'schemas.yaml');

const content = fs.readFileSync(mainPath, 'utf8');
const lines = content.split(/\n/);

const schemasLineIdx = lines.findIndex(
  (line, i) => line === '  schemas:' && i > 0 && lines[i - 1].trim() === '',
);

if (schemasLineIdx === -1) {
  console.error('Could not find components.schemas block in openapi.yaml');
  process.exit(1);
}

const schemaBody = lines.slice(schemasLineIdx + 1).join('\n').trimEnd();
const mainBody = lines.slice(0, schemasLineIdx).join('\n').trimEnd() + '\n';

const schemasFile = [
  'openapi: 3.0.3',
  'info:',
  '  title: WIJK App API Schemas',
  '  version: \'1.0.0\'',
  '  description: >-',
  '    Component schemas externalized from docs/openapi.yaml. Referenced via',
    '    `openapi/schemas.yaml#/components/schemas/{Name}` from the main spec.',
  'components:',
  '  schemas:',
  schemaBody,
  '',
].join('\n');

const SCHEMA_REF = /#\/components\/schemas\//g;
const EXTERNAL_REF = "openapi/schemas.yaml#/components/schemas/";

const updatedMain = mainBody.replace(SCHEMA_REF, EXTERNAL_REF);

fs.mkdirSync(path.dirname(schemasPath), { recursive: true });
fs.writeFileSync(schemasPath, schemasFile);
fs.writeFileSync(mainPath, updatedMain);

const mainLines = updatedMain.split(/\n/).length;
const schemaLines = schemasFile.split(/\n/).length;
console.log(`Wrote ${schemasPath} (${schemaLines} lines)`);
console.log(`Updated ${mainPath} (${mainLines} lines, was ${lines.length})`);
