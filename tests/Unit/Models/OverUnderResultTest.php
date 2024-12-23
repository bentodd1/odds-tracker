<?php

namespace Tests\Unit\Models;

use App\Models\OverUnderResult;
use PHPUnit\Framework\TestCase;

class OverUnderResultTest extends TestCase
{
    /**
     * @dataProvider provideTestCases
     */
    public function testVariousScenarios($actualTotal, $line, $overOdds, $underOdds, $expectedWentOver, $expectedProfitLoss)
    {
        $result = OverUnderResult::calculateResult($actualTotal, $line, $overOdds, $underOdds);

        $this->assertEquals($expectedWentOver, $result['went_over']);
        $this->assertEquals($expectedProfitLoss, $result['profit_loss']);
    }

    public static function provideTestCases()
    {
        return [
            'Over hits with positive odds' => [
                'actualTotal' => 55,
                'line' => 45.5,
                'overOdds' => 110,
                'underOdds' => -110,
                'expectedWentOver' => true,
                'expectedProfitLoss' => 110.00
            ],
            'Under hits with negative odds' => [
                'actualTotal' => 35,
                'line' => 45.5,
                'overOdds' => 110,
                'underOdds' => -110,
                'expectedWentOver' => false,
                'expectedProfitLoss' => 90.91
            ],
            'Push case' => [
                'actualTotal' => 45.5,
                'line' => 45.5,
                'overOdds' => 110,
                'underOdds' => -110,
                'expectedWentOver' => null,
                'expectedProfitLoss' => 0
            ],
            'Over loses' => [
                'actualTotal' => 44,
                'line' => 45.5,
                'overOdds' => 110,
                'underOdds' => -110,
                'expectedWentOver' => false,
                'expectedProfitLoss' => 90.91
            ],
            'Under loses' => [
                'actualTotal' => 46,
                'line' => 45.5,
                'overOdds' => -110,
                'underOdds' => 110,
                'expectedWentOver' => true,
                'expectedProfitLoss' => 90.91
            ],
            'Even odds push' => [
                'actualTotal' => 45,
                'line' => 45,
                'overOdds' => 100,
                'underOdds' => 100,
                'expectedWentOver' => null,
                'expectedProfitLoss' => 0
            ],
        ];
    }

    public function testProfitCalculationWithVariousOdds()
    {
        // Test positive odds
        $result = OverUnderResult::calculateResult(55, 45.5, 150, -110);
        $this->assertEquals(150.00, $result['profit_loss']);

        // Test negative odds
        $result = OverUnderResult::calculateResult(35, 45.5, 150, -110);
        $this->assertEquals(90.91, $result['profit_loss']);
    }
}
