<?php

namespace WebTranslator\Controller;


use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Validator\Constraints as Assert;
use WebTranslator\Utility\TranslationHelper;
use Symfony\Component\Translation\Loader\YamlFileLoader as TranslationYamlFileLoader;


class WebTranslatorController
{
    const MODE_FULL = 'full';
    const MODE_API = 'api';

    /** @var  string */
    private $mode;

    public function __construct($mode = self::MODE_FULL)
    {
        $this->setMode($mode);
    }

    private function isApi()
    {
        return $this->mode == self::MODE_API;
    }

    public function indexAction(Application $app, Request $request)
    {
        $translator = $this->getTranslatorByOptions($app);
        $locale = $app['locale'];
        $locales = $this->getLocaleList($app);

        $totalTranslations = TranslationHelper::getRealCatalogueSize($locale, $translator);
        $totalUntranslated = TranslationHelper::getTotalUntranslated($locale, $translator, $locales);

        $context = [
            'locale' => $locale,
            'locales' => $locales,
            'totalUntranslated' => $totalUntranslated,
            'totalTranslations' => $totalTranslations,
        ];

        return $this->isApi() ? $context : $app['twig']->render('@webtranslator/index.twig', $context);
    }

    private function getTranslatorByOptions(Application $app)
    {
        if (isset($app['webtranslator.options']['library_resources'])) {

            $translator = new Translator($app['locale']);

            $translator->addLoader('yml', new TranslationYamlFileLoader());

            foreach ($app['webtranslator.options']['library_resources'] as $resource) {
                $translator->addResource($resource[0], $resource[1], $resource[2], $resource[3]);
            }

            return $translator;
        }
        return $app['translator'];
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param null|int $page
     *
     * @return RedirectResponse|array|bool
     */
    public function translationsListAction(Application $app, Request $request, $page = null)
    {
        $locale = $app['locale'];

        if (!is_numeric($page)) {
            return $this->translationsListAction($app, $request, 1);
        }

        if (!empty($targetLocale)) {
            $locale = $targetLocale;
        }

        $translator = $this->getTranslatorByOptions($app);
        $translatedCatalogues = [];
        foreach ($this->getLocaleList($app) as $otherlocale) {
            if ($otherlocale !== $locale) {
                $translatedCatalogues[$otherlocale] = $translator->getCatalogue($otherlocale)->all();
            }
        }
        $primaryCatalogue = $translator->getCatalogue($app['locale'])->all();
        if ($request->getMethod() == 'POST') {
            $translationForm = $request->get('translations');
            foreach ($translationForm as $locale => $newTranslations) {
                foreach ($newTranslations AS $domain => $translations) {
                    $domainTranslations = self::unflattenTranslationArray($translations);
                    $fullpath = $app['webtranslator.options']['translator_file_path'] . $domain . '.' . $locale . '.yml';

                    if (file_exists($fullpath)) {
                        $domainTranslations = array_merge(Yaml::parse(file_get_contents($fullpath)), $domainTranslations);
                    }
                    $str = Yaml::dump($domainTranslations, 10, 4, false, false);
                    file_put_contents($fullpath, $str);
                }
            }

            return $this->isApi() ? true : $app->redirect($app['url_generator']->generate('webtranslator.translations.list', ['page' => $page]));
        }

        $context = [
            'primaryLocale' => $locale,
            'primaryCatalogue' => $primaryCatalogue,
            'translatedCatalogues' => $translatedCatalogues,
            'locales' => $this->getLocaleList($app),
            'missingCount' => TranslationHelper::compareTotalTranslations($translator, $app['locale'], $locale),
            'page' => $page,
        ];

        return $this->isApi() ? $context : $app['twig']->render('@webtranslator/translations/list.twig', $context);
    }

    public static function unflattenTranslationArray($translationArray)
    {
        $res = [];
        foreach ($translationArray as $key => $value) {
            $key = explode('.', $key);
            $res = array_merge_recursive($res, self::flatten_yaml_key_value($key, $value));
        }
        return $res;
    }

    /**
     * @param array $arr
     * @param string $inner
     *
     * @return array
     */
    public static function flatten_yaml_key_value(array $arr, $inner = '')
    {
        $key = array_pop($arr);
        if (empty($arr)) {
            return [$key => $inner];
        } else {
            return self::flatten_yaml_key_value($arr, [$key => $inner]);
        }
    }

    private function getLocaleList(Application $app)
    {
        if (isset($app['webtranslator.options']['locales'])) {
            return $app['webtranslator.options']['locales'];
        }
        return array_unique(array_merge(array($app['translator']->getLocale()), $app['translator']->getFallbackLocales()));
    }

    private function setMode($mode)
    {
        $this->mode = $mode;
    }
}