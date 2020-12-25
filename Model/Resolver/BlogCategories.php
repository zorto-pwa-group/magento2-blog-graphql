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
use ZT\Blog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Blog BlogCategories field resolver
 */
class BlogCategories implements ResolverInterface
{
    const TYPE_MENU = 'menu';
    const TYPE_POST_CATE = 'post_cate';

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepositoryInterface;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ResourceConnection
     */
    protected $_resource;

    /**
     * BlogCategories constructor.
     * @param CategoryRepositoryInterface $categoryRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ResourceConnection $resource
     */
    public function __construct(
        CategoryRepositoryInterface $categoryRepositoryInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ResourceConnection $resource
    )
    {
        $this->categoryRepositoryInterface = $categoryRepositoryInterface;
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
        return $this->getCategoryData($listType, $pageId);
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
     * @param $listType
     * @param $pageId
     * @return array
     * @throws GraphQlNoSuchEntityException
     * @throws LocalizedException
     */
    private function getCategoryData($listType, $pageId): array
    {
        $blogCategories = [];
        try {
            /* filter for all the categories */
            $searchCriteriaBuilder = $this->searchCriteriaBuilder
                ->addFilter('is_active', 1, 'eq');
            switch ($listType){
                case self::TYPE_MENU :
                    $searchCriteriaBuilder->addFilter('include_in_menu', 1, 'eq');
                    $searchCriteria = $searchCriteriaBuilder->create();
                    $blogCategories['menus'] = $this->categoryRepositoryInterface
                        ->getList($searchCriteria)
                        ->getItems();
                    break;
                case  self::TYPE_POST_CATE :
                    $cateIds = $this->getCategoriesOfPost($pageId);
                    $searchCriteriaBuilder->addFilter('category_id', $cateIds, 'in');
                default :
                    $searchCriteria = $searchCriteriaBuilder->create();
                    $blogCategories['categories'] = $this->categoryRepositoryInterface
                        ->getList($searchCriteria)
                        ->getItems();
                    break;
            }
        } catch (Exception $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        return $blogCategories;
    }

    /**
     * @param string $pageId
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function getCategoriesOfPost(string $pageId)
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
