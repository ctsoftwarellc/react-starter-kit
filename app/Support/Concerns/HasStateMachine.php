<?php

namespace App\Support\Concerns;

use InvalidArgumentException;

trait HasStateMachine
{
    /**
     * Transition the model to a new status.
     *
     * @throws InvalidArgumentException
     */
    public function transitionTo(string|\BackedEnum $newStatus): static
    {
        $newStatus = $newStatus instanceof \BackedEnum ? $newStatus : $this->getStatusEnum()::from($newStatus);
        $currentStatus = $this->{$this->getStatusColumn()};

        if ($currentStatus instanceof \BackedEnum) {
            $currentValue = $currentStatus->value;
        } else {
            $currentValue = $currentStatus;
        }

        $allowed = $this->getAllowedTransitions();
        $key = $currentValue;

        if (! isset($allowed[$key]) || ! in_array($newStatus, $allowed[$key], true)) {
            throw new InvalidArgumentException(
                "Invalid transition from [{$key}] to [{$newStatus->value}] on ".static::class
            );
        }

        $this->{$this->getStatusColumn()} = $newStatus;
        $this->save();

        return $this;
    }

    /**
     * Check if a transition to the given status is valid.
     */
    public function canTransitionTo(string|\BackedEnum $newStatus): bool
    {
        $newStatus = $newStatus instanceof \BackedEnum ? $newStatus : $this->getStatusEnum()::from($newStatus);
        $currentStatus = $this->{$this->getStatusColumn()};

        if ($currentStatus instanceof \BackedEnum) {
            $currentValue = $currentStatus->value;
        } else {
            $currentValue = $currentStatus;
        }

        $allowed = $this->getAllowedTransitions();

        return isset($allowed[$currentValue]) && in_array($newStatus, $allowed[$currentValue], true);
    }

    /**
     * Get the column name that holds the status.
     */
    protected function getStatusColumn(): string
    {
        return 'status';
    }

    /**
     * Get the enum class for the status column.
     *
     * @return class-string<\BackedEnum>
     */
    abstract protected function getStatusEnum(): string;

    /**
     * Get the allowed transitions map.
     *
     * Return format: ['current_status_value' => [NewStatusEnum::Case, ...]]
     *
     * @return array<string, array<\BackedEnum>>
     */
    abstract protected function getAllowedTransitions(): array;
}
