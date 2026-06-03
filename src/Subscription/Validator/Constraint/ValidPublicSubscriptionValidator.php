<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Validator\Constraint;

use PhpList\Core\Domain\Common\Model\AttributeTypeEnum;
use PhpList\RestBundle\Subscription\Request\PublicSubscriptionRequest;
use PhpList\RestBundle\Subscription\Service\PublicSubscriptionAttributeRuleProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidPublicSubscriptionValidator extends ConstraintValidator
{
    public function __construct(
        private readonly PublicSubscriptionAttributeRuleProvider $ruleProvider,
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidPublicSubscription) {
            throw new UnexpectedTypeException($constraint, ValidPublicSubscription::class);
        }

        if (!$value instanceof PublicSubscriptionRequest) {
            return;
        }

        $page = $value->getSubscribePage();
        if ($page === null) {
            return;
        }

        $rules = $this->ruleProvider->getRules($page);
        $submitted = is_array($value->attributes) ? $value->attributes : [];
        $submittedByKey = [];
        foreach ($submitted as $rawKey => $rawValue) {
            $key = mb_strtolower(trim((string) $rawKey));
            if ($key === '') {
                continue;
            }
            $submittedByKey[$key] = ['path' => (string) $rawKey, 'value' => $rawValue];
        }

        if ($constraint->rejectUnknownAttributes) {
            foreach ($submittedByKey as $key => $entry) {
                if (!isset($rules[$key])) {
                    $this->context->buildViolation($constraint->unknownAttributeMessage)
                        ->atPath('attributes.' . $entry['path'])
                        ->addViolation();
                }
            }
        }

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
                continue;
            }

            if ($rule['max_length'] !== null) {
                $asString = trim((string) $submittedValue);
                if (mb_strlen($asString) > $rule['max_length']) {
                    $this->context->buildViolation($constraint->invalidValueMessage)
                        ->atPath('attributes.' . $pathKey)
                        ->addViolation();
                }
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
        $type = $rule['type'];
        $allowed = $rule['allowed'];

        if ($type === AttributeTypeEnum::Checkbox) {
            return is_bool($value)
                || is_numeric($value)
                || in_array(mb_strtolower(trim((string) $value)), ['on', 'off', 'true', 'false', 'yes', 'no'], true);
        }

        if ($type === AttributeTypeEnum::CheckboxGroup) {
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

        if ($type === AttributeTypeEnum::Select || $type === AttributeTypeEnum::Radio) {
            return isset($allowed[(string) $value]);
        }

        if ($type === AttributeTypeEnum::Date) {
            return $this->isValidDateValue($value);
        }

        if ($type === AttributeTypeEnum::Number) {
            return is_numeric($value);
        }

        if (is_array($value) || is_object($value)) {
            return false;
        }

        return true;
    }

    private function isValidDateValue(mixed $value): bool
    {
        if (is_array($value)) {
            $year = $value['year'] ?? $value['yyyy'] ?? null;
            $month = $value['month'] ?? $value['mm'] ?? null;
            $day = $value['day'] ?? $value['dd'] ?? null;

            if (!is_numeric($year) || !is_numeric($month) || !is_numeric($day)) {
                return false;
            }

            return checkdate((int) $month, (int) $day, (int) $year);
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return false;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $stringValue);
        if ($date !== false && $date->format('Y-m-d') === $stringValue) {
            return true;
        }

        return strtotime($stringValue) !== false;
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
}
