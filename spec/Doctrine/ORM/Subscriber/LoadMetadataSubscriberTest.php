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

namespace Tests\Sylius\Bundle\AttributeBundle\Doctrine\ORM\Subscriber;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Sylius\Bundle\AttributeBundle\Doctrine\ORM\Subscriber\LoadMetadataSubscriber;

final class LoadMetadataSubscriberTest extends TestCase
{
    private LoadMetadataSubscriber $loadMetadataSubscriber;
    protected function setUp(): void
    {
        $this->loadMetadataSubscriber = new LoadMetadataSubscriber([
            'product' => [
                'subject' => 'Some\App\Product\Entity\Product',
                'attribute' => [
                    'classes' => [
                        'model' => 'Some\App\Product\Entity\Attribute',
                    ],
                ],
                'attribute_value' => [
                    'classes' => [
                        'model' => 'Some\App\Product\Entity\AttributeValue',
                    ],
                ],
            ],
        ]);
    }

    public function testADoctrineEventSubscriber(): void
    {
        $this->assertInstanceOf(EventSubscriber::class, $this->loadMetadataSubscriber);
    }

    public function testSubscribesLoadClassMetadataEvent(): void
    {
        $this->assertSame(['loadClassMetadata'], $this->loadMetadataSubscriber->getSubscribedEvents());
    }

    public function testMapsManyToOneAssociationsFromTheAttributeValueModelToTheSubjectModelAndTheAttributeModel(): void
    {
        /** @var LoadClassMetadataEventArgs|MockObject $eventArgsMock */
        $eventArgsMock = $this->createMock(LoadClassMetadataEventArgs::class);
        /** @var ClassMetadata|MockObject $metadataMock */
        $metadataMock = $this->createMock(ClassMetadata::class);
        /** @var EntityManager|MockObject $entityManagerMock */
        $entityManagerMock = $this->createMock(EntityManager::class);
        /** @var ClassMetadataFactory|MockObject $classMetadataFactoryMock */
        $classMetadataFactoryMock = $this->createMock(ClassMetadataFactory::class);
        /** @var ClassMetadata|MockObject $classMetadataMock */
        $classMetadataMock = $this->createMock(ClassMetadata::class);
        $eventArgsMock->expects($this->once())->method('getClassMetadata')->willReturn($metadataMock);
        $eventArgsMock->expects($this->once())->method('getEntityManager')->willReturn($entityManagerMock);
        $entityManagerMock->expects($this->once())->method('getMetadataFactory')->willReturn($classMetadataFactoryMock);
        $classMetadataMock->fieldMappings = [
            'id' => [
                'columnName' => 'id',
            ],
        ];
        $classMetadataFactoryMock->expects($this->exactly(2))->method('getMetadataFor')->willReturnMap([['Some\App\Product\Entity\Product', $classMetadataMock], ['Some\App\Product\Entity\Attribute', $classMetadataMock]]);
        $metadataMock->expects($this->once())->method('getName')->willReturn('Some\App\Product\Entity\AttributeValue');
        $metadataMock->expects($this->once())->method('hasAssociation')->with('subject')->willReturn(false);
        $metadataMock->expects($this->exactly(2))->method('hasAssociation')->willReturnMap([['subject', false], ['attribute', false]]);
        $attributeMapping = [
            'fieldName' => 'attribute',
            'targetEntity' => 'Some\App\Product\Entity\Attribute',
            'joinColumns' => [[
                'name' => 'attribute_id',
                'referencedColumnName' => 'id',
                'nullable' => false,
            ]],
        ];
        $metadataMock->expects($this->once())->method('mapManyToOne')->with($subjectMapping);
        $metadataMock->expects($this->once())->method('mapManyToOne')->with($attributeMapping);
        $metadataMock->expects($this->exactly(2))->method('mapManyToOne')->willReturnMap([[$subjectMapping], [$attributeMapping]]);
    }

    public function testDoesNotMapRelationsForAttributeValueModelIfTheRelationsAlreadyExist(): void
    {
        /** @var LoadClassMetadataEventArgs|MockObject $eventArgsMock */
        $eventArgsMock = $this->createMock(LoadClassMetadataEventArgs::class);
        /** @var ClassMetadata|MockObject $metadataMock */
        $metadataMock = $this->createMock(ClassMetadata::class);
        /** @var EntityManager|MockObject $entityManagerMock */
        $entityManagerMock = $this->createMock(EntityManager::class);
        /** @var ClassMetadataFactory|MockObject $classMetadataFactoryMock */
        $classMetadataFactoryMock = $this->createMock(ClassMetadataFactory::class);
        $eventArgsMock->expects($this->once())->method('getClassMetadata')->willReturn($metadataMock);
        $eventArgsMock->expects($this->once())->method('getEntityManager')->willReturn($entityManagerMock);
        $entityManagerMock->expects($this->once())->method('getMetadataFactory')->willReturn($classMetadataFactoryMock);
        $metadataMock->expects($this->once())->method('getName')->willReturn('Some\App\Product\Entity\AttributeValue');
        $metadataMock->expects($this->exactly(2))->method('hasAssociation')->willReturnMap([['subject', true], ['attribute', true]]);
        $metadataMock->expects($this->never())->method('mapManyToOne');
        $this->loadMetadataSubscriber->loadClassMetadata($eventArgsMock);
    }

    public function testDoesNotAddAManyToOneMappingIfTheClassIsNotAConfiguredAttributeValueModel(): void
    {
        /** @var LoadClassMetadataEventArgs|MockObject $eventArgsMock */
        $eventArgsMock = $this->createMock(LoadClassMetadataEventArgs::class);
        /** @var ClassMetadata|MockObject $metadataMock */
        $metadataMock = $this->createMock(ClassMetadata::class);
        /** @var EntityManager|MockObject $entityManagerMock */
        $entityManagerMock = $this->createMock(EntityManager::class);
        /** @var ClassMetadataFactory|MockObject $classMetadataFactoryMock */
        $classMetadataFactoryMock = $this->createMock(ClassMetadataFactory::class);
        $eventArgsMock->expects($this->once())->method('getEntityManager')->willReturn($entityManagerMock);
        $entityManagerMock->expects($this->once())->method('getMetadataFactory')->willReturn($classMetadataFactoryMock);
        $eventArgsMock->expects($this->once())->method('getClassMetadata')->willReturn($metadataMock);
        $metadataMock->expects($this->once())->method('getName')->willReturn('KeepMoving\ThisClass\DoesNot\Concern\You');
        $metadataMock->expects($this->never())->method('mapManyToOne');
        $this->loadMetadataSubscriber->loadClassMetadata($eventArgsMock);
    }

    public function testDoesNotAddValuesOneToManyMappingIfTheClassIsNotAConfiguredAttributeModel(): void
    {
        /** @var LoadClassMetadataEventArgs|MockObject $eventArgsMock */
        $eventArgsMock = $this->createMock(LoadClassMetadataEventArgs::class);
        /** @var ClassMetadata|MockObject $metadataMock */
        $metadataMock = $this->createMock(ClassMetadata::class);
        /** @var EntityManager|MockObject $entityManagerMock */
        $entityManagerMock = $this->createMock(EntityManager::class);
        /** @var ClassMetadataFactory|MockObject $classMetadataFactoryMock */
        $classMetadataFactoryMock = $this->createMock(ClassMetadataFactory::class);
        $eventArgsMock->expects($this->once())->method('getEntityManager')->willReturn($entityManagerMock);
        $entityManagerMock->expects($this->once())->method('getMetadataFactory')->willReturn($classMetadataFactoryMock);
        $eventArgsMock->expects($this->once())->method('getClassMetadata')->willReturn($metadataMock);
        $metadataMock->expects($this->once())->method('getName')->willReturn('KeepMoving\ThisClass\DoesNot\Concern\You');
        $metadataMock->expects($this->never())->method('mapOneToMany');
        $this->loadMetadataSubscriber->loadClassMetadata($eventArgsMock);
    }
}
