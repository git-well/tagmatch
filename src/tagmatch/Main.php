<?php

namespace tagmatch;

/**
 * 文本关键词匹配类库。
 *
 * 匹配规则：左边优先；同一起点存在多个关键词时，优先匹配最长关键词。
 */
class Main
{
    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $wordData = [];

    /**
     * 设置标签数据。
     *
     * @param array<int, array<string, mixed>> $words
     * @return $this
     */
    public function setTree(array $words = []): self
    {
        $this->wordData = [];

        foreach ($words as $word) {
            if (!is_array($word) || !isset($word['word'])) {
                continue;
            }

            $tag = (string) $word['word'];
            if ($tag === '') {
                continue;
            }

            $word['word'] = $tag;
            $word['len'] = mb_strlen($tag, 'UTF-8');
            $this->wordData[] = $word;
        }

        // 同一起点发生冲突时，最长关键词优先。
        usort($this->wordData, static function (array $a, array $b): int {
            return $b['len'] <=> $a['len'];
        });

        return $this;
    }

    /**
     * 获取文本中的标签匹配结果。
     *
     * 匹配采用“左边优先 + 最长匹配”，不会修改原始文本来制造匹配状态。
     *
     * @param string $content
     * @param int $wordNum 0 表示返回全部结果。
     * @return array<int, array<string, mixed>>
     */
    public function getTagWord(string $content, int $wordNum = 0): array
    {
        $matches = [];
        $length = mb_strlen($content, 'UTF-8');
        $cursor = 0;

        while ($cursor < $length) {
            $matched = $this->matchAt($content, $cursor);

            if ($matched === null) {
                $cursor++;
                continue;
            }

            $matches[] = $matched;
            $cursor += $matched['len'];

            if ($wordNum > 0 && count($matches) >= $wordNum) {
                break;
            }
        }

        return $matches;
    }

    /**
     * 替换文本中的标签。
     *
     * 默认替换所有不重叠匹配；$replaceOne=true 时，每个关键词只替换第一次。
     *
     * @param string $content
     * @param string $newclass 兼容旧 API 的 HTML 属性字符串。
     * @param bool $replaceOne
     * @return string
     */
    public function replace(string $content, string $newclass = '', bool $replaceOne = false): string
    {
        $length = mb_strlen($content, 'UTF-8');
        $cursor = 0;
        $result = '';
        $replaced = [];

        while ($cursor < $length) {
            $matched = $this->matchAt($content, $cursor);

            if ($matched === null) {
                $result .= mb_substr($content, $cursor, 1, 'UTF-8');
                $cursor++;
                continue;
            }

            $tag = $matched['word'];

            if ($replaceOne && isset($replaced[$tag])) {
                $result .= $tag;
                $cursor += $matched['len'];
                continue;
            }

            $url = htmlspecialchars((string) ($matched['url'] ?? ''), ENT_QUOTES, 'UTF-8');
            $result .= '<a href="' . $url . '" ' . $newclass . '>' . $tag . '</a>';
            $replaced[$tag] = true;
            $cursor += $matched['len'];
        }

        return $result;
    }

    /**
     * 从指定字符位置寻找最长关键词。
     *
     * @param string $content
     * @param int $offset
     * @return array<string, mixed>|null
     */
    protected function matchAt(string $content, int $offset): ?array
    {
        foreach ($this->wordData as $word) {
            $candidate = mb_substr($content, $offset, $word['len'], 'UTF-8');
            if ($candidate === $word['word']) {
                return $word;
            }
        }

        return null;
    }
}
