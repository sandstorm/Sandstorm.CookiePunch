<?php
namespace Sandstorm\CookiePunch\DataSource;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Sandstorm\CookiePunch\Eel\Helper\CookiePunchConfig;

class GroupsDataSource extends AbstractDataSource
{
    /**
     * @var string
     */
    protected static $identifier = 'sandstorm-cookiepunch-groups';

    /**
     * @Flow\InjectConfiguration(package="Sandstorm.CookiePunch", path="groups")
     */
    protected $groups;

    public function getData(NodeInterface $node = null, array $arguments = [])
    {
        $CookiePunchConfig = new CookiePunchConfig();
        $options = [];
        foreach ($this->groups as $name => $group) {
            $label = isset($group["title"])
                ? $CookiePunchConfig->translate($group["title"])
                : $name;
            $options[$name] = ['label' => $label];
        }
        return $options;
    }
}
