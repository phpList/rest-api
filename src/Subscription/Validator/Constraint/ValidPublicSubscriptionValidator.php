<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Validator\Constraint;

use DateTimeImmutable;
use PhpList\Core\Domain\Common\Model\AttributeTypeEnum;
use PhpList\RestBundle\Subscription\Request\PublicSubscriptionRequest;
use PhpList\RestBundle\Subscription\Service\PublicSubscriptionAttributeRuleProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidPublicSubscriptionValidator extends ConstraintValidator
{
    private const VALID_CHECKBOX_VALUES = ['on', 'off', 'true', 'false', 'yes', 'no'];

    public function __construct(
        private readonly PublicSubscriptionAttributeRuleProvider $ruleProvider,
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidPublicSubscription) {
            throw new UnexpectedTypeException($constraint, ValidPublicSubscription::class);
        }

        if ($this->doesNotSupportValidation($value)) {
            return;
        }

        $rules = $this->ruleProvider->getRules($value->getSubscribePage());
        $submittedByKey = $this->mapSubmittedByKey($value);
        $this->rejectUnknownAttributes($submittedByKey, $rules, $constraint);

        foreach ($rules as $key => $rule) {
            $submittedEntry = $submittedByKey[$key] ?? null;
            $submittedValue = $submittedEntry['value'] ?? null;
            $pathKey = $submittedEntry['path'] ?? $rule['key'];

            if ($rule['required'] && $this->isEmptyValue($submittedValue, $rule['type'])) {
                $this->context->buildViolation($constraint->requiredAttributeMessage)
                    ->atPath('attributes.' . $pathKey)
                    ->addViolation();
                continue;
            }

            if ($this->isEmptyValue($submittedValue, $rule['type'])) {
                continue;
            }

            if (!$this->isValidTypeValue($submittedValue, $rule)) {
                $this->context->buildViolation($constraint->invalidValueMessage)
                    ->atPath('attributes.' . $pathKey)
                    ->addViolation();
            }
        }
    }

    private function isEmptyValue(mixed $value, ?AttributeTypeEnum $type): bool
    {
        if ($type === AttributeTypeEnum::CheckboxGroup) {
            return !is_array($value) || $value === [];
        }

        if ($type === AttributeTypeEnum::Checkbox) {
            return !$this->toBool($value);
        }

        if (is_array($value)) {
            return $value === [];
        }

        return trim((string) $value) === '';
    }

    /**
     * @param array{
     *     type:AttributeTypeEnum|null,
     *     allowed:array<string,true>
     * } $rule
     */
    private function isValidTypeValue(mixed $value, array $rule): bool
    {
        return match ($rule['type']) {
            AttributeTypeEnum::Checkbox => $this->isValidCheckboxValue($value),
            AttributeTypeEnum::CheckboxGroup => $this->isValidCheckboxGroupValue($value, $rule['allowed']),
            AttributeTypeEnum::Select,
            AttributeTypeEnum::Radio => isset($rule['allowed'][(string) $value]),
            AttributeTypeEnum::Date => $this->isValidDateValue($value),
            AttributeTypeEnum::Number => is_numeric($value),
            default => $this->isValidScalarValue($value),
        };
    }

    private function isValidScalarValue(mixed $value): bool
    {
        if (is_array($value) || is_object($value)) {
            return false;
        }

        return true;
    }

    private function isValidCheckboxValue(mixed $value): bool
    {
        return is_bool($value)
            || is_numeric($value)
            || in_array(mb_strtolower(trim((string) $value)), self::VALID_CHECKBOX_VALUES, true);
    }

    private function isValidCheckboxGroupValue(mixed $value, mixed $allowed): bool
    {
        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (!isset($allowed[(string) $item])) {
                return false;
            }
        }

        return true;
    }

    private function isValidDateValue(mixed $value): bool
    {
        if (is_array($value)) {
            return $this->isValidDateArray($value);
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $stringValue);
        if ($date !== false && $date->format('Y-m-d') === $stringValue) {
            return true;
        }

        return strtotime($stringValue) !== false;
    }

    private function isValidDateArray(array $value): bool
    {
        $year = $value['year'] ?? $value['yyyy'] ?? null;
        $month = $value['month'] ?? $value['mm'] ?? null;
        $day = $value['day'] ?? $value['dd'] ?? null;

        if (!is_numeric($year) || !is_numeric($month) || !is_numeric($day)) {
            return false;
        }

        return checkdate((int) $month, (int) $day, (int) $year);
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return in_array(mb_strtolower(trim((string) $value)), ['1', 'on', 'true', 'yes'], true);
    }

    private function doesNotSupportValidation($value): bool
    {
        if (!$value instanceof PublicSubscriptionRequest) {
            return true;
        }

        $page = $value->getSubscribePage();
        if ($page === null) {
            return true;
        }

        return false;
    }

    private function rejectUnknownAttributes(
        array $submittedByKey,
        array $rules,
        ValidPublicSubscription $constraint
    ): void {
        if ($constraint->rejectUnknownAttributes) {
            foreach ($submittedByKey as $key => $entry) {
                if (!isset($rules[$key])) {
                    $this->context->buildViolation($constraint->unknownAttributeMessage)
                        ->atPath('attributes.' . $entry['path'])
                        ->addViolation();
                }
            }
        }
    }

    private function mapSubmittedByKey(mixed $value): array
    {
        $submittedByKey = [];
        foreach ($value->attributes as $rawKey => $rawValue) {
            $key = mb_strtolower(trim((string) $rawKey));
            if ($key === '') {
                continue;
            }
            $submittedByKey[$key] = ['path' => (string) $rawKey, 'value' => $rawValue];
        }

        return $submittedByKey;
    }
}
