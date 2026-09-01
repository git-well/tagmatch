<?php

use PHPUnit\Framework\TestCase;
use tagmatch\Main;

final class MainTest extends TestCase
{
    private function matcher(): Main
    {
        return (new Main())->setTree([
            ['word' => '北京', 'url' => 'https://example.com/beijing'],
            ['word' => '北京大学', 'url' => 'https://example.com/pku'],
            ['word' => '大学', 'url' => 'https://example.com/university'],
            ['word' => 'PHP', 'url' => 'https://example.com/php'],
        ]);
    }

    public function testLongestMatchWinsAtSamePosition(): void
    {
        $matches = $this->matcher()->getTagWord('我在北京大学学习');

        $this->assertCount(1, $matches);
        $this->assertSame('北京大学', $matches[0]['word']);
    }

    public function testMatchesContinueAfterAConsumedMatch(): void
    {
        $matches = $this->matcher()->getTagWord('北京大学和PHP');

        $this->assertSame(['北京大学', 'PHP'], array_column($matches, 'word'));
    }

    public function testWordNumLimitsResultCount(): void
    {
        $matches = $this->matcher()->getTagWord('北京和PHP还有北京', 2);

        $this->assertSame(['北京', 'PHP'], array_column($matches, 'word'));
    }

    public function testReplaceAllOccurrencesByDefault(): void
    {
        $result = $this->matcher()->replace('PHP很好，PHP也很好');

        $this->assertSame(
            '<a href="https://example.com/php" >PHP</a>很好，<a href="https://example.com/php" >PHP</a>也很好',
            $result
        );
    }

    public function testReplaceOneOnlyReplacesFirstOccurrenceOfEachWord(): void
    {
        $result = $this->matcher()->replace('PHP很好，PHP也很好', '', true);

        $this->assertSame(
            '<a href="https://example.com/php" >PHP</a>很好，PHP也很好',
            $result
        );
    }

    public function testChineseAndEmojiRemainIntact(): void
    {
        $matches = $this->matcher()->getTagWord('😀北京大学🚀');

        $this->assertSame('北京大学', $matches[0]['word']);
    }
}
