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
use ZT\Blog\Api\PostRepositoryInterface;
use ZT\Blog\Model\Post;
use Magento\Cms\Model\Template\FilterProvider;
/**
 * Blog Posts field resolver
 */
class BlogPost implements ResolverInterface
{

    /**
     * @var PostRepositoryInterface
     */

    /**
     * @var FilterProvider
     */
    protected $_filterProvider;

    private $postRepositoryInterface;

    public function __construct(
        FilterProvider $filterProvider,
        PostRepositoryInterface $postRepositoryInterface
    )
    {
        $this->_filterProvider = $filterProvider;
        $this->postRepositoryInterface = $postRepositoryInterface;
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
        return $this->getPostData($identifier);
    }

    /**
     * @param array $args
     * @return string
     * @throws GraphQlInputException
     */
    private function getIdentifier(array $args): string
    {
        if (!isset($args['identifier'])) {
            throw new GraphQlInputException(__('"Page id should be specified'));
        }
        return (string)$args['identifier'];
    }

    /**
     * @param $identifier
     * @return array
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     */
    private function getPostData($identifier): array
    {
        try {
            /** @var Post $blogPost */
            $blogPost = $this->postRepositoryInterface
                ->getByIdentifier($identifier);
        } catch (Exception $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        $html = $this->_filterProvider->getBlockFilter()->setStoreId(0)->filter($blogPost->getData('content'));
        $blogPost->setData('content', $html);
        return $blogPost->getData();
    }
}
