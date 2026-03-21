const fs = require('fs');
const path = require('path');

const target = path.join(__dirname, '..', 'node_modules', '@tarojs', 'helper', 'dist', 'utils.js');
if (!fs.existsSync(target)) {
  console.log('[patch-taro-helper] target not found, skip');
  process.exit(0);
}

const source = fs.readFileSync(target, 'utf8');
if (source.includes("const configExt = path.extname(configPath).toLowerCase();")) {
  console.log('[patch-taro-helper] already patched');
  process.exit(0);
}

const startMarker = "        else {\n            result = (0, esbuild_1.requireWithEsbuild)(configPath, {";
const endMarker = "        result = (0, exports.getModuleDefaultExport)(result);";
const start = source.indexOf(startMarker);
const end = source.indexOf(endMarker);
if (start === -1 || end === -1 || end <= start) {
  console.error('[patch-taro-helper] expected markers not found');
  process.exit(1);
}

const replacement = `        else {\n            const configExt = path.extname(configPath).toLowerCase();\n            if (configExt === '.js' || configExt === '.cjs') {\n                delete require.cache[require.resolve(configPath)];\n                result = require(configPath);\n            }\n            else {\n                result = (0, esbuild_1.requireWithEsbuild)(configPath, {\n                    customConfig: {\n                        alias: options.alias || {},\n                        define: (0, lodash_1.defaults)({}, options.defineConstants || {}, {\n                            define: 'define', // Note: 该场景下不支持 AMD 导出，这会导致 esbuild 替换 babel 的 define 方法\n                        }),\n                    },\n                    customSwcConfig: {\n                        jsc: {\n                            parser: {\n                                syntax: 'typescript',\n                                decorators: true,\n                            },\n                            transform: {\n                                legacyDecorator: true,\n                            },\n                            experimental: {\n                                plugins: [\n                                    [path.resolve(__dirname, '../swc/swc_plugin_define_config.wasm'), {}]\n                                ]\n                            }\n                        },\n                        module: {\n                            type: 'commonjs',\n                        },\n                    },\n                });\n            }\n        }\n`;

const patched = source.slice(0, start) + replacement + source.slice(end);
fs.writeFileSync(target, patched, 'utf8');
console.log('[patch-taro-helper] patched successfully');
