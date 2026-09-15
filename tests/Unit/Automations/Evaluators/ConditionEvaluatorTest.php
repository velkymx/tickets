<?php

namespace Tests\Unit\Automations\Evaluators;

use App\Automations\Evaluators\ConditionEvaluator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConditionEvaluatorTest extends TestCase
{
    private ConditionEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new ConditionEvaluator();
    }

    private function ctx(): array
    {
        return [
            'ticket' => ['priority' => 'critical', 'status' => 'open', 'subject' => 'Prod issue', 'id' => 99],
            'changes' => ['status' => 'open'],
        ];
    }

    #[Test]
    public function empty_all_group_matches(): void
    {
        $this->assertTrue($this->evaluator->matches(['all' => []], $this->ctx()));
    }

    #[Test]
    public function equals_operator_matches(): void
    {
        $conditions = ['all' => [
            ['field' => 'ticket.priority', 'operator' => 'equals', 'value' => 'critical'],
        ]];
        $this->assertTrue($this->evaluator->matches($conditions, $this->ctx()));
    }

    #[Test]
    public function equals_operator_does_not_match_wrong_value(): void
    {
        $conditions = ['all' => [
            ['field' => 'ticket.priority', 'operator' => 'equals', 'value' => 'low'],
        ]];
        $this->assertFalse($this->evaluator->matches($conditions, $this->ctx()));
    }

    #[Test]
    public function not_equals_operator(): void
    {
        $conditions = ['all' => [
            ['field' => 'ticket.priority', 'operator' => 'not_equals', 'value' => 'low'],
        ]];
        $this->assertTrue($this->evaluator->matches($conditions, $this->ctx()));
    }

    #[Test]
    public function contains_operator(): void
    {
        $conditions = ['all' => [
            ['field' => 'ticket.subject', 'operator' => 'contains', 'value' => 'Prod'],
        ]];
        $this->assertTrue($this->evaluator->matches($conditions, $this->ctx()));
    }

    #[Test]
    public function starts_with_operator(): void
    {
        $conditions = ['all' => [
            ['field' => 'ticket.subject', 'operator' => 'starts_with', 'value' => 'Prod'],
        ]];
        $this->assertTrue($this->evaluator->matches($conditions, $this->ctx()));
    }

    #[Test]
    public function ends_with_operator(): void
    {
        $conditions = ['all' => [
            ['field' => 'ticket.subject', 'operator' => 'ends_with', 'value' => 'issue'],
        ]];
        $this->assertTrue($this->evaluator->matches($conditions, $this->ctx()));
    }

    #[Test]
    public function greater_than_operator(): void
    {
        $conditions = ['all' => [
            ['field' => 'ticket.id', 'operator' => 'greater_than', 'value' => 10],
        ]];
        $this->assertTrue($this->evaluator->matches($conditions, $this->ctx()));
    }

    #[Test]
    public function less_than_operator(): void
    {
        $conditions = ['all' => [
            ['field' => 'ticket.id', 'operator' => 'less_than', 'value' => 1000],
        ]];
        $this->assertTrue($this->evaluator->matches($conditions, $this->ctx()));
    }

    #[Test]
    public function equals_does_not_loosely_match_null_field_against_zero(): void
    {
        // A missing field resolves to null; `null == 0` is true under loose
        // comparison but must not fire an equals condition.
        $conditions = ['all' => [
            ['field' => 'ticket.nonexistent', 'operator' => 'equals', 'value' => 0],
        ]];
        $this->assertFalse($this->evaluator->matches($conditions, $this->ctx()));
    }

    #[Test]
    public function not_equals_treats_null_field_as_different_from_zero(): void
    {
        $conditions = ['all' => [
            ['field' => 'ticket.nonexistent', 'operator' => 'not_equals', 'value' => 0],
        ]];
        $this->assertTrue($this->evaluator->matches($conditions, $this->ctx()));
    }

    #[Test]
    public function greater_than_compares_numeric_strings(): void
    {
        $conditions = ['all' => [
            ['field' => 'ticket.id', 'operator' => 'greater_than', 'value' => '10'],
        ]];
        $this->assertTrue($this->evaluator->matches($conditions, $this->ctx()));
    }

    #[Test]
    public function in_operator(): void
    {
        $conditions = ['all' => [
            ['field' => 'ticket.priority', 'operator' => 'in', 'value' => ['critical', 'high']],
        ]];
        $this->assertTrue($this->evaluator->matches($conditions, $this->ctx()));
    }

    #[Test]
    public function not_in_operator(): void
    {
        $conditions = ['all' => [
            ['field' => 'ticket.priority', 'operator' => 'not_in', 'value' => ['low', 'medium']],
        ]];
        $this->assertTrue($this->evaluator->matches($conditions, $this->ctx()));
    }

    #[Test]
    public function is_empty_operator(): void
    {
        $ctx = ['ticket' => ['priority' => 'critical', 'status' => 'open', 'subject' => 'Prod issue', 'id' => 99, 'project' => null], 'changes' => []];
        $conditions = ['all' => [
            ['field' => 'ticket.project', 'operator' => 'is_empty'],
        ]];
        $this->assertTrue($this->evaluator->matches($conditions, $ctx));
    }

    #[Test]
    public function is_not_empty_operator(): void
    {
        $conditions = ['all' => [
            ['field' => 'ticket.priority', 'operator' => 'is_not_empty'],
        ]];
        $this->assertTrue($this->evaluator->matches($conditions, $this->ctx()));
    }

    #[Test]
    public function changed_operator_checks_changes_array(): void
    {
        $conditions = ['all' => [
            ['field' => 'status', 'operator' => 'changed'],
        ]];
        $this->assertTrue($this->evaluator->matches($conditions, $this->ctx()));
    }

    #[Test]
    public function any_group_returns_true_if_one_matches(): void
    {
        $conditions = ['any' => [
            ['field' => 'ticket.priority', 'operator' => 'equals', 'value' => 'low'],
            ['field' => 'ticket.priority', 'operator' => 'equals', 'value' => 'critical'],
        ]];
        $this->assertTrue($this->evaluator->matches($conditions, $this->ctx()));
    }

    #[Test]
    public function all_group_returns_false_if_one_fails(): void
    {
        $conditions = ['all' => [
            ['field' => 'ticket.priority', 'operator' => 'equals', 'value' => 'critical'],
            ['field' => 'ticket.status', 'operator' => 'equals', 'value' => 'closed'],
        ]];
        $this->assertFalse($this->evaluator->matches($conditions, $this->ctx()));
    }

    #[Test]
    public function nested_groups_evaluate_correctly(): void
    {
        $conditions = ['all' => [
            ['field' => 'ticket.priority', 'operator' => 'equals', 'value' => 'critical'],
            ['any' => [
                ['field' => 'ticket.status', 'operator' => 'equals', 'value' => 'open'],
                ['field' => 'ticket.status', 'operator' => 'equals', 'value' => 'pending'],
            ]],
        ]];
        $this->assertTrue($this->evaluator->matches($conditions, $this->ctx()));
    }
}
