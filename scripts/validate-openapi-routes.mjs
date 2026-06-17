/**
 * Compare Laravel api routes with docs/openapi.yaml path keys.
 */
import fs from 'node:fs';
import path from 'node:path';
import { execSync } from 'node:child_process';

const root = path.resolve(import.meta.dirname, '..');
const openapiPath = path.join(root, 'docs', 'openapi.yaml');
const backendDir = path.join(root, 'apps', 'backend');

function normalizeLaravelUri(uri) {
  return (
    '/api/' +
    uri
      .replace(/^api\//, '')
      .replace(/\{(\w+)\}/g, (_, name) => `{${laravelParamToOpenApi(name)}}`)
  );
}

function laravelParamToOpenApi(name) {
  const map = {
    community_post: 'communityPostId',
    officer_update: 'officerUpdateId',
    notification: 'notificationId',
    category: 'categoryId',
    department: 'departmentId',
    district: 'districtId',
    hub: 'hubId',
    issue: 'issueId',
    attachment: 'attachmentId',
    chat: 'chatId',
    message: 'messageId',
    comment: 'commentId',
    feedback: 'feedbackId',
    officer: 'officerId',
    manager: 'managerId',
  };
  return map[name] ?? name;
}

function expandMethods(method) {
  if (method.includes('|')) {
    return method.split('|').map((m) => m.trim().toUpperCase());
  }
  return [method.toUpperCase()];
}

function parseOpenApi(content) {
  const paths = new Map();
  const pathRe = /^  (\/api\/[^:]+):$/;
  let currentPath = null;

  for (const line of content.split(/\n/)) {
    const pathMatch = line.match(pathRe);
    if (pathMatch) {
      currentPath = pathMatch[1];
      paths.set(currentPath, new Set());
      continue;
    }
    if (!currentPath) continue;
    const opMatch = line.match(/^    (get|post|put|patch|delete):$/i);
    if (opMatch) {
      paths.get(currentPath).add(opMatch[1].toUpperCase());
    }
  }
  return paths;
}

const routesJson = execSync('php artisan route:list --path=api --json', {
  cwd: backendDir,
  encoding: 'utf8',
  maxBuffer: 10 * 1024 * 1024,
});

const routes = JSON.parse(routesJson.trim().split(/\n/).pop());
const openapi = fs.readFileSync(openapiPath, 'utf8');
const openApiPaths = parseOpenApi(openapi);

const backendOps = new Map();
for (const route of routes) {
  const pathKey = normalizeLaravelUri(route.uri);
  for (const method of expandMethods(route.method)) {
    if (method === 'HEAD') continue;
    if (!backendOps.has(pathKey)) backendOps.set(pathKey, new Set());
    backendOps.get(pathKey).add(method);
  }
}

const missingInOpenApi = [];
for (const [pathKey, methods] of backendOps) {
  const specMethods = openApiPaths.get(pathKey);
  if (!specMethods) {
    missingInOpenApi.push({ path: pathKey, methods: [...methods].sort() });
    continue;
  }
  for (const method of methods) {
    if (!specMethods.has(method)) {
      missingInOpenApi.push({ path: pathKey, methods: [method], partial: true });
    }
  }
}

const staleInOpenApi = [];
for (const [pathKey, methods] of openApiPaths) {
  const backendMethods = backendOps.get(pathKey);
  if (!backendMethods) {
    staleInOpenApi.push({ path: pathKey, methods: [...methods].sort() });
  }
}

console.log(`Backend paths: ${backendOps.size}`);
console.log(`OpenAPI paths: ${openApiPaths.size}`);

if (missingInOpenApi.length === 0 && staleInOpenApi.length === 0) {
  console.log('OK: OpenAPI paths align with Laravel routes.');
  process.exit(0);
}

if (missingInOpenApi.length > 0) {
  console.error('\nMissing or incomplete in OpenAPI:');
  for (const item of missingInOpenApi) {
    console.error(`  ${item.methods.join(',')} ${item.path}${item.partial ? ' (method missing)' : ''}`);
  }
}

if (staleInOpenApi.length > 0) {
  console.error('\nStale in OpenAPI (no backend route):');
  for (const item of staleInOpenApi) {
    console.error(`  ${item.methods.join(',')} ${item.path}`);
  }
}

process.exit(1);
