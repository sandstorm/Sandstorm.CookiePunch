<?php
namespace Sandstorm\CookieCutter\DataSource;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Sandstorm\CookieCutter\Eel\Helper\CookieCutterConfig;

class GroupsDataSource extends AbstractDataSource
{
    /**
     * @var string
     */
    protected static $identifier = 'sandstorm-cookiecutter-groups';

    /**
     * @Flow\InjectConfiguration(package="Sandstorm.CookieCutter", path="groups")
     */
    protected $groups;

    public function getData(NodeInterface $node = null, array $arguments = [])
    {
        $CookieCutterConfig = new CookieCutterConfig();
        $options = [];
        foreach ($this->groups as $name => $group) {
            $label = isset($group["title"])
                ? $CookieCutterConfig->translate($group["title"])
                : $name;
            $options[$name] = ['label' => $label];
        }
        return $options;
    }
}
