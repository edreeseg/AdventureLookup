<?php


namespace Tests\AppBundle\Service;

use AppBundle\Entity\Adventure;
use AppBundle\Entity\Author;
use AppBundle\Entity\Edition;
use AppBundle\Entity\Publisher;
use AppBundle\Field\Field;
use AppBundle\Field\FieldProvider;
use AppBundle\Service\AdventureSerializer;
use Doctrine\Common\Collections\ArrayCollection;

class AdventureSerializerTest extends \PHPUnit_Framework_TestCase
{
    const TITLE = 'a title';
    const SLUG = 'a-title';
    const MIN_STARTING_LEVEL = 5;
    const TACTICAL_MAPS = true;
    const LINK = 'http://example.org';
    const AUTHOR_1 = 'an author 1';
    const AUTHOR_2 = 'an author 2';
    const PUBLISHER = 'a publisher';

    /**
     * @var AdventureSerializer
     */
    private $serializer;

    /**
     * @var FieldProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fieldProvider;

    public function setUp()
    {
        $this->fieldProvider = $this->createMock(FieldProvider::class);
        $this->serializer = new AdventureSerializer($this->fieldProvider);
    }

    public function testAlwaysSerializesSlug()
    {
        $this->fieldProvider->method('getFields')->willReturn(new ArrayCollection([]));
        $adventure = $this->createMock(Adventure::class);
        $adventure->method('getSlug')->willReturn(self::SLUG);

        $doc = $this->serializer->toElasticDocument($adventure);
        $this->assertSame([
            'slug' => self::SLUG,
        ], $doc);
    }

    public function testSerializeSimpleFields()
    {
        $this->fieldProvider->method('getFields')->willReturn(new ArrayCollection([
            new Field('title', 'string', false, false, 'title'),
            new Field('link', 'url', false, false, 'link'),
            new Field('foundIn', 'url', false, false, 'foundIn'),
            new Field('minStartingLevel', 'integer', false, false, 'minStartingLevel'),
            new Field('tacticalMaps', 'boolean', false, false, 'tacticalMaps'),
        ]));

        $adventure = $this->createMock(Adventure::class);
        $adventure->method('getTitle')->willReturn(self::TITLE);
        $adventure->method('getSlug')->willReturn(self::SLUG);
        $adventure->method('getMinStartingLevel')->willReturn(self::MIN_STARTING_LEVEL);
        $adventure->method('hasTacticalMaps')->willReturn(self::TACTICAL_MAPS);
        $adventure->method('getLink')->willReturn(self::LINK);

        $doc = $this->serializer->toElasticDocument($adventure);
        $this->assertSame([
            'slug' => self::SLUG,
            'title' => self::TITLE,
            'link' => self::LINK,
            'foundIn' => null,
            'minStartingLevel' => self::MIN_STARTING_LEVEL,
            'tacticalMaps' => self::TACTICAL_MAPS,
        ], $doc);
    }

    public function testSerializeRelatedEntities()
    {
        $this->fieldProvider->method('getFields')->willReturn(new ArrayCollection([
            new Field('title', 'string', false, false, 'title'),
            new Field('authors', 'string', true, false, 'authors', null, 1, Author::class),
            new Field('publisher', 'string', false, false, 'publisher', null, 1, Publisher::class),
            new Field('edition', 'string', false, false, 'edition', null, 1, Edition::class),
        ]));

        $author1 = new Author();
        $author1->setName(self::AUTHOR_1);
        $author2 = new Author();
        $author2->setName(self::AUTHOR_2);
        $publisher = (new Publisher())->setName(self::PUBLISHER);

        $adventure = $this->createMock(Adventure::class);
        $adventure->method('getTitle')->willReturn(self::TITLE);
        $adventure->method('getSlug')->willReturn(self::SLUG);
        $adventure->method('getAuthors')->willReturn(
            new ArrayCollection([$author1, $author2])
        );
        $adventure->method('getPublisher')->willReturn(
            $publisher
        );
        $adventure->method('getEdition')->willReturn(null);

        $doc = $this->serializer->toElasticDocument($adventure);
        $this->assertSame([
            'slug' => self::SLUG,
            'title' => self::TITLE,
            'authors' => [self::AUTHOR_1, self::AUTHOR_2],
            'publisher' => self::PUBLISHER,
            'edition' => null
        ], $doc);
    }
}
