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

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Sylius\Bundle\AttributeBundle\Validator\Constraints\AttributeTypeValidator;
use InvalidArgumentException;
use stdClass;
use Sylius\Bundle\AttributeBundle\Validator\Constraints\AttributeType;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class AttributeTypeValidatorTest extends TestCase
{
    /**
     * @var ServiceRegistryInterface|MockObject
     */
    private MockObject $attributeTypesRegistryMock;
    /**
     * @var ExecutionContextInterface|MockObject
     */
    private MockObject $contextMock;
    private AttributeTypeValidator $attributeTypeValidator;
    protected function setUp(): void
    {
        $this->attributeTypesRegistryMock = $this->createMock(ServiceRegistryInterface::class);
        $this->contextMock = $this->createMock(ExecutionContextInterface::class);
        $this->attributeTypeValidator = new AttributeTypeValidator($this->attributeTypesRegistryMock);
        $this->initialize($this->contextMock);
    }

    public function testThrowsExceptionWhenValueIsNotAnAttribute(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->attributeTypeValidator->validate(new stdClass(), new AttributeType());
    }

    public function testThrowsExceptionWhenConstraintIsNotAnAttributeType(): void
    {
        /** @var AttributeInterface|MockObject $attributeMock */
        $attributeMock = $this->createMock(AttributeInterface::class);
        /** @var Constraint|MockObject $constraintMock */
        $constraintMock = $this->createMock(Constraint::class);
        $this->expectException(InvalidArgumentException::class);
        $this->attributeTypeValidator->validate($attributeMock, $constraintMock);
    }

    public function testDoesNothingWhenAttributeTypeIsNull(): void
    {
        /** @var AttributeInterface|MockObject $attributeMock */
        $attributeMock = $this->createMock(AttributeInterface::class);
        $attributeMock->expects($this->once())->method('getType')->willReturn(null);
        $this->attributeTypesRegistryMock->expects($this->never())->method('has');
        $this->contextMock->expects($this->never())->method('addViolation');
        $this->attributeTypeValidator->validate($attributeMock, new AttributeType());
    }

    public function testDoesNothingWhenAttributeTypeIsRegistered(): void
    {
        /** @var AttributeInterface|MockObject $attributeMock */
        $attributeMock = $this->createMock(AttributeInterface::class);
        $attributeMock->expects($this->once())->method('getType')->willReturn('foo');
        $this->attributeTypesRegistryMock->expects($this->once())->method('has')->with('foo')->willReturn(true);
        $this->contextMock->expects($this->never())->method('addViolation');
        $this->attributeTypeValidator->validate($attributeMock, new AttributeType());
    }

    public function testAddsViolationWhenAttributeTypeIsNotRegistered(): void
    {
        /** @var ConstraintViolationBuilderInterface|MockObject $violationBuilderMock */
        $violationBuilderMock = $this->createMock(ConstraintViolationBuilderInterface::class);
        /** @var AttributeInterface|MockObject $attributeMock */
        $attributeMock = $this->createMock(AttributeInterface::class);
        $constraint = new AttributeType();
        $attributeMock->expects($this->once())->method('getType')->willReturn('foo');
        $this->attributeTypesRegistryMock->expects($this->once())->method('has')->with('foo')->willReturn(false);
        $this->attributeTypesRegistryMock->expects($this->once())->method('all')->willReturn(['foo_attribute_name' => 'foo_value', 'bar_attribute_name' => 'bar_value']);
        $this->contextMock->expects($this->once())->method('buildViolation')->with($constraint->unregisteredAttributeTypeMessage, [
            '%type%' => 'foo', '%available_types%' => 'foo_attribute_name, bar_attribute_name',
        ])
            ->willReturn($violationBuilderMock);
        $violationBuilderMock->expects($this->once())->method('atPath')->with('type')->willReturn($violationBuilderMock);
        $violationBuilderMock->expects($this->once())->method('addViolation');
        $this->attributeTypeValidator->validate($attributeMock, $constraint);
    }
}
