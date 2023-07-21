<?php
namespace DMore\ChromeExtension\Behat\ServiceContainer\Driver;

use Behat\MinkExtension\ServiceContainer\Driver\DriverFactory;
use DMore\ChromeDriver\ChromeDriver;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;

final class ChromeFactory implements DriverFactory
{
    /**
     * {@inheritdoc}
     */
    public function getDriverName()
    {
        return 'chrome';
    }

    /**
     * {@inheritdoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder->children()
            ->scalarNode('api_url')->end()
            ->booleanNode('validate_certificate')->defaultTrue()->end()
            ->enumNode('download_behavior')
                ->values(['allow', 'default', 'deny'])->defaultValue('default')->end()
            ->scalarNode('download_path')->defaultValue('/tmp')->end()
            ->integerNode('socket_timeout')->defaultValue(10)->end()
            ->integerNode('dom_wait_timeout')->defaultValue(3000)->end()
            ->end();
    }

    /**
     * {@inheritdoc}
     */
    public function buildDriver(array $config)
    {
        $validateCert = isset($config['validate_certificate']) ? $config['validate_certificate'] : true;
        $socketTimeout = $config['socket_timeout'];
        $domWaitTimeout = $config['dom_wait_timeout'];
        $downloadBehavior = $config['download_behavior'];
        $downloadPath = $config['download_path'];
        return new Definition(ChromeDriver::class, [
            $this->resolveApiUrl($config['api_url']),
            null,
            '%mink.base_url%',
            [
                'validateCertificate' => $validateCert,
                'socketTimeout' => $socketTimeout,
                'domWaitTimeout' => $domWaitTimeout,
                'downloadBehavior' => $downloadBehavior,
                'downloadPath' => $downloadPath,
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsJavascript()
    {
        return true;
    }

    private function resolveApiUrl($url)
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $url;
        }

        return str_replace($host, gethostbyname($host), $url);
    }
}
