# tagmatch

> PHP 文本关键词匹配与标签替换库。

## v2 匹配规则

v2 重写了核心匹配算法，采用 **Unicode Trie + 左边优先 + 最长匹配**：

1. 从文本左侧开始逐字符扫描。
2. 同一个位置存在多个关键词时，优先选择最长关键词。
3. 一个关键词匹配成功后，直接跳过已经消费的字符，因此不会产生重叠匹配。
4. 不再通过修改原始文本或插入占位符来阻止重复匹配。
5. 默认会替换关键词的所有非重叠出现位置。

例如关键词同时存在：

```text
北京
北京大学
大学
```

文本：

```text
我在北京大学学习
```

匹配结果只有：

```text
北京大学
```

而不是拆成「北京」+「大学」。

## 使用方法

### 引入

```bash
composer require git-mz/tagmatch
```

### 匹配

```php
use tagmatch\Main;

$matcher = new Main();
$matcher->setTree([
    ['word' => '云朵', 'url' => 'https://www.yunduo.com'],
    ['word' => '七彩云朵', 'url' => 'https://www.qicaiyunduo.com'],
]);

$content = '我会脚踏云朵，哦不，是七彩云朵去娶你！';

$matches = $matcher->getTagWord($content);
```

### 限制匹配数量

```php
$matches = $matcher->getTagWord($content, 3);
```

`wordNum = 0` 表示返回全部匹配结果。

### 替换

```php
$result = $matcher->replace($content);
```

默认替换所有非重叠匹配。

如果只希望每个关键词替换第一次：

```php
$result = $matcher->replace($content, '', true);
```

### 自定义 HTML 属性

为了兼容旧版本 API，第二个参数仍然接受 HTML 属性字符串：

```php
$result = $matcher->replace($content, 'class="tag-link" target="_blank"');
```

## 算法说明

关键词会预先构建成 Unicode Trie。匹配时从文本当前位置沿 Trie 向下查找，并记录最后一个完整关键词节点，因此天然支持最长匹配。

相比旧版本逐个关键词调用 `strpos()`，新版本不会在每个文本位置重新遍历全部关键词；实际匹配成本主要取决于文本长度和当前位置可能形成的关键词前缀长度。

## 测试

```bash
composer install
vendor/bin/phpunit
```

测试覆盖：

- 最长关键词优先
- 多个关键词连续匹配
- `wordNum` 限制
- 默认替换全部出现位置
- `replaceOne` 每个关键词只替换一次
- 中文、Emoji 等 Unicode 文本

## 兼容性

`setTree()`、`getTagWord()`、`replace()` 保留，方便现有代码迁移到新版。
