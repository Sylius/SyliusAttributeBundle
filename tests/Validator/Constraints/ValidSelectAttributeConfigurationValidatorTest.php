<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Sylius\Bundle\AttributeBundle\Validator\Constraints;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\AttributeBundle\Validator\Constraints\ValidSelectAttributeConfiguration;
use Sylius\Bundle\AttributeBundle\Validator\Constraints\ValidSelectAttributeConfigurationValidator;
use Sylius\Bundle\AttributeBundle\Validator\Constraints\ValidTextAttributeConfiguration;
use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ValidSelectAttributeConfigurationValidatorTest extends TestCase
{
    /** @var ExecutionContextInterface&MockObject */
    private MockObject $contextMock;

    private ValidSelectAttributeConfigurationValidator $validSelectAttributeConfigurationValidator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contextMock = $this->createMock(ExecutionContextInterface::class);
        $this->validSelectAttributeConfigurationValidator = new ValidSelectAttributeConfigurationValidator();
        $this->initialize($this->contextMock);
    }

    private function initialize(ExecutionContextInterface $context): void
    {
        $this->validSelectAttributeConfigurationValidator->initialize($context);
    }

    public function testAddsAViolationIfMaxEntriesValueIsLowerThanMinEntriesValue(): void
    {
        /** @var AttributeInterface&MockObject $attributeMock */
        $attributeMock = $this->createMock(AttributeInterface::class);
        $constraint = new ValidSelectAttributeConfiguration();
        $attributeMock->expects(self::once())->method('getType')->willReturn(SelectAttributeType::TYPE);
        $attributeMock->expects(self::atLeastOnce())->method('getConfiguration')->willReturn(['multiple' => true, 'min' => 6, 'max' => 4]);
        $this->contextMock->expects(self::once())->method('addViolation');
        $this->validSelectAttributeConfigurationValidator->validate($attributeMock, $constraint);
    }

    public function testAddsAViolationIfMinEntriesValueIsGreaterThanTheNumberOfAddedChoices(): void
    {
        /** @var AttributeInterface&MockObject $attributeMock */
        $attributeMock = $this->createMock(AttributeInterface::class);
        $constraint = new ValidSelectAttributeConfiguration();
        $attributeMock->expects(self::once())->method('getType')->willReturn(SelectAttributeType::TYPE);
        $attributeMock->expects(self::atLeastOnce())->method('getConfiguration')->willReturn([
            'multiple' => true,
            'min' => 4,
            'max' => 6,
            'choices' => [
                'ec134e10-6a80-4eaf-8346-e9bb0f7406a4' => 'Banana',
                '63148775-be39-47eb-8afd-a4818981e3c0' => 'Watermelon',
            ],
        ]);
        $this->contextMock->expects(self::once())->method('addViolation');
        $this->validSelectAttributeConfigurationValidator->validate($attributeMock, $constraint);
    }

    public function testAddsAViolationIfMultipleIsNotTrueWhenMinOrMaxEntriesValuesAreSpecified(): void
    {
        /** @var AttributeInterface&MockObject $attributeMock */
        $attributeMock = $this->createMock(AttributeInterface::class);
        $constraint = new ValidSelectAttributeConfiguration();
        $attributeMock->expects(self::once())->method('getType')->willReturn(SelectAttributeType::TYPE);
        $attributeMock->expects(self::atLeastOnce())->method('getConfiguration')->willReturn(['multiple' => false, 'min' => 4, 'max' => 6]);
        $this->contextMock->expects(self::once())->method('addViolation');
        $this->validSelectAttributeConfigurationValidator->validate($attributeMock, $constraint);
    }

    public function testAddsAViolationIfMultipleIsNotSetWhenMinOrMaxEntriesValuesAreSpecified(): void
    {
        /** @var AttributeInterface&MockObject $attributeMock */
        $attributeMock = $this->createMock(AttributeInterface::class);
        $constraint = new ValidSelectAttributeConfiguration();
        $attributeMock->expects(self::once())->method('getType')->willReturn(SelectAttributeType::TYPE);
        $attributeMock->expects(self::atLeastOnce())->method('getConfiguration')->willReturn(['min' => 4, 'max' => 6]);
        $this->contextMock->expects(self::once())->method('addViolation');
        $this->validSelectAttributeConfigurationValidator->validate($attributeMock, $constraint);
    }

    public function testDoesNothingIfAnAttributeIsNotASelectType(): void
    {
        /** @var AttributeInterface&MockObject $attributeMock */
        $attributeMock = $this->createMock(AttributeInterface::class);
        $constraint = new ValidSelectAttributeConfiguration();
        $attributeMock->expects(self::once())->method('getType')->willReturn(TextAttributeType::TYPE);
        $this->contextMock->expects($this->never())->method('addViolation');
        $this->validSelectAttributeConfigurationValidator->validate($attributeMock, $constraint);
    }

    public function testThrowsAnExceptionIfValidatedValueIsNotAnAttribute(): void
    {
        $constraint = new ValidSelectAttributeConfiguration();
        $this->expectException(InvalidArgumentException::class);
        $this->validSelectAttributeConfigurationValidator->validate('badObject', $constraint);
    }

    public function testThrowsAnExceptionIfConstraintIsNotAValidSelectAttributeConfigurationConstraint(): void
    {
        /** @var AttributeInterface&MockObject $attributeMock */
        $attributeMock = $this->createMock(AttributeInterface::class);
        $constraint = new ValidTextAttributeConfiguration();
        $this->expectException(InvalidArgumentException::class);
        $this->validSelectAttributeConfigurationValidator->validate($attributeMock, $constraint);
    }
}
