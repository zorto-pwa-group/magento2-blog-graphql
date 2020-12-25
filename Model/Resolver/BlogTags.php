<?php
declare(strict_types=1);

namespace ZT\BlogGraphQl\Model\Resolver;

use Exception;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use ZT\Blog\Api\TagRepositoryInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Blog BlogTags field resolver
 */
class BlogTags implements ResolverInterface
{
    const TYPE_POST_TAG = 'post_tag';

    /**
     * @var TagRepositoryInterface
     */
    private $tagRepositoryInterface;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * BlogTags constructor.
     * @param TagRepositoryInterface $tagRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ResourceConnection $resource
     */
    public function __construct(
        TagRepositoryInterface $tagRepositoryInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ResourceConnection $resource
    )
    {
        $this->tagRepositoryInterface = $tagRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_resource = $resource;
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
        $pageId = $this->getPageId($args);
        $listType = $this->getListType($args);
        return $this->getTagsData($listType, $pageId);
    }

    /**
     * @param array $args
     * @return string
     * @throws GraphQlInputException
     */
    private function getListType(array $args): string
    {
        if (!isset($args['list_type'])) {
            throw new GraphQlInputException(__('"Page type should be specified'));
        }
        return (string)$args['list_type'];
    }

    /**
     * @param array $args
     * @return string
     * @throws GraphQlInputException
     */
    private function getPageId(array $args): string
    {
        if(isset($args['page_id'])){
            return (string)$args['page_id'];
        }
        if(isset($args['page_sid'])){
            return (string)$args['page_sid'];
        }
        if (!isset($args['page_id']) && !isset($args['page_sid'])) {
            throw new GraphQlInputException(__('"Page id should be specified'));
        }
    }

    /**
     * @param $listType
     * @param $pageId
     * @return array
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     */
    private function getTagsData($listType, $pageId): array
    {
        $blogTags = [];
        try {
            /* filter for all the tags */
            $searchCriteriaBuilder = $this->searchCriteriaBuilder
                ->addFilter('is_active', 1, 'eq');
            switch ($listType){
                case  self::TYPE_POST_TAG :
                    $tagIds = $this->getTagsOfPost($pageId);
                    $searchCriteriaBuilder->addFilter('tag_id', $tagIds, 'in');
                    break;
                default :
                    break;
            }
            $searchCriteria = $searchCriteriaBuilder->create();
            $blogTags['tags'] = $this->tagRepositoryInterface
                ->getList($searchCriteria)
                ->getItems();
        } catch (Exception $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        return $blogTags;
    }

    /**
     * @param string $pageId
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function getTagsOfPost(string $pageId)
    {
        $selectP = $this->getConnection()->select()->from(
            ['main_table' => 'ztpwa_blog_post'],
            ['post_id']
        )->where(
            'main_table.identifier = "'. $pageId .'"'
        );
        $postId = 0;
        $query = $this->getConnection()->query($selectP);
        while ($row = $query->fetch()) {
            $postId = $row['post_id'];
            break;
        }
        $selectC = $this->getConnection()->select()->from(
            ['main_table' => 'ztpwa_blog_post_tag'],
            ['tag_id']
        )->where(
            'main_table.post_id = "'. $postId .'"'
        );
        $tagIds = [];
        $queryC = $this->getConnection()->query($selectC);
        while ($rowC = $queryC->fetch()) {
            $tagIds[] = $rowC['tag_id'];
        }
        return $tagIds;
    }

    /**
     * @inheritDoc
     * @since 100.1.0
     */
    public function getConnection()
    {
        return $this->_resource->getConnection();
    }
}
