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
use ZT\Blog\Api\CategoryRepositoryInterface;
use ZT\Blog\Model\Category;

/**
 * Blog Category field resolver
 */
class BlogCategory implements ResolverInterface
{

    /**
     * @var CategoryRepositoryInterface
     */
    private $cateRepositoryInterface;

    public function __construct(
        CategoryRepositoryInterface $cateRepositoryInterface
    )
    {
        $this->cateRepositoryInterface = $cateRepositoryInterface;
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
        return $this->getCategoryData($identifier);
    }

    /**
     * @param array $args
     * @return string
     * @throws GraphQlInputException
     */
    private function getIdentifier(array $args): string
    {
        if (!isset($args['identifier'])) {
            throw new GraphQlInputException(__('Category id should be specified'));
        }
        return (string)$args['identifier'];
    }

    /**
     * @param $identifier
     * @return array
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     */
    private function getCategoryData($identifier): array
    {
        try {
            /** @var Category $blogCategory */
            $blogCategory = $this->cateRepositoryInterface
                ->getByIdentifier($identifier);
        } catch (Exception $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        return $blogCategory->getData();
    }
}
