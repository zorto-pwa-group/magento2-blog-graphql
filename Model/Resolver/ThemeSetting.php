<?php
declare(strict_types=1);

namespace ZT\BlogGraphQl\Model\Resolver;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use ZT\BlogTheme\Api\SettingRepositoryInterface;

class ThemeSetting implements ResolverInterface
{
    const DEFAULT_SELECTED_THEME = 'rozy';
    /**
     * @var ScopeConfigInterface
     */
    protected $_storeConfig;

    /**
     * @var SettingRepositoryInterface
     */
    protected $_settingRepository;

    /**
     * ThemeSetting constructor.
     * @param ScopeConfigInterface $storeConfig
     * @param SettingRepositoryInterface $settingRepository
     */
    public function __construct(
        ScopeConfigInterface $storeConfig,
        SettingRepositoryInterface $settingRepository
    )
    {
        $this->_storeConfig = $storeConfig;
        $this->_settingRepository = $settingRepository;
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
        return $this->getThemeConfigData($identifier);
    }

    /**
     * @param array $args
     * @return string
     * @throws GraphQlInputException
     */
    private function getIdentifier(array $args): string
    {
        if (!isset($args['code'])) {
            throw new GraphQlInputException(__('"Theme id should be specified'));
        }
        return (string)$args['code'];
    }

    /**
     * @param $themeId
     * @return array
     * @throws GraphQlNoSuchEntityException
     */
    private function getThemeConfigData($themeId): array
    {
        try {
            $setting = $this->_settingRepository->getById($themeId);
            $data = $setting->getData();
        } catch (Exception $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }

        return $data;
    }
}
