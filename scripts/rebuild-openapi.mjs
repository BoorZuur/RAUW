import { execSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
const root = path.resolve(import.meta.dirname, '..');
const outPath = path.join(root, 'docs', 'openapi.yaml');

function gitShow(rev) {
  return execSync(`git show ${rev}:docs/openapi.yaml`, {
    cwd: root,
    encoding: 'utf8',
    maxBuffer: 20 * 1024 * 1024,
  });
}

function sliceLines(content, start1, end1) {
  return content.split(/\n/).slice(start1 - 1, end1).join('\n');
}

const ce770be = gitShow('ce770be');
const fcca226 = gitShow('fcca226');
const community381 = gitShow('381afae');
const chats7759 = gitShow('7759d01');

const infoBlock = sliceLines(ce770be, 1, 135);
const prefixPaths = sliceLines(fcca226, 130, 4447);
const communityPaths = sliceLines(community381, 4107, 4507);
const chatPaths = sliceLines(chats7759, 4078, 4510);
const referencePaths = sliceLines(community381, 4508, 5237);
const notificationPaths = sliceLines(ce770be, 5960, 6316);
const componentsBlock = sliceLines(ce770be, 6318, 99999);

const resolutionAttachmentDelete = `
  /api/issues/{issueId}/officer-resolution/attachments/{attachmentId}:
    parameters:
      - name: issueId
        in: path
        required: true
        schema:
          type: integer
      - name: attachmentId
        in: path
        required: true
        schema:
          type: integer
    delete:
      tags: [ Issues ]
      summary: Delete officer resolution attachment
      description: >-
        Hard delete one attachment from the issue's officer resolution. Current
        assignee only; non-assignees receive **403** \`not_assigned_officer\`.
        Repeat DELETE returns **404** once the row is gone (route model binding).
        **Tier C** for officers (\`hub_active_required\` without active shared
        shift). District scoping applies (\`officer_not_in_district\`). Issue
        must be viewable; hidden or out-of-scope issues return **404**.
      security:
        - bearerAuth: [ ]
      responses:
        '204':
          description: Attachment deleted; no response body
        '401':
          $ref: '#/components/responses/Unauthorized'
        '403':
          description: Forbidden (assignee, district, hub-active, or inactive account)
          content:
            application/json:
              schema:
                oneOf:
                  - $ref: '#/components/schemas/HubError'
                  - $ref: '#/components/schemas/OfficerWorkflowError'
                  - $ref: '#/components/schemas/AccountInactiveError'
                  - $ref: '#/components/schemas/Error'
        '404':
          $ref: '#/components/responses/NotFound'
`;

const tagsBlock = `
tags:
  - name: Auth
    description: Registration, login, profile, districts, and officer shift start.
  - name: Managers
    description: Main manager and ordinary manager administration.
  - name: Officers
    description: Officer directory, sessions, districts, departments, and self-service reads.
  - name: Issues
    description: Issue lifecycle, assignments, status, attachments, and visibility.
  - name: Issue Comments
    description: Public issue comment threads.
  - name: Issue Feedback
    description: User satisfaction feedback on closed issues.
  - name: Issue Chat
    description: 1:1 officer–user chats and messages on canonical issues.
  - name: Community Posts
    description: District community news feed, attachments, and user saves.
  - name: Notifications
    description: Poll-friendly domain notifications for users and officers (Tier B officer routes).
  - name: Categories
    description: Issue category hierarchy and department assignments.
  - name: Departments
    description: Department reference and main-manager CRUD.
  - name: Hubs
    description: BOA hub clusters and geo login radius.
  - name: Districts
    description: District reference and main-manager CRUD.
  - name: Attachments
    description: File upload and download endpoints grouped with parent resources.
`;

const assembled = [
  infoBlock.trimEnd(),
  'servers:',
  '  - url: /',
  '    description: Backend host root; API routes are prefixed with /api.',
  tagsBlock.trimEnd(),
  prefixPaths.trimEnd(),
  resolutionAttachmentDelete.trimEnd(),
  communityPaths.trimEnd(),
  chatPaths.trimEnd(),
  notificationPaths.trimEnd(),
  referencePaths.trimEnd(),
  componentsBlock.trimEnd(),
  '',
].join('\n');

fs.writeFileSync(outPath, assembled);

const pathCount = [...assembled.matchAll(/^  \/api\/[^:]+:/gm)].length;
console.log(`Wrote ${outPath} (${pathCount} paths, ${assembled.split(/\n/).length} lines)`);
