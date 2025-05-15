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
use Sylius\Bundle\AttributeBundle\Validator\Constraints\ValidTextAttributeConfiguration;
use Sylius\Bundle\AttributeBundle\Validator\Constraints\ValidTextAttributeConfigurationValidator;
use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ValidTextAttributeConfigurationValidatorTest extends TestCase
{
    /** @var ExecutionContextInterface&MockObject */
    private MockObject $contextMock;

    private ValidTextAttributeConfigurationValidator $validTextAttributeConfigurationValidator;

    protected function setUp(): void
    {
        $this->contextMock = $this->createMock(ExecutionContextInterface::class);
        $this->validTextAttributeConfigurationValidator = new ValidTextAttributeConfigurationValidator();
        $this->initialize($this->contextMock);
    }

    private function initialize(ExecutionContextInterface $context): void
    {
        $this->validTextAttributeConfigurationValidator->initialize($context);
    }

    public function testAddsAViolationIfMaxEntriesValueIsLowerThanMinEntriesValue(): void
    {
        /** @var AttributeInterface&MockObject $attributeMock */
        $attributeMock = $this->createMock(AttributeInterface::class);
        $constraint = new ValidTextAttributeConfiguration();
        $attributeMock->expects($this->once())->method('getType')->willReturn(TextAttributeType::TYPE);
        $attributeMock->expects($this->once())->method('getConfiguration')->willReturn(['min' => 6, 'max' => 4]);
        $this->contextMock->expects($this->once())->method('addViolation');
        $this->validTextAttributeConfigurationValidator->validate($attributeMock, $constraint);
    }

    public function testDoesNothingIfAnAttributeIsNotATextType(): void
    {
        /** @var AttributeInterface&MockObject $attributeMock */
        $attributeMock = $this->createMock(AttributeInterface::class);
        $constraint = new ValidTextAttributeConfiguration();
        $attributeMock->expects($this->once())->method('getType')->willReturn(SelectAttributeType::TYPE);
        $this->contextMock->expects($this->never())->method('addViolation');
        $this->validTextAttributeConfigurationValidator->validate($attributeMock, $constraint);
    }

    public function testThrowsAnExceptionIfValidatedValueIsNotAnAttribute(): void
    {
        $constraint = new ValidTextAttributeConfiguration();
        $this->expectException(InvalidArgumentException::class);
        $this->validTextAttributeConfigurationValidator->validate('badObject', $constraint);
    }

    public function testThrowsAnExceptionIfConstraintIsNotAValidTextAttributeConfigurationConstraint(): void
    {
        /** @var AttributeInterface|MockObject $attributeMock */
        $attributeMock = $this->createMock(AttributeInterface::class);
        $constraint = new ValidSelectAttributeConfiguration();
        $this->expectException(InvalidArgumentException::class);
        $this->validTextAttributeConfigurationValidator->validate($attributeMock, $constraint);
    }
}
