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

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\AttributeBundle\Doctrine\ORM\Subscriber\LoadMetadataSubscriber;

final class LoadMetadataSubscriberTest extends TestCase
{
    private LoadMetadataSubscriber $loadMetadataSubscriber;

    protected function setUp(): void
    {
        parent::setUp();
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
        self::assertInstanceOf(EventSubscriber::class, $this->loadMetadataSubscriber);
    }

    public function testSubscribesLoadClassMetadataEvent(): void
    {
        self::assertSame(['loadClassMetadata'], $this->loadMetadataSubscriber->getSubscribedEvents());
    }

    public function testMapsManyToOneAssociationsFromAttributeValueModelToSubjectModelAndAttributeModel(): void
    {
        /** @var LoadClassMetadataEventArgs&MockObject $eventArgs */
        $eventArgs = $this->createMock(LoadClassMetadataEventArgs::class);
        /** @var ClassMetadata&MockObject $metadata */
        $metadata = $this->createMock(ClassMetadata::class);
        /** @var EntityManager&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        /** @var ClassMetadataFactory&MockObject $classMetadataFactory */
        $classMetadataFactory = $this->createMock(ClassMetadataFactory::class);
        /** @var ClassMetadata&MockObject $classMetadata */
        $classMetadata = $this->createMock(ClassMetadata::class);

        $eventArgs->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $eventArgs->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $entityManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($classMetadataFactory);

        $classMetadata->fieldMappings = [
            'id' => [
                'columnName' => 'id',
                'type' => 'integer',
                'id' => true,
                'nullable' => false,
                'fieldName' => 'id',
            ],
        ];

        $classMetadataFactory->expects($this->exactly(2))
            ->method('getMetadataFor')
            ->willReturnMap([
                ['Some\App\Product\Entity\Product', $classMetadata],
                ['Some\App\Product\Entity\Attribute', $classMetadata],
            ]);

        $metadata->expects($this->once())
            ->method('getName')
            ->willReturn('Some\App\Product\Entity\AttributeValue');

        $metadata->expects($this->exactly(2))
            ->method('hasAssociation')
            ->willReturnMap([
                ['subject', false],
                ['attribute', false],
            ]);

        $subjectMapping = [
            'fieldName' => 'subject',
            'targetEntity' => 'Some\App\Product\Entity\Product',
            'inversedBy' => 'attributes',
            'joinColumns' => [[
                'name' => 'product_id',
                'referencedColumnName' => 'id',
                'nullable' => false,
                'onDelete' => 'CASCADE',
            ]],
        ];

        $attributeMapping = [
            'fieldName' => 'attribute',
            'targetEntity' => 'Some\App\Product\Entity\Attribute',
            'joinColumns' => [[
                'name' => 'attribute_id',
                'referencedColumnName' => 'id',
                'nullable' => false,
            ]],
        ];

        $metadata->expects($this->exactly(2))
            ->method('mapManyToOne')
            ->willReturnCallback(function ($mapping) use ($subjectMapping, $attributeMapping) {
                static $callCount = 0;
                ++$callCount;

                if ($callCount === 1) {
                    $this->assertEquals($subjectMapping, $mapping);
                } elseif ($callCount === 2) {
                    $this->assertEquals($attributeMapping, $mapping);
                }
            });

        $this->loadMetadataSubscriber->loadClassMetadata($eventArgs);
    }

    public function testDoesNotMapRelationsForAttributeValueModelIfTheRelationsAlreadyExist(): void
    {
        /** @var LoadClassMetadataEventArgs&MockObject $eventArgsMock */
        $eventArgsMock = $this->createMock(LoadClassMetadataEventArgs::class);
        /** @var ClassMetadata&MockObject $metadataMock */
        $metadataMock = $this->createMock(ClassMetadata::class);
        /** @var EntityManager&MockObject $entityManagerMock */
        $entityManagerMock = $this->createMock(EntityManager::class);
        /** @var ClassMetadataFactory&MockObject $classMetadataFactoryMock */
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
        /** @var LoadClassMetadataEventArgs&MockObject $eventArgsMock */
        $eventArgsMock = $this->createMock(LoadClassMetadataEventArgs::class);
        /** @var ClassMetadata&MockObject $metadataMock */
        $metadataMock = $this->createMock(ClassMetadata::class);
        /** @var EntityManager&MockObject $entityManagerMock */
        $entityManagerMock = $this->createMock(EntityManager::class);
        /** @var ClassMetadataFactory&MockObject $classMetadataFactoryMock */
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
        /** @var LoadClassMetadataEventArgs&MockObject $eventArgsMock */
        $eventArgsMock = $this->createMock(LoadClassMetadataEventArgs::class);
        /** @var ClassMetadata&MockObject $metadataMock */
        $metadataMock = $this->createMock(ClassMetadata::class);
        /** @var EntityManager&MockObject $entityManagerMock */
        $entityManagerMock = $this->createMock(EntityManager::class);
        /** @var ClassMetadataFactory&MockObject $classMetadataFactoryMock */
        $classMetadataFactoryMock = $this->createMock(ClassMetadataFactory::class);
        $eventArgsMock->expects($this->once())->method('getEntityManager')->willReturn($entityManagerMock);
        $entityManagerMock->expects($this->once())->method('getMetadataFactory')->willReturn($classMetadataFactoryMock);
        $eventArgsMock->expects($this->once())->method('getClassMetadata')->willReturn($metadataMock);
        $metadataMock->expects($this->once())->method('getName')->willReturn('KeepMoving\ThisClass\DoesNot\Concern\You');
        $metadataMock->expects($this->never())->method('mapOneToMany');
        $this->loadMetadataSubscriber->loadClassMetadata($eventArgsMock);
    }
}
