## 字典系统插件

MineAdmin 3.0 字典系统插件，提供字典持久化保存的功能，方便前端和其他业务调用静态数据

## 下载插件

- 后台应用市场下载插件
- 命令安装，在后端根目录下执行命令：

```bash
php bin/hyperf.php mine-extension:download mine-admin/dictionary
```
## 安装插件

- 后台应用商店安装插件
- 命令安装，在后端根目录下执行命令：

```bash
php bin/hyperf.php mine-extension:install mine-admin/dictionary --yes
```

## 使用方法

### 后端
后端提供了一个助手类，可以快捷调用字典数据
```php
// 获取某个分类，如果不传入参数，则获取所有字典分类
\Plugin\MineAdmin\Dictionary\Helper\Helper::getDictionaryType('testType')

// 获取某个分类下的字典数据
\Plugin\MineAdmin\Dictionary\Helper\Helper::getDictionary('testType')
```

### 前端
前端在插件启动时，会请求后端获取所有字典数据，并存入到  `useDictStore()`，然后可以配合以下组件直接使用
- `<ma-dict-select>`
- `<ma-dict-radio>`
- `<ma-dict-checkbox>`

如果需要单独渲染，可以使用 `useDictStore()` 来获取字典数据
```js
const dictStore = useDictStore()
// 获取某个分类的字典数据
const data = dictStore.find('testType')
```
