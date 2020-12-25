<?php
declare(strict_types=1);

namespace ZT\BlogGraphQl\Model\Resolver;

use Exception;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use ZT\Blog\Api\TagRepositoryInterface;
use ZT\Blog\Model\Tag;

/**
 * Blog Tags field resolver
 */
class BlogTag implements ResolverInterface
{

    /**
     * @var TagRepositoryInterface
     */
    private $tagRepositoryInterface;

    public function __construct(
        TagRepositoryInterface $tagRepositoryInterface
    )
    {
        $this->tagRepositoryInterface = $tagRepositoryInterface;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    )
    {
        $identifier = $this->getIdentifier($args);
        return $this->getTagData($identifier);
    }

    /**
     * @param array $args
     * @return string
     * @throws GraphQlInputException
     */
    private function getIdentifier(array $args): string
    {
        if (!isset($args['identifier'])) {
            throw new GraphQlInputException(__('Tag id should be specified'));
        }
        return (string)$args['identifier'];
    }

    /**
     * @param $identifier
     * @return array
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     */
    private function getTagData($identifier): array
    {
        try {
            /** @var Tag $blogTag */
            $blogTag = $this->tagRepositoryInterface
                ->getByIdentifier($identifier);
        } catch (Exception $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        return $blogTag->getData();
    }
}
