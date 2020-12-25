<?php
declare(strict_types=1);

namespace ZT\BlogGraphQl\Model\Resolver;

use Exception;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use ZT\Blog\Api\PostRepositoryInterface;
use ZT\Blog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Blog Posts field resolver
 */
class BlogPosts implements ResolverInterface
{
    const HOME_POST = 'home';
    const ALL_POST = 'all';
    const CATEGORY_POST = 'category';
    const TAG_POST = 'tag';
    const RECENT_POST = 'recent';
    const POPULAR_POST = 'popular';
    const DETAIL_POST = 'detail';
    const DEFAULT_SORT = 'publish_time';
    const DEFAULT_PAGE_SIZE = 6;

    /**
     * @var PostRepositoryInterface
     */
    private $postRepositoryInterface;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepositoryInterface;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SortOrder
     */
    private $_sortOrder;

    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * BlogPosts constructor.
     * @param PostRepositoryInterface $postRepositoryInterface
     * @param CategoryRepositoryInterface $categoryRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrder $sortOrder
     * @param ResourceConnection $resource
     */
    public function __construct(
        PostRepositoryInterface $postRepositoryInterface,
        CategoryRepositoryInterface $categoryRepositoryInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrder $sortOrder,
        ResourceConnection $resource
    )
    {
        $this->postRepositoryInterface = $postRepositoryInterface;
        $this->categoryRepositoryInterface = $categoryRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_sortOrder = $sortOrder;
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
        $identifier = '';
        $pageId = $this->getPageId($args);
        $listType = $this->getListType($args);
        if($listType == self::CATEGORY_POST || $listType == self::TAG_POST) {
            $identifier = $this->getIdentifier($args);
        }

        return $this->getPostsData($listType, $identifier, $pageId);
    }

    /**
     * @param array $args
     * @return string
     * @throws GraphQlInputException
     */
    private function getListType(array $args): string
    {
        if (!isset($args['list_type'])) {
            throw new GraphQlInputException(__('Page type should be specified'));
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
        if (!isset($args['page_id'])) {
            throw new GraphQlInputException(__('"Page id should be specified'));
        }
        return (string)$args['page_id'];
    }

    /**
     * @param array $args
     * @return string
     * @throws GraphQlInputException
     */
    private function getIdentifier(array $args): string
    {
        if (!isset($args['identifier'])) {
            throw new GraphQlInputException(__('Page identifier should be specified'));
        }
        return (string)$args['identifier'];
    }

    /**
     * @param $listType
     * @param $identifier
     * @param $pageId
     * @return array
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     */
    private function getPostsData($listType, $identifier, $pageId): array
    {
        $blogPosts = [];
        try {
            $today = date("Y-m-d");
            /* filter for all the posts */
            $searchCriteriaBuilder = $this->searchCriteriaBuilder
                ->addFilter('publish_time', $today, 'lteq')
                ->addFilter('is_active', 1, 'eq');
            switch ($listType){
                case self::CATEGORY_POST:
                    $searchCriteriaBuilder->addFilter('category', (string)$identifier);
                    break;
                case self::TAG_POST:
                    $searchCriteriaBuilder->addFilter('tag', (string)$identifier);
                    break;
                case self::POPULAR_POST:
                    $searchCriteriaBuilder->addFilter('is_recommended_post', 1, 'eq');
                    break;
                default:
                    break;
            }
            $sortOrder = $this->_sortOrder
                ->setField(self::DEFAULT_SORT)
                ->setDirection("DESC");
            $searchCriteriaBuilder
                ->setSortOrders([$sortOrder])
                ->setCurrentPage($pageId)
                ->setPageSize(self::DEFAULT_PAGE_SIZE);
            $searchCriteria = $searchCriteriaBuilder
                ->create();
            $list = $this->postRepositoryInterface
                ->getList($searchCriteria);
            $blogPosts['posts'] = $list->getItems();
            $blogPosts['totalCount']  = $list->getTotalCount();
        } catch (Exception $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        return $blogPosts;
    }

    /**
     * @param string $postId
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function getNextAndPrevPosts(string $postId)
    {
        $selectN = $this->getConnection()->select()->from(
            ['main_table' => 'ztpwa_blog_post'],
            ['post_id']
        )->where(
            'main_table.identifier = "'. $postId .'"'
        );
        $postId = 0;
        $query = $this->getConnection()->query($selectP);
        while ($row = $query->fetch()) {
            $postId = $row['post_id'];
            break;
        }
        $selectC = $this->getConnection()->select()->from(
            ['main_table' => 'ztpwa_blog_post_category'],
            ['category_id']
        )->where(
            'main_table.post_id = "'. $postId .'"'
        );
        $cateIds = [];
        $queryC = $this->getConnection()->query($selectC);
        while ($rowC = $queryC->fetch()) {
            $cateIds[] = $rowC['category_id'];
        }
        return $cateIds;
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
