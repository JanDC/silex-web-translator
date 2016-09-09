<?php

namespace WebTranslator\Controller;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceControllerResolver;

class WebTranslatorControllerProvider implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        if (!$app['resolver'] instanceof ServiceControllerResolver) {
            // using RuntimeException crashes PHP?!
            throw new \LogicException('You must enable the ServiceController service provider to be able to use these routes.');
        }

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/locales', 'webtranslator.controller:localesListAction')
            ->bind('webtranslator.locales.list');

        $controllers->match('/list/{page}', 'webtranslator.controller:translationsListAction')
            ->method('GET|POST')
            ->value('page', 1)
            ->bind('webtranslator.translations.list');

        $controllers->get('/', 'webtranslator.controller:indexAction')
            ->bind('webtranslator.index');
        return $controllers;
    }


}