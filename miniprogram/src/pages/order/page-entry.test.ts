import { existsSync, readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import { dirname, resolve } from 'node:path';
import ts from 'typescript';
import { expect, it } from 'vitest';

const sourceRoot = resolve(process.cwd(), 'src');
const require = createRequire(resolve(sourceRoot, 'app.config.js'));
const appConfig = require('./app.config.js');

function resolveSource(path: string): string | undefined {
  return ['', '.tsx', '.ts', '.jsx', '.js', '/index.tsx', '/index.ts', '/index.js']
    .map((suffix) => path + suffix)
    .find((candidate) => /\.[jt]sx?$/.test(candidate) && existsSync(candidate));
}

it('订单路由不导入其他已注册页面，避免微信小程序重复 Page 注册导致启动白屏', () => {
  const entries = new Set<string>(
    appConfig.pages.map((page: string) => resolveSource(resolve(sourceRoot, page))).filter(Boolean),
  );
  const conflicts: string[] = [];

  for (const entry of entries) {
    if (!entry.includes('/pages/order/')) continue;
    const visited = new Set<string>();
    const visit = (file: string) => {
      if (visited.has(file)) return;
      visited.add(file);
      const source = ts.createSourceFile(file, readFileSync(file, 'utf8'), ts.ScriptTarget.Latest, true);
      for (const statement of source.statements) {
        if (!ts.isImportDeclaration(statement) && !ts.isExportDeclaration(statement)) continue;
        const specifier = statement.moduleSpecifier;
        if (!specifier || !ts.isStringLiteral(specifier) || !specifier.text.startsWith('.')) continue;
        const dependency = resolveSource(resolve(dirname(file), specifier.text));
        if (!dependency) continue;
        if (entries.has(dependency)) {
          conflicts.push(`${entry} -> ${file} -> ${dependency}`);
          continue;
        }
        visit(dependency);
      }
    };
    visit(entry);
  }

  expect(conflicts).toEqual([]);
});
