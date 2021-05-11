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
    protected static $identifier = 'sandstorm-cookiepunch-services';

    /**
     * @Flow\InjectConfiguration(package="Sandstorm.CookiePunch", path="consent.services")
     */
    protected $services;

    public function getData(NodeInterface $node = null, array $arguments = [])
    {
        $CookiePunchConfig = new CookiePunchConfig();
        $options = [];

        if(isset($this->services) && sizeof(array_keys($this->services)) > 0) {
            foreach ($this->services as $name => $service) {
                $label = isset($service["title"])
                    ? $CookiePunchConfig->translate($service["title"])
                    : $name;
                $options[$name] = ['label' => $label];
            }
        }

        return $options;
    }
}
